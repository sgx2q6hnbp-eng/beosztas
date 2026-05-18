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
        $user = $_SESSION['user'];
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd   = date('Y-m-d', strtotime('sunday this week'));

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM shifts WHERE shift_date = :today AND status = 'active'"
        );
        $stmt->execute([':today' => $today]);
        $todayShiftCount = (int)$stmt->fetchColumn();

        $isAdmin = ($user['role'] === 'admin');
        if ($isAdmin) {
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
            $stmt->execute([':start' => $weekStart, ':end' => $weekEnd, ':fleet_id' => $user['fleet_id']]);
        }
        $weekShifts = $stmt->fetchAll();

        $pendingLeaves = 0;
        if ($isAdmin) {
            $stmt = $this->db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'");
            $pendingLeaves = (int)$stmt->fetchColumn();
        }

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
