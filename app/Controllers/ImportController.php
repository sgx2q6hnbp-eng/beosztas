<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/Controllers' . '/../Services/AuthService.php';
require_once BASE_PATH . '/app/Controllers' . '/../Services/ExcelImportService.php';

/**
 * ImportController – Excel feltöltés és feldolgozás
 */
class ImportController
{
    private string $uploadDir;

    public function __construct()
    {
        AuthService::requireAdmin();
        $this->uploadDir = BASE_PATH . '/uploads/excel/';
    }

    /**
     * GET /admin/import – Feltöltő oldal megjelenítése
     */
    public function showForm(): void
    {
        $lastImports = $this->getLastImports();
        require BASE_PATH . '/app/Views/admin/import.php';
    }

    /**
     * GET /admin/import/template – Excel sablon letöltése
     */
    public function downloadTemplate(): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Muszakok');

        $sheet->setCellValue('A1', 'Munkabeosztás Import Sablon');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '01696F']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->setCellValue('A2', 'Flotta: I. vagy II. | Datum: YYYY.MM.DD | Az 5. sortol kezdj el adatot beirni!');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '555555']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F0EC']],
        ]);

        $sheet->getRowDimension(3)->setRowHeight(8);

        $headers = [
            'A4' => 'Flotta',
            'B4' => 'Datum',
            'C4' => 'Nap',
            'D4' => 'Torzsszam',
            'E4' => 'Dolgozo neve',
            'F4' => 'Rendszam',
            'G4' => 'Telepules',
            'H4' => 'Megjegyzes',
        ];
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        $sheet->getStyle('A4:H4')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0C4E54']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->setCellValue('A5', 'I.');
        $sheet->setCellValue('B5', date('Y.m.d'));
        $sheet->setCellValue('C5', 'Hetfo');
        $sheet->setCellValue('D5', 'T-0001');
        $sheet->setCellValue('E5', 'Kovacs Janos');
        $sheet->setCellValue('F5', 'ABC-123');
        $sheet->setCellValue('G5', 'Budapest');
        $sheet->setCellValue('H5', 'Pelda sor - torolheto');
        $sheet->getStyle('A5:H5')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '888888']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EDEDE0']],
        ]);

        foreach (['A' => 8, 'B' => 13, 'C' => 10, 'D' => 12, 'E' => 22, 'F' => 12, 'G' => 18, 'H' => 25] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(4)->setRowHeight(20);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="muszak_sablon_' . date('Ymd') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * POST /admin/import – Fájl feltöltése és feldolgozása
     */
    public function handle(): void
    {
        if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->redirectWithError('Érvénytelen kérés.');
        }

        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $this->redirectWithError($this->uploadErrorMessage($_FILES['excel_file']['error'] ?? -1));
        }

        $tmpPath  = $_FILES['excel_file']['tmp_name'];
        $origName = basename($_FILES['excel_file']['name']);

        $safeName = sprintf('import_%s_%s.xlsx', date('Ymd_His'), bin2hex(random_bytes(4)));
        $destPath = $this->uploadDir . $safeName;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            $this->redirectWithError('A fájl mentése sikertelen.');
        }

        try {
            $service = new ExcelImportService();
            $result  = $service->import($destPath);

            $_SESSION['import_result'] = $result;
            $_SESSION['success'] = sprintf(
                'Import kész! Új: %d sor | Frissített: %d sor | Kihagyott: %d sor',
                $result['inserted'],
                $result['updated'],
                $result['skipped']
            );

            $this->logImport($origName, $result);

        } catch (\RuntimeException $e) {
            $this->redirectWithError('Import hiba: ' . $e->getMessage());
        }

        header('Location: /admin/import');
        exit;
    }

    // ── Segédfüggvények ─────────────────────────────────────────────────

    private function getLastImports(): array
    {
        $db   = \Database::getInstance();
        $stmt = $db->query(
            "SELECT sl.changed_at, u.name AS changed_by, sl.new_value
             FROM shift_logs sl
             JOIN users u ON u.id = sl.changed_by
             WHERE sl.change_type = 'create'
             ORDER BY sl.changed_at DESC
             LIMIT 10"
        );
        return $stmt->fetchAll();
    }

    private function logImport(string $filename, array $result): void
    {
        $db   = \Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO shift_logs (changed_by, change_type, new_value)
             VALUES (:uid, 'create', :val)"
        );
        $stmt->execute([
            ':uid' => AuthService::userId(),
            ':val' => json_encode([
                'file'     => $filename,
                'inserted' => $result['inserted'],
                'updated'  => $result['updated'],
                'skipped'  => $result['skipped'],
            ]),
        ]);
    }

    private function redirectWithError(string $msg): never
    {
        $_SESSION['error'] = $msg;
        header('Location: /admin/import');
        exit;
    }

    private function uploadErrorMessage(int $code): string
    {
        return match($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'A fájl túl nagy.',
            UPLOAD_ERR_PARTIAL  => 'A feltöltés megszakadt.',
            UPLOAD_ERR_NO_FILE  => 'Nem választottál fájlt.',
            default             => 'Ismeretlen feltöltési hiba.',
        };
    }
}
