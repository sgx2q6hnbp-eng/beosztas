<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/Services/Database.php';
require_once BASE_PATH . '/app/Services/AuthService.php';

class ScheduleController
{
    private PDO $db;

    public function __construct()
    {
        AuthService::requireLogin();
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $user    = $_SESSION['user'];
        $isAdmin = ($user['role'] === 'admin');

        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));
        $month = max(1, min(12, $month));
        $year  = max(2020, min(2100, $year));

        $firstDay = sprintf('%04d-%02d-01', $year, $month);
        $lastDay  = date('Y-m-t', strtotime($firstDay));

        if ($isAdmin) {
            $stmt = $this->db->prepare(
                "SELECT s.*, u.name AS employee_name, f.name AS fleet_name, f.color
                 FROM shifts s
                 JOIN users u ON u.id = s.user_id
                 JOIN fleets f ON f.id = s.fleet_id
                 WHERE s.shift_date BETWEEN :first AND :last
                 ORDER BY s.shift_date ASC, f.name ASC, CASE WHEN s.status = 'active' THEN 0 ELSE 1 END ASC, u.name ASC"
            );
            $stmt->execute([':first' => $firstDay, ':last' => $lastDay]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT s.*, u.name AS employee_name, f.name AS fleet_name, f.color
                 FROM shifts s
                 JOIN users u ON u.id = s.user_id
                 JOIN fleets f ON f.id = s.fleet_id
                 WHERE s.shift_date BETWEEN :first AND :last
                   AND s.fleet_id = :fleet_id
                 ORDER BY s.shift_date ASC, f.name ASC, CASE WHEN s.status = 'active' THEN 0 ELSE 1 END ASC, u.name ASC"
            );
            $stmt->execute([':first' => $firstDay, ':last' => $lastDay, ':fleet_id' => $user['fleet_id']]);
        }
        $shifts = $stmt->fetchAll();

        $shiftsByDate = [];
        foreach ($shifts as $shift) {
            $shiftsByDate[$shift['shift_date']][] = $shift;
        }

        require BASE_PATH . '/app/Views/schedule/index.php';
    }

    public function toggleOvertime(): void
    {
        AuthService::requireLogin();

        $shiftId = (int)($_POST['shift_id'] ?? 0);

        if ($shiftId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Érvénytelen műszak azonosító.']);
            return;
        }

        // Csak admin vagy saját műszak
        $user    = $_SESSION['user'];
        $isAdmin = ($user['role'] === 'admin');

        if ($isAdmin) {
            $stmt = $this->db->prepare("SELECT id, is_overtime FROM shifts WHERE id = :id");
            $stmt->execute([':id' => $shiftId]);
        } else {
            $stmt = $this->db->prepare("SELECT id, is_overtime FROM shifts WHERE id = :id AND user_id = :uid");
            $stmt->execute([':id' => $shiftId, ':uid' => $user['id']]);
        }

        $shift = $stmt->fetch();

        if (!$shift) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Nincs jogosultságod ehhez a művelethez.']);
            return;
        }

        $newValue = $shift['is_overtime'] ? 0 : 1;

        $update = $this->db->prepare("UPDATE shifts SET is_overtime = :val WHERE id = :id");
        $update->execute([':val' => $newValue, ':id' => $shiftId]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'is_overtime' => (bool)$newValue]);
    }


    public function addShift(): void
    {
        AuthService::requireAdmin();

        $userId    = (int)($_POST['user_id']    ?? 0);
        $shiftDate = trim($_POST['shift_date']  ?? '');
        $startTime = trim($_POST['start_time']  ?? '06:00');
        $endTime   = trim($_POST['end_time']    ?? '18:00');
        $location  = trim($_POST['location']    ?? '');
        $plate     = trim($_POST['license_plate'] ?? '');

        header('Content-Type: application/json');

        if ($userId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $shiftDate)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Hiányzó vagy hibás adatok.']);
            return;
        }

        // Felhasználó fleet_id lekérése
        $uStmt = $this->db->prepare("SELECT id, name, fleet_id FROM users WHERE id = :id AND is_active = 1");
        $uStmt->execute([':id' => $userId]);
        $user = $uStmt->fetch();
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Dolgozó nem található.']);
            return;
        }

        // Duplikáció ellenőrzés
        $chk = $this->db->prepare("SELECT id FROM shifts WHERE user_id = :uid AND shift_date = :date");
        $chk->execute([':uid' => $userId, ':date' => $shiftDate]);
        if ($chk->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Ennek a dolgozónak már van beosztása ezen a napon.']);
            return;
        }

        $ins = $this->db->prepare(
            "INSERT INTO shifts (user_id, fleet_id, shift_date, start_time, end_time, location, license_plate, status)
             VALUES (:uid, :fid, :date, :start, :end, :loc, :plate, 'active')"
        );
        $ins->execute([
            ':uid'   => $userId,
            ':fid'   => $user['fleet_id'],
            ':date'  => $shiftDate,
            ':start' => $startTime,
            ':end'   => $endTime,
            ':loc'   => $location ?: null,
            ':plate' => $plate ?: null,
        ]);

        $newId = (int)$this->db->lastInsertId();

        // Fleet szín lekérése
        $fStmt = $this->db->prepare("SELECT name, color FROM fleets WHERE id = :id");
        $fStmt->execute([':id' => $user['fleet_id']]);
        $fleet = $fStmt->fetch();

        echo json_encode([
            'success' => true,
            'shift'   => [
                'id'            => $newId,
                'user_id'       => $userId,
                'employee_name' => $user['name'],
                'fleet_name'    => $fleet['name'] ?? '',
                'color'         => $fleet['color'] ?? '#64748b',
                'shift_date'    => $shiftDate,
                'start_time'    => $startTime,
                'end_time'      => $endTime,
                'location'      => $location,
                'license_plate' => $plate,
                'status'        => 'active',
                'is_overtime'   => false,
            ]
        ]);
    }

    public function deleteShift(): void
    {
        AuthService::requireAdmin();

        $shiftId = (int)($_POST['shift_id'] ?? 0);

        header('Content-Type: application/json');

        if ($shiftId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Érvénytelen azonosító.']);
            return;
        }

        $chk = $this->db->prepare("SELECT id FROM shifts WHERE id = :id");
        $chk->execute([':id' => $shiftId]);
        if (!$chk->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'A műszak nem található.']);
            return;
        }

        $del = $this->db->prepare("DELETE FROM shifts WHERE id = :id");
        $del->execute([':id' => $shiftId]);

        echo json_encode(['success' => true]);
    }

}