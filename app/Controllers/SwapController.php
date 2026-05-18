<?php
declare(strict_types=1);

class SwapController
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

        // Saját jövőbeli műszakok
        $myShifts = $this->db->prepare(
            "SELECT s.*, f.name AS fleet_name, f.color
             FROM shifts s
             LEFT JOIN fleets f ON f.id = s.fleet_id
             WHERE s.user_id = :uid
               AND s.shift_date >= CURDATE()
               AND s.status = 'active'
             ORDER BY s.shift_date ASC
             LIMIT 30"
        );
        $myShifts->execute([':uid' => $userId]);
        $myShifts = $myShifts->fetchAll();

        // Összes többi dolgozó jövőbeli műszakja
        $otherShifts = $this->db->prepare(
            "SELECT s.*, u.name AS employee_name, f.name AS fleet_name, f.color
             FROM shifts s
             LEFT JOIN users u ON u.id = s.user_id
             LEFT JOIN fleets f ON f.id = s.fleet_id
             WHERE s.user_id != :uid
               AND s.shift_date >= CURDATE()
               AND s.status = 'active'
             ORDER BY s.shift_date ASC
             LIMIT 60"
        );
        $otherShifts->execute([':uid' => $userId]);
        $otherShifts = $otherShifts->fetchAll();

        // Saját korábbi csere kérelmek
        $mySwaps = $this->db->prepare(
            "SELECT sr.*,
                    u.name  AS target_name,
                    rs.shift_date AS my_date,
                    rs.start_time AS my_start,
                    rs.end_time   AS my_end,
                    ts.shift_date AS their_date,
                    ts.start_time AS their_start,
                    ts.end_time   AS their_end
             FROM swap_requests sr
             LEFT JOIN users u  ON u.id  = sr.target_id
             LEFT JOIN shifts rs ON rs.id = sr.requester_shift_id
             LEFT JOIN shifts ts ON ts.id = sr.target_shift_id
             WHERE sr.requester_id = :uid
             ORDER BY sr.created_at DESC
             LIMIT 20"
        );
        $mySwaps->execute([':uid' => $userId]);
        $mySwaps = $mySwaps->fetchAll();

        // Bejövő csere kérelmek (mások kérnek tőlem)
        $incomingSwaps = $this->db->prepare(
            "SELECT sr.*,
                    u.name  AS requester_name,
                    rs.shift_date AS their_date,
                    rs.start_time AS their_start,
                    rs.end_time   AS their_end,
                    ts.shift_date AS my_date,
                    ts.start_time AS my_start,
                    ts.end_time   AS my_end
             FROM swap_requests sr
             LEFT JOIN users u  ON u.id  = sr.requester_id
             LEFT JOIN shifts rs ON rs.id = sr.requester_shift_id
             LEFT JOIN shifts ts ON ts.id = sr.target_shift_id
             WHERE sr.target_id = :uid
               AND sr.status = 'pending'
             ORDER BY sr.created_at DESC"
        );
        $incomingSwaps->execute([':uid' => $userId]);
        $incomingSwaps = $incomingSwaps->fetchAll();

        require BASE_PATH . '/app/Views/swap/index.php';
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /swap'); exit;
        }

        if (!User::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Érvénytelen kérés.';
            header('Location: /swap'); exit;
        }

        $userId          = $_SESSION['user']['id'];
        $myShiftId       = (int)($_POST['requester_shift_id'] ?? 0);
        $targetShiftId   = (int)($_POST['target_shift_id']    ?? 0);
        $message         = trim($_POST['message'] ?? '');

        if (!$myShiftId || !$targetShiftId || $myShiftId === $targetShiftId) {
            $_SESSION['error'] = 'Kérlek válassz két különböző műszakot.';
            header('Location: /swap'); exit;
        }

        // Ellenőrzés: valóban a saját műszaka-e
        $check = $this->db->prepare(
            "SELECT id, user_id FROM shifts WHERE id = :id"
        );
        $check->execute([':id' => $myShiftId]);
        $myShift = $check->fetch();

        if (!$myShift || (int)$myShift['user_id'] !== (int)$userId) {
            $_SESSION['error'] = 'Ez a műszak nem a tiéd.';
            header('Location: /swap'); exit;
        }

        // Target műszak tulajdonosa
        $check->execute([':id' => $targetShiftId]);
        $targetShift = $check->fetch();

        if (!$targetShift || (int)$targetShift['user_id'] === (int)$userId) {
            $_SESSION['error'] = 'Érvénytelen célmőszak.';
            header('Location: /swap'); exit;
        }

        $targetUserId = (int)$targetShift['user_id'];

        // Duplikált kérelem ellenőrzés
        $dup = $this->db->prepare(
            "SELECT COUNT(*) FROM swap_requests
             WHERE requester_id = :uid
               AND requester_shift_id = :rsid
               AND status = 'pending'"
        );
        $dup->execute([':uid' => $userId, ':rsid' => $myShiftId]);
        if ((int)$dup->fetchColumn() > 0) {
            $_SESSION['error'] = 'Erre a műszakra már van függő csere kérelem.';
            header('Location: /swap'); exit;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO swap_requests
                (requester_id, target_id, requester_shift_id, target_shift_id, message)
             VALUES (:req, :tgt, :rsid, :tsid, :msg)"
        );
        $stmt->execute([
            ':req'  => $userId,
            ':tgt'  => $targetUserId,
            ':rsid' => $myShiftId,
            ':tsid' => $targetShiftId,
            ':msg'  => $message ?: null,
        ]);

        // Saját műszak státusza swap_pending-re
        $this->db->prepare(
            "UPDATE shifts SET status = 'swap_pending' WHERE id = :id"
        )->execute([':id' => $myShiftId]);


        // Célszemély és kérelmező adatai az e-mailhez
        $usersStmt = $this->db->prepare("SELECT id, name, email FROM users WHERE id IN (:req, :tgt)");
        $usersStmt->execute([':req' => $userId, ':tgt' => $targetUserId]);
        $usersMap = [];
        foreach ($usersStmt->fetchAll() as $u) { $usersMap[$u['id']] = $u; }
        $requesterUser = $usersMap[$userId]        ?? ['id'=>$userId,      'name'=>'?', 'email'=>''];
        $targetUser    = $usersMap[$targetUserId]  ?? ['id'=>$targetUserId, 'name'=>'?', 'email'=>''];
        $myShiftDate     = $this->db->prepare("SELECT shift_date FROM shifts WHERE id = :id")->execute([':id' => $myShiftId]);
        $requesterDate = $this->db->query("SELECT shift_date FROM shifts WHERE id = $myShiftId")->fetchColumn();
        $targetDate    = $this->db->query("SELECT shift_date FROM shifts WHERE id = $targetShiftId")->fetchColumn();
        MailService::notifySwapNew($targetUser, $requesterUser, $requesterDate, $targetDate);
        $_SESSION['success'] = 'Csere kérelem sikeresen elküldve!';
        header('Location: /swap'); exit;
    }

    public function accept(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /swap'); exit;
        }
        if (!User::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Érvénytelen kérés.';
            header('Location: /swap'); exit;
        }

        $userId = (int)$_SESSION['user']['id'];
        $id     = (int)($_POST['id'] ?? 0);

        if (!$id) {
            $_SESSION['error'] = 'Érvénytelen azonosító.';
            header('Location: /swap'); exit;
        }

        // Csak a célpont fogadhatja el, és csak pending státuszban
        $stmt = $this->db->prepare(
            "SELECT * FROM swap_requests WHERE id = :id AND target_id = :uid AND status = 'pending' LIMIT 1"
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $swap = $stmt->fetch();

        if (!$swap) {
            $_SESSION['error'] = 'A kérelem nem található vagy már le van zárva.';
            header('Location: /swap'); exit;
        }

        $this->db->prepare(
            "UPDATE swap_requests SET status = 'accepted' WHERE id = :id"
        )->execute([':id' => $id]);

        $_SESSION['success'] = 'Elfogadtad a csere kérelmet! Az admin jóváhagyására vár.';
        header('Location: /swap'); exit;
    }

    public function decline(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /swap'); exit;
        }
        if (!User::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Érvénytelen kérés.';
            header('Location: /swap'); exit;
        }

        $userId = (int)$_SESSION['user']['id'];
        $id     = (int)($_POST['id'] ?? 0);

        if (!$id) {
            $_SESSION['error'] = 'Érvénytelen azonosító.';
            header('Location: /swap'); exit;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM swap_requests WHERE id = :id AND target_id = :uid AND status = 'pending' LIMIT 1"
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $swap = $stmt->fetch();

        if (!$swap) {
            $_SESSION['error'] = 'A kérelem nem található vagy már le van zárva.';
            header('Location: /swap'); exit;
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "UPDATE swap_requests SET status = 'rejected' WHERE id = :id"

            )->execute([':id' => $id]);

            // Kérelmező műszakját visszaállítjuk active-ra
            $this->db->prepare(
                "UPDATE shifts SET status = 'active' WHERE id = :id"
            )->execute([':id' => (int)$swap['requester_shift_id']]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
        }

        
        // E-mail értesítés a kérelmezőnek (elutasítás)
        $reqUserStmt = $this->db->prepare('SELECT id, name, email FROM users WHERE id = :id');
        $reqUserStmt->execute([':id' => (int)$swap['requester_id']]);
        $requesterData = $reqUserStmt->fetch();
        $reqDate = $this->db->query('SELECT shift_date FROM shifts WHERE id = ' . (int)$swap['requester_shift_id'])->fetchColumn();
        $tgtDate = $this->db->query('SELECT shift_date FROM shifts WHERE id = ' . (int)$swap['target_shift_id'])->fetchColumn();
        if ($requesterData) MailService::notifySwapReviewed($requesterData, 'rejected', $reqDate, $tgtDate);
        $_SESSION['success'] = 'Elutasítottad a csere kérelmet.';
        header('Location: /swap'); exit;
    }
}
