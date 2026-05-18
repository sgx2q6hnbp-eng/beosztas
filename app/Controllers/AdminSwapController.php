<?php
declare(strict_types=1);

class AdminSwapController
{
    private PDO $db;

    public function __construct()
    {
        AuthService::requireLogin();
        AuthService::requireAdmin();
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        // Fuggő kérelmek (pending + accepted = dolgozo elfogadta, admin jóváhagyásra var)
        $pending = $this->db->query(
            "SELECT sr.*,
                    ru.name  AS requester_name,
                    tu.name  AS target_name,
                    rs.shift_date AS req_date,
                    rs.start_time AS req_start,
                    rs.end_time   AS req_end,
                    ts.shift_date AS tgt_date,
                    ts.start_time AS tgt_start,
                    ts.end_time   AS tgt_end,
                    rf.name AS req_fleet,
                    rf.color AS req_color,
                    tf.name AS tgt_fleet,
                    tf.color AS tgt_color
             FROM swap_requests sr
             LEFT JOIN users  ru ON ru.id = sr.requester_id
             LEFT JOIN users  tu ON tu.id = sr.target_id
             LEFT JOIN shifts rs ON rs.id = sr.requester_shift_id
             LEFT JOIN shifts ts ON ts.id = sr.target_shift_id
             LEFT JOIN fleets rf ON rf.id = rs.fleet_id
             LEFT JOIN fleets tf ON tf.id = ts.fleet_id
             WHERE sr.status IN ('pending','accepted')
             ORDER BY sr.created_at DESC"
        )->fetchAll();

        // Lezárt kérelmek (utolso 50)
        $closed = $this->db->query(
            "SELECT sr.*,
                    ru.name  AS requester_name,
                    tu.name  AS target_name,
                    au.name  AS reviewed_by_name,
                    rs.shift_date AS req_date,
                    ts.shift_date AS tgt_date
             FROM swap_requests sr
             LEFT JOIN users ru ON ru.id = sr.requester_id
             LEFT JOIN users tu ON tu.id = sr.target_id
             LEFT JOIN users au ON au.id = sr.reviewed_by
             LEFT JOIN shifts rs ON rs.id = sr.requester_shift_id
             LEFT JOIN shifts ts ON ts.id = sr.target_shift_id
             WHERE sr.status IN ('approved','rejected','cancelled')
             ORDER BY sr.reviewed_at DESC
             LIMIT 50"
        )->fetchAll();

        require BASE_PATH . '/app/Views/admin/swaps.php';
    }

    public function approve(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/swaps'); exit;
        }
        if (!User::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Ervenytelen keres.';
            header('Location: /admin/swaps'); exit;
        }

        $id      = (int)($_POST['id'] ?? 0);
        $adminId = (int)$_SESSION['user']['id'];

        if (!$id) {
            $_SESSION['error'] = 'Ervenytelen azonosito.';
            header('Location: /admin/swaps'); exit;
        }

        // Kérelem lekérése
        $sr = $this->db->prepare(
            "SELECT * FROM swap_requests WHERE id = :id AND status IN ('pending','accepted') LIMIT 1"
        );
        $sr->execute([':id' => $id]);
        $swap = $sr->fetch();

        if (!$swap) {
            $_SESSION['error'] = 'A kérelem nem talalhato vagy mar le van zarva.';
            header('Location: /admin/swaps'); exit;
        }

        $this->db->beginTransaction();
        try {
            // Státusz: approved
            $this->db->prepare(
                "UPDATE swap_requests SET status = 'approved', reviewed_by = :adm, reviewed_at = NOW() WHERE id = :id"
            )->execute([':adm' => $adminId, ':id' => $id]);

            // Műszakok user_id cseréje
            $reqUserId = (int)$swap['requester_id'];
            $tgtUserId = (int)$swap['target_id'];
            $reqShiftId = (int)$swap['requester_shift_id'];
            $tgtShiftId = (int)$swap['target_shift_id'];

            $this->db->prepare("UPDATE shifts SET user_id = :uid, status = 'active' WHERE id = :id")
                ->execute([':uid' => $tgtUserId, ':id' => $reqShiftId]);

            $this->db->prepare("UPDATE shifts SET user_id = :uid, status = 'active' WHERE id = :id")
                ->execute([':uid' => $reqUserId, ':id' => $tgtShiftId]);

            // Tobbi fuggő kérelem törlése ezekre a műszakokra
            $this->db->prepare(
                "UPDATE swap_requests SET status = 'cancelled'
                 WHERE id != :id
                   AND status = 'pending'
                   AND (requester_shift_id IN (:rs,:ts) OR target_shift_id IN (:rs2,:ts2))"
            )->execute([':id' => $id, ':rs' => $reqShiftId, ':ts' => $tgtShiftId, ':rs2' => $reqShiftId, ':ts2' => $tgtShiftId]);

            $this->db->commit();

            // E-mail értesítés mindkét félnek (jóváhagyás)
            $usersQ = $this->db->prepare('SELECT id, name, email FROM users WHERE id IN (:req, :tgt)');
            $usersQ->execute([':req' => $reqUserId, ':tgt' => $tgtUserId]);
            $usersMap = [];
            foreach ($usersQ->fetchAll() as $u) { $usersMap[$u['id']] = $u; }
            $reqUser2 = $usersMap[$reqUserId] ?? ['id'=>$reqUserId,'name'=>'?','email'=>''];
            $reqDate2 = $this->db->query('SELECT shift_date FROM shifts WHERE id = ' . $reqShiftId)->fetchColumn();
            $tgtDate2 = $this->db->query('SELECT shift_date FROM shifts WHERE id = ' . $tgtShiftId)->fetchColumn();
            MailService::notifySwapReviewed($reqUser2, 'approved', $reqDate2, $tgtDate2);
            $_SESSION['success'] = 'Csere jovahagyva! A muszakok felcserelve.';
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $_SESSION['error'] = 'Hiba tortent: ' . $e->getMessage();
        }

        header('Location: /admin/swaps'); exit;
    }

    public function reject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/swaps'); exit;
        }
        if (!User::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Ervenytelen keres.';
            header('Location: /admin/swaps'); exit;
        }

        $id      = (int)($_POST['id'] ?? 0);
        $adminId = (int)$_SESSION['user']['id'];

        if (!$id) {
            $_SESSION['error'] = 'Ervenytelen azonosito.';
            header('Location: /admin/swaps'); exit;
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "UPDATE swap_requests SET status = 'rejected', reviewed_by = :adm, reviewed_at = NOW() WHERE id = :id"
            )->execute([':adm' => $adminId, ':id' => $id]);

            // Műszakok visszaállítása active státuszra
            $sr = $this->db->prepare("SELECT requester_shift_id FROM swap_requests WHERE id = :id");
            $sr->execute([':id' => $id]);
            $row = $sr->fetch();
            if ($row) {
                $this->db->prepare("UPDATE shifts SET status = 'active' WHERE id = :id")
                    ->execute([':id' => $row['requester_shift_id']]);
            }

            $this->db->commit();

            // E-mail értesítés a kérelmezőnek (elutasítás)
            if (isset($row) && $row) {
                $reqUserStmt = $this->db->prepare('SELECT id, name, email FROM users WHERE id = :id');
                $reqUserStmt->execute([':id' => (int)$swap['requester_id']]);
                $requesterData = $reqUserStmt->fetch();
                $reqDate3 = $this->db->query('SELECT shift_date FROM shifts WHERE id = ' . (int)$swap['requester_shift_id'])->fetchColumn();
                $tgtDate3 = $this->db->query('SELECT shift_date FROM shifts WHERE id = ' . (int)$swap['target_shift_id'])->fetchColumn();
                if ($requesterData) MailService::notifySwapReviewed($requesterData, 'rejected', $reqDate3, $tgtDate3);
            }
            $_SESSION['success'] = 'Csere elutasitva.';
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $_SESSION['error'] = 'Hiba tortent: ' . $e->getMessage();
        }

        header('Location: /admin/swaps'); exit;
    }
}
