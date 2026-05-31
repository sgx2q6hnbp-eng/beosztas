<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/Services/Database.php';
require_once BASE_PATH . '/app/Services/AuthService.php';
require_once BASE_PATH . '/app/Controllers/HolidayController.php';

class MyScheduleController
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
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));
        $month = max(1, min(12, $month));
        $year  = max(2020, min(2100, $year));

        $firstDay    = sprintf('%04d-%02d-01', $year, $month);
        $lastDay     = date('Y-m-t', strtotime($firstDay));
        $daysInMonth = (int)date('t', strtotime($firstDay));

        // Saját műszakok az adott hónapra
        $stmt = $this->db->prepare(
            "SELECT shift_date, start_time, end_time, location, license_plate, status, is_overtime
             FROM shifts
             WHERE user_id = :uid AND shift_date BETWEEN :first AND :last
             ORDER BY shift_date ASC"
        );
        $stmt->execute([':uid' => $user['id'], ':first' => $firstDay, ':last' => $lastDay]);
        $shiftsRaw = $stmt->fetchAll();

        $shiftMap = [];
        foreach ($shiftsRaw as $s) {
            $shiftMap[$s['shift_date']] = $s;
        }

        // Ünnepnapok
        $holidays = HolidayController::getHolidaysForMonth($this->db, $year, $month);

        require BASE_PATH . '/app/Views/my-schedule/index.php';
    }
}
