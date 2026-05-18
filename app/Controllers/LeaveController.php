<?php
declare(strict_types=1);

class LeaveController
{
    private PDO $db;

    public function __construct()
    {
        AuthService::requireLogin();
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $userId = $_SESSION['user']['id'];

        $leaves = $this->db->prepare(
            "SELECT * FROM leave_requests
             WHERE user_id = :uid
             ORDER BY created_at DESC
             LIMIT 20"
        );
        $leaves->execute([':uid' => $userId]);
        $leaves = $leaves->fetchAll();

        require BASE_PATH . '/app/Views/leave/index.php';
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /leave'); exit;
        }

        // CSRF ellenőrzés
        if (!User::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Érvénytelen kérés. Próbáld újra.';
            header('Location: /leave'); exit;
        }

        $userId    = $_SESSION['user']['id'];
        $leaveType = $_POST['leave_type'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate   = $_POST['end_date']   ?? '';
        $reason    = trim($_POST['reason'] ?? '');

        // Validáció
        $allowed = ['vacation','sick','unpaid','other'];
        if (!in_array($leaveType, $allowed, true)) {
            $_SESSION['error'] = 'Érvénytelen szabadság típus.';
            header('Location: /leave'); exit;
        }
        if (!$startDate || !$endDate || $endDate < $startDate) {
            $_SESSION['error'] = 'Érvénytelen dátum intervallum.';
            header('Location: /leave'); exit;
        }

        // Átfedés ellenőrzés
        $overlap = $this->db->prepare(
            "SELECT COUNT(*) FROM leave_requests
             WHERE user_id = :uid
               AND status != 'rejected'
               AND start_date <= :end
               AND end_date   >= :start"
        );
        $overlap->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
        if ((int)$overlap->fetchColumn() > 0) {
            $_SESSION['error'] = 'Erre az időszakra már van kérelem benyújtva.';
            header('Location: /leave'); exit;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason)
             VALUES (:uid, :type, :start, :end, :reason)"
        );
        $stmt->execute([
            ':uid'    => $userId,
            ':type'   => $leaveType,
            ':start'  => $startDate,
            ':end'    => $endDate,
            ':reason' => $reason ?: null,
        ]);

                // E-mail értesítés az adminnak
        MailService::notifyAdminNewLeave(
            ['name' => $_SESSION['user']['name'], 'email' => $_SESSION['user']['email']],
            ['leave_type' => $leaveType, 'start_date' => $startDate, 'end_date' => $endDate]
        );
        $_SESSION['success'] = 'Szabadságkérelem sikeresen beküldve!';
        header('Location: /leave'); exit;
    }
}
