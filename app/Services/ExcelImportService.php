<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/Services' . '/Database.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * ExcelImportService
 * Feldolgozza a feltöltött .xlsx munkabeosztás sablont és
 * az adatokat a shifts táblába menti.
 *
 * Elvárt oszlopok a sablonban:
 *   A = Flotta  (I. / II. / I / II)
 *   B = Dátum   (ÉÉÉÉ.HH.NN vagy Excel dátumszám)
 *   C = Nap     (nem importált, csak olvashatóság)
 *   D = Törzsszám
 *   E = Dolgozó neve
 *   F = Rendszám
 *   G = Település
 *   H = Megjegyzés (opcionális)
 */
class ExcelImportService
{
    private PDO $db;

    private int $inserted  = 0;
    private int $updated   = 0;
    private int $skipped   = 0;
    private array $errors  = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function import(string $filePath): array
    {
        $this->validateFile($filePath);

        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true);

        $dataRows = array_filter($rows, fn($key) => $key >= 5, ARRAY_FILTER_USE_KEY);

        $this->db->beginTransaction();

        try {
            foreach ($dataRows as $rowNum => $row) {
                $this->processRow($row, $rowNum);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw new \RuntimeException('Import megszakadt: ' . $e->getMessage());
        }

        @unlink($filePath);

        return $this->getSummary();
    }

    private function processRow(array $row, int $rowNum): void
    {
        if (empty(trim((string)($row['A'] ?? ''))) && empty(trim((string)($row['D'] ?? ''))) && empty(trim((string)($row['E'] ?? '')))) {
            return;
        }

        $fleetRaw  = trim((string)($row['A'] ?? ''));
        $dateRaw   = $row['B'] ?? null;
        $empNumber = trim((string)($row['D'] ?? ''));
        $nameRaw   = trim((string)($row['E'] ?? ''));
        $plateRaw  = strtoupper(trim((string)($row['F'] ?? '')));
        $location  = trim((string)($row['G'] ?? ''));
        $note      = trim((string)($row['H'] ?? ''));

        // Flotta azonosítás – ponttal és pont nélkül is elfogadjuk
        $fleetId = match(rtrim($fleetRaw, '.')) {
            'I'  => 1,
            'II' => 2,
            default => null,
        };

        if ($fleetId === null) {
            $this->errors[] = "{$rowNum}. sor: Ismeretlen flotta érték: '{$fleetRaw}'";
            $this->skipped++;
            return;
        }

        $shiftDate = $this->parseDate($dateRaw);
        if ($shiftDate === null) {
            $this->errors[] = "{$rowNum}. sor: Érvénytelen dátum: '{$dateRaw}'";
            $this->skipped++;
            return;
        }

        if (empty($empNumber) && empty($nameRaw)) {
            $this->errors[] = "{$rowNum}. sor: Hiányzó törzsszám és dolgozó neve.";
            $this->skipped++;
            return;
        }

        $lookupKey = !empty($empNumber) ? $empNumber : $nameRaw;
        $userId = $this->resolveUserId($lookupKey, $fleetId);
        if ($userId === null) {
            $this->errors[] = "{$rowNum}. sor: Ismeretlen dolgozó: '{$empNumber}' / '{$nameRaw}'";
            $this->skipped++;
            return;
        }

        $existing = $this->findExistingShift($userId, $shiftDate);

        if ($existing) {
            if (in_array($existing['status'], ['sick', 'vacation', 'absence'], true)) {
                $this->skipped++;
                return;
            }
            $this->updateShift($existing['id'], $fleetId, $plateRaw, $location, $note);
            $this->updated++;
        } else {
            $this->insertShift($userId, $fleetId, $shiftDate, $plateRaw, $location, $note);
            $this->inserted++;
        }
    }

    private function parseDate(mixed $value): ?string
    {
        if (empty($value)) return null;

        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float)$value);
                return $dt->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        if (preg_match('/^(\d{4})\.(\d{2})\.(\d{2})$/', trim((string)$value), $m)) {
            $date = sprintf('%s-%s-%s', $m[1], $m[2], $m[3]);
            return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? $date : null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string)$value))) {
            return trim((string)$value);
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', trim((string)$value), $m)) {
            $date = sprintf('%s-%02d-%02d', $m[3], (int)$m[1], (int)$m[2]);
            return checkdate((int)$m[1], (int)$m[2], (int)$m[3]) ? $date : null;
        }

        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', trim((string)$value), $m)) {
            $date = sprintf('%s-%02d-%02d', $m[3], (int)$m[1], (int)$m[2]);
            return checkdate((int)$m[1], (int)$m[2], (int)$m[3]) ? $date : null;
        }

        return null;
    }

    private function resolveUserId(string $name, int $fleetId): ?int
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM users
             WHERE employee_number = :en AND is_active = 1
             LIMIT 1"
        );
        $stmt->execute([':en' => $name]);
        $row = $stmt->fetch();
        if ($row) return (int)$row['id'];

        $stmt2 = $this->db->prepare(
            "SELECT id FROM users
             WHERE name = :name AND fleet_id = :fleet AND is_active = 1
             LIMIT 1"
        );
        $stmt2->execute([':name' => $name, ':fleet' => $fleetId]);
        $row2 = $stmt2->fetch();
        if ($row2) return (int)$row2['id'];

        $stmt3 = $this->db->prepare(
            "SELECT id FROM users
             WHERE name LIKE :name AND fleet_id = :fleet AND is_active = 1
             LIMIT 1"
        );
        $stmt3->execute([':name' => '%' . $name . '%', ':fleet' => $fleetId]);
        $row3 = $stmt3->fetch();
        return $row3 ? (int)$row3['id'] : null;
    }

    private function findExistingShift(int $userId, string $date): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, status FROM shifts
             WHERE user_id = :uid AND shift_date = :date
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId, ':date' => $date]);
        return $stmt->fetch() ?: null;
    }

    private function insertShift(
        int $userId, int $fleetId, string $date,
        string $plate, string $location, string $note
    ): void {
        // Jóváhagyott távollét ellenőrzése – külön named paraméterekkel
        $leaveCheck = $this->db->prepare(
            "SELECT leave_type FROM leave_requests
             WHERE user_id = :lc_uid
               AND status = 'approved'
               AND start_date <= :lc_date
               AND end_date   >= :lc_date2
             LIMIT 1"
        );
        $leaveCheck->execute([
            ':lc_uid'   => $userId,
            ':lc_date'  => $date,
            ':lc_date2' => $date,
        ]);
        $leave = $leaveCheck->fetch();

        $status = 'active';
        if ($leave) {
            $status = $leave['leave_type'] === 'sick' ? 'sick' : 'vacation';
        }

        $stmt = $this->db->prepare(
            "INSERT INTO shifts
                (user_id, fleet_id, shift_date, start_time, end_time,
                 location, license_plate, status, note, imported_at)
             VALUES
                (:uid, :fleet, :date, '06:00:00', '18:00:00',
                 :loc, :plate, :status, :note, NOW())"
        );
        $stmt->execute([
            ':uid'    => $userId,
            ':fleet'  => $fleetId,
            ':date'   => $date,
            ':loc'    => $location,
            ':plate'  => $plate,
            ':status' => $status,
            ':note'   => $note,
        ]);
    }

    private function updateShift(
        int $shiftId, int $fleetId,
        string $plate, string $location, string $note
    ): void {
        $stmt = $this->db->prepare(
            "UPDATE shifts SET
                fleet_id      = :fleet,
                location      = :loc,
                license_plate = :plate,
                note          = :note,
                imported_at   = NOW()
             WHERE id = :id"
        );
        $stmt->execute([
            ':fleet' => $fleetId,
            ':loc'   => $location,
            ':plate' => $plate,
            ':note'  => $note,
            ':id'    => $shiftId,
        ]);
    }

    private function validateFile(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException('A feltöltött fájl nem található.');
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') {
            throw new \RuntimeException('Csak .xlsx formátumú fájl fogadható el.');
        }

        $maxSize = 5 * 1024 * 1024;
        if (filesize($path) > $maxSize) {
            throw new \RuntimeException('A fájl mérete meghaladja az 5 MB-os korlátot.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($path);
        $allowed = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
        ];
        if (!in_array($mime, $allowed, true)) {
            throw new \RuntimeException('Érvénytelen fájltípus.');
        }
    }

    public function getSummary(): array
    {
        return [
            'inserted' => $this->inserted,
            'updated'  => $this->updated,
            'skipped'  => $this->skipped,
            'errors'   => $this->errors,
            'success'  => empty($this->errors) || ($this->inserted + $this->updated) > 0,
        ];
    }
}
