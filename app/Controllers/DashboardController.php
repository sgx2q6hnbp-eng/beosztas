<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/Services/Database.php';
require_once BASE_PATH . '/app/Services/AuthService.php';

class DashboardController
{
    private PDO $db;

    public function __construct()
    {
        AuthService::requireLogin();
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $user  = $_SESSION['user'];
        $today = date('Y-m-d');
        $isAdmin = ($user['role'] === 'admin');

        // Hét navigáció: ?week=2026-W21 formátum, alapértelmezett: aktuális hét
        $weekParam = trim($_GET['week'] ?? '');
        if (preg_match('/^\d{4}-W(0[1-9]|[1-4]\d|5[0-3])$/', $weekParam)) {
            $weekStart = date('Y-m-d', strtotime($weekParam . '-1')); // hétfő
        } else {
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $weekParam = date('o-\WW', strtotime($weekStart));
        }
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

        // Előző / következő hét ISO paramétere
        $prevWeek = date('o-\WW', strtotime($weekStart . ' -7 days'));
        $nextWeek = date('o-\WW', strtotime($weekStart . ' +7 days'));
        $thisWeek = date('o-\WW', strtotime('monday this week'));
        $isCurrentWeek = ($weekParam === $thisWeek);

        // Mai műszakok száma
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM shifts WHERE shift_date = :today AND status = 'active'"
        );
        $stmt->execute([':today' => $today]);
        $todayShiftCount = (int)$stmt->fetchColumn();

        // Heti beosztás
        $fleetId = !empty($user['fleet_id']) ? (int)$user['fleet_id'] : null;

        if ($isAdmin || $fleetId === null) {
            $stmt = $this->db->prepare(
                "SELECT s.*, u.name AS employee_name, f.name AS fleet_name, f.color
                 FROM shifts s
                 JOIN users u ON u.id = s.user_id
                 JOIN fleets f ON f.id = s.fleet_id
                 WHERE s.shift_date BETWEEN :start AND :end
                 ORDER BY s.shift_date ASC, u.name ASC"
            );
            $stmt->execute([':start' => $weekStart, ':end' => $weekEnd]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT s.*, u.name AS employee_name, f.name AS fleet_name, f.color
                 FROM shifts s
                 JOIN users u ON u.id = s.user_id
                 JOIN fleets f ON f.id = s.fleet_id
                 WHERE s.shift_date BETWEEN :start AND :end
                   AND s.fleet_id = :fleet_id
                 ORDER BY s.shift_date ASC, u.name ASC"
            );
            $stmt->execute([':start' => $weekStart, ':end' => $weekEnd, ':fleet_id' => $fleetId]);
        }
        $weekShifts = $stmt->fetchAll();

        // Függő szabadságkérelmek (csak admin)
        $pendingLeaves = 0;
        if ($isAdmin) {
            $stmt = $this->db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'");
            $pendingLeaves = (int)$stmt->fetchColumn();
        }

        // Következő saját műszak
        $stmt = $this->db->prepare(
            "SELECT shift_date, start_time, end_time, location, license_plate
             FROM shifts
             WHERE user_id = :uid AND shift_date >= :today AND status = 'active'
             ORDER BY shift_date ASC LIMIT 1"
        );
        $stmt->execute([':uid' => $user['id'], ':today' => $today]);
        $nextShift = $stmt->fetch() ?: null;

        require BASE_PATH . '/app/Views/dashboard/index.php';
    }
}
