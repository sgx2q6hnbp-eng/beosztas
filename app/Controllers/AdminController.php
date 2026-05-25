<?php
declare(strict_types=1);

class AdminController
{
    private PDO $db;

    public function __construct()
    {
        AuthService::requireLogin();
        AuthService::requireAdmin();
        $this->db = Database::getInstance();
    }

    public function employees(): void
    {
        $stmt = $this->db->query(
            "SELECT u.*, f.name AS fleet_name, f.color
             FROM users u
             LEFT JOIN fleets f ON f.id = u.fleet_id
             ORDER BY f.name, u.name"
        );
        $employees = $stmt->fetchAll();
        $fleets = $this->db->query("SELECT id, name, color FROM fleets ORDER BY id")->fetchAll();
        require BASE_PATH . '/app/Views/admin/employees.php';
    }

    public function storeEmployee(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/employees'); exit;
        }

        if (!User::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Ervenytelen keres.';
            header('Location: /admin/employees'); exit;
        }

        $name           = trim($_POST['name']            ?? '');
        $employeeNumber = trim($_POST['employee_number'] ?? '');
        $email          = trim($_POST['email']           ?? '');
        $phone          = trim($_POST['phone']           ?? '');
        $role           = $_POST['role']                 ?? 'employee';
        $fleetId        = (int)($_POST['fleet_id']       ?? 0);
        $password       = $_POST['password']             ?? '';
        $isActive       = isset($_POST['is_active']) ? 1 : 0;

        $errors = [];

        if (empty($name)) {
            $errors[] = 'A nev megadasa kotelezo.';
        }
        if (empty($employeeNumber)) {
            $errors[] = 'A torzsszam megadasa kotelezo.';
        }
        if (!in_array($role, ['admin', 'employee'], true)) {
            $errors[] = 'Ervenytelen szerepkor.';
        }
        if (strlen($password) < 4) {
            $errors[] = 'A jelszónak legalabb 4 karakter hosszunak kell lennie.';
        }

        $checkNum = $this->db->prepare("SELECT id FROM users WHERE employee_number = :en LIMIT 1");
        $checkNum->execute([':en' => $employeeNumber]);
        if ($checkNum->fetch()) {
            $errors[] = 'Ez a torzsszam mar foglalt.';
        }

        // E-mail ellenorzese csak ha meg van adva
        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Ervenytelen e-mail cim formatum.';
            } else {
                $checkEmail = $this->db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                $checkEmail->execute([':email' => $email]);
                if ($checkEmail->fetch()) {
                    $errors[] = 'Ez az e-mail cim mar foglalt.';
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(' ', $errors);
            header('Location: /admin/employees'); exit;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO users (name, employee_number, email, password, role, fleet_id, phone, is_active)
             VALUES (:name, :employee_number, :email, :password, :role, :fleet_id, :phone, :is_active)"
        );
        $stmt->execute([
            ':name'            => $name,
            ':employee_number' => $employeeNumber,
            ':email'           => $email ?: null,
            ':password'        => password_hash($password, PASSWORD_BCRYPT),
            ':role'            => $role,
            ':fleet_id'        => $fleetId ?: null,
            ':phone'           => $phone ?: null,
            ':is_active'       => $isActive,
        ]);

        $newId = (int)$this->db->lastInsertId();

        $this->db->prepare(
            "INSERT INTO shift_logs (shift_id, changed_by, change_type, new_value)
             VALUES (NULL, :uid, 'create', :val)"
        )->execute([
            ':uid' => $_SESSION['user']['id'],
            ':val' => json_encode([
                'action'          => 'new_employee',
                'id'              => $newId,
                'name'            => $name,
                'employee_number' => $employeeNumber,
            ]),
        ]);

        $_SESSION['success'] = "{$name} ({$employeeNumber}) sikeresen hozzaadva!";
        header('Location: /admin/employees'); exit;
    }

    public function updateEmployee(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/employees'); exit;
        }
        if (!User::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Ervenytelen keres.';
            header('Location: /admin/employees'); exit;
        }

        $id             = (int)($_POST['id'] ?? 0);
        $name           = trim($_POST['name']            ?? '');
        $employeeNumber = trim($_POST['employee_number'] ?? '');
        $email          = trim($_POST['email']           ?? '');
        $phone          = trim($_POST['phone']           ?? '');
        $role           = $_POST['role']                 ?? 'employee';
        $fleetId        = (int)($_POST['fleet_id']       ?? 0);
        $isActive       = isset($_POST['is_active']) ? 1 : 0;
        $password       = $_POST['password']             ?? '';

        if (!$id || empty($name) || empty($employeeNumber)) {
            $_SESSION['error'] = 'Minden kotelezo mezot ki kell tolteni.';
            header('Location: /admin/employees'); exit;
        }

        if (!in_array($role, ['admin', 'employee'], true)) {
            $_SESSION['error'] = 'Ervenytelen szerepkor.';
            header('Location: /admin/employees'); exit;
        }

        $checkNum = $this->db->prepare("SELECT id FROM users WHERE employee_number = :en AND id != :id LIMIT 1");
        $checkNum->execute([':en' => $employeeNumber, ':id' => $id]);
        if ($checkNum->fetch()) {
            $_SESSION['error'] = 'Ez a torzsszam mar foglalt.';
            header('Location: /admin/employees'); exit;
        }

        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = 'Ervenytelen e-mail cim formatum.';
                header('Location: /admin/employees'); exit;
            }
            $checkEmail = $this->db->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
            $checkEmail->execute([':email' => $email, ':id' => $id]);
            if ($checkEmail->fetch()) {
                $_SESSION['error'] = 'Ez az e-mail cim mar foglalt.';
                header('Location: /admin/employees'); exit;
            }
        }

        $stmt = $this->db->prepare(
            "UPDATE users SET name = :name, employee_number = :en, email = :email,
             role = :role, fleet_id = :fleet, phone = :phone, is_active = :active
             WHERE id = :id"
        );
        $stmt->execute([
            ':name'   => $name,
            ':en'     => $employeeNumber,
            ':email'  => $email ?: null,
            ':role'   => $role,
            ':fleet'  => $fleetId ?: null,
            ':phone'  => $phone ?: null,
            ':active' => $isActive,
            ':id'     => $id,
        ]);

        if (strlen($password) >= 4) {
            $this->db->prepare("UPDATE users SET password = :pw WHERE id = :id")
                ->execute([':pw' => password_hash($password, PASSWORD_BCRYPT), ':id' => $id]);
        }

        $_SESSION['success'] = "{$name} adatai frissitve!";
        header('Location: /admin/employees'); exit;
    }

    public function deleteEmployee(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/employees'); exit;
        }
        if (!User::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Ervenytelen keres.';
            header('Location: /admin/employees'); exit;
        }

        $id      = (int)($_POST['id'] ?? 0);
        $adminId = (int)$_SESSION['user']['id'];

        if (!$id) {
            $_SESSION['error'] = 'Ervenytelen azonosito.';
            header('Location: /admin/employees'); exit;
        }

        if ($id === $adminId) {
            $_SESSION['error'] = 'Sajat magadat nem torölheted.';
            header('Location: /admin/employees'); exit;
        }

        $stmt = $this->db->prepare("UPDATE users SET is_active = 0 WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $_SESSION['success'] = 'Dolgozo inaktiválva.';
        header('Location: /admin/employees'); exit;
    }

    public function leaves(): void
    {
        $filter = $_GET['status'] ?? 'pending';
        if (!in_array($filter, ['pending','approved','rejected','all'], true)) $filter = 'pending';

        if ($filter === 'all') {
            $stmt = $this->db->query(
                "SELECT lr.*, u.name AS employee_name, u.fleet_id,
                        f.name AS fleet_name, r.name AS reviewer_name
                 FROM leave_requests lr
                 JOIN users u ON u.id = lr.user_id
                 LEFT JOIN fleets f ON f.id = u.fleet_id
                 LEFT JOIN users r ON r.id = lr.reviewed_by
                 ORDER BY lr.created_at DESC"
            );
        } else {
            $stmt = $this->db->prepare(
                "SELECT lr.*, u.name AS employee_name, u.fleet_id,
                        f.name AS fleet_name, r.name AS reviewer_name
                 FROM leave_requests lr
                 JOIN users u ON u.id = lr.user_id
                 LEFT JOIN fleets f ON f.id = u.fleet_id
                 LEFT JOIN users r ON r.id = lr.reviewed_by
                 WHERE lr.status = :status
                 ORDER BY lr.created_at DESC"
            );
            $stmt->execute([':status' => $filter]);
        }
        $leaves = $stmt->fetchAll();

        $employees = $this->db->query(
            "SELECT id, name FROM users WHERE role = 'employee' AND is_active = 1 ORDER BY name ASC"
        )->fetchAll();

        require BASE_PATH . '/app/Views/admin/leaves.php';
    }

    public function reviewLeave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/leaves'); exit;
        }

        if (!User::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Ervenytelen keres.';
            header('Location: /admin/leaves'); exit;
        }

        $id      = (int)($_POST['leave_id'] ?? 0);
        $action  = $_POST['action'] ?? '';
        $note    = trim($_POST['admin_note'] ?? '');
        $adminId = $_SESSION['user']['id'];

        if (!$id || !in_array($action, ['approved','rejected'], true)) {
            $_SESSION['error'] = 'Ervenytelen muvelet.';
            header('Location: /admin/leaves'); exit;
        }

        $check = $this->db->prepare(
            'SELECT lr.id, lr.user_id, lr.start_date, lr.end_date, lr.leave_type,
                    u.email AS employee_email, u.name AS employee_name, u.id AS uid
             FROM leave_requests lr
             JOIN users u ON u.id = lr.user_id
             WHERE lr.id = :id AND lr.status = :st'
        );
        $check->execute([':id' => $id, ':st' => 'pending']);
        $leave = $check->fetch();

        if (!$leave) {
            $_SESSION['error'] = 'A kerelem nem talalhato vagy mar felulvizsgalva lett.';
            header('Location: /admin/leaves'); exit;
        }

        $stmt = $this->db->prepare(
            'UPDATE leave_requests
            SET status = :status, reviewed_by = :admin, reviewed_at = NOW(), admin_note = :note
            WHERE id = :id'
        );
        $stmt->execute([
            ':status' => $action,
            ':admin'  => $adminId,
            ':note'   => $note ?: null,
            ':id'     => $id,
        ]);

        if ($action === 'approved') {
            $shiftStatus = $leave['leave_type'] === 'sick' ? 'sick' : 'vacation';
            $this->db->prepare(
                'UPDATE shifts SET status = :st
                 WHERE user_id = :uid
                   AND shift_date BETWEEN :start AND :end'
            )->execute([
                ':st'    => $shiftStatus,
                ':uid'   => $leave['user_id'],
                ':start' => $leave['start_date'],
                ':end'   => $leave['end_date'],
            ]);
        }

        MailService::notifyEmployeeLeaveReviewed(
            ['id' => $leave['uid'], 'email' => $leave['employee_email'], 'name' => $leave['employee_name']],
            $leave,
            $action,
            $note
        );
        $filter = $_POST['filter'] ?? 'pending';
        header('Location: /admin/leaves?status=' . $filter); exit;
    }

    public function deleteLeave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/leaves'); exit;
        }

        if (!User::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Érvénytelen kérés.';
            header('Location: /admin/leaves'); exit;
        }

        $id     = (int)($_POST['leave_id'] ?? 0);
        $filter = $_POST['filter'] ?? 'approved';

        if (!$id) {
            $_SESSION['error'] = 'Érvénytelen azonosító.';
            header('Location: /admin/leaves?status=' . $filter); exit;
        }

        // Csak elbírált (approved/rejected) kérelem törölhető
        $check = $this->db->prepare(
            'SELECT id, user_id, leave_type, start_date, end_date, status
             FROM leave_requests
             WHERE id = :id AND status IN ("approved", "rejected")'
        );
        $check->execute([':id' => $id]);
        $leave = $check->fetch();

        if (!$leave) {
            $_SESSION['error'] = 'A kérelem nem található, vagy függő kérelem nem törölhető így.';
            header('Location: /admin/leaves?status=' . $filter); exit;
        }

        $this->db->beginTransaction();
        try {
            // Ha jóváhagyott volt, a műszakok státusza visszaáll aktívra
            if ($leave['status'] === 'approved') {
                $this->db->prepare(
                    'UPDATE shifts SET status = \'active\'
                     WHERE user_id = :uid
                       AND shift_date BETWEEN :start AND :end
                       AND status IN (\'vacation\', \'sick\')'
                )->execute([
                    ':uid'   => $leave['user_id'],
                    ':start' => $leave['start_date'],
                    ':end'   => $leave['end_date'],
                ]);
            }

            $this->db->prepare('DELETE FROM leave_requests WHERE id = :id')
                ->execute([':id' => $id]);

            $this->db->commit();
            $_SESSION['success'] = 'A szabadságkérelem sikeresen törölve.';
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $_SESSION['error'] = 'Hiba történt: ' . $e->getMessage();
        }

        header('Location: /admin/leaves?status=' . $filter); exit;
    }

    public function logs(): void
    {
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 50;
        $offset = ($page - 1) * $limit;

        $stmt = $this->db->prepare(
            "SELECT sl.*, u.name AS changed_by_name
             FROM shift_logs sl
             LEFT JOIN users u ON u.id = sl.changed_by
             ORDER BY sl.changed_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();

        $total      = (int)$this->db->query("SELECT COUNT(*) FROM shift_logs")->fetchColumn();
        $totalPages = (int)ceil($total / $limit);

        require BASE_PATH . '/app/Views/admin/logs.php';
    }

    public function adminStoreLeave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/leaves'); exit;
        }
        if (!User::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Érvénytelen kérés.';
            header('Location: /admin/leaves'); exit;
        }

        $adminId   = (int)$_SESSION['user']['id'];
        $userId    = (int)($_POST['user_id']    ?? 0);
        $leaveType = $_POST['leave_type'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate   = $_POST['end_date']   ?? '';
        $reason    = trim($_POST['reason'] ?? '');

        $allowed = ['vacation','sick','unpaid','other'];
        if (!$userId || !in_array($leaveType, $allowed, true)) {
            $_SESSION['error'] = 'Hiányzó vagy érvénytelen adat.';
            header('Location: /admin/leaves'); exit;
        }
        if (!$startDate || !$endDate || $endDate < $startDate) {
            $_SESSION['error'] = 'Érvénytelen dátum intervallum.';
            header('Location: /admin/leaves'); exit;
        }

        $overlap = $this->db->prepare(
            "SELECT COUNT(*) FROM leave_requests
             WHERE user_id = :uid
               AND status != 'rejected'
               AND start_date <= :end
               AND end_date   >= :start"
        );
        $overlap->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
        if ((int)$overlap->fetchColumn() > 0) {
            $_SESSION['error'] = 'Erre az időszakra már van rögzített kérelem a dolgozónál.';
            header('Location: /admin/leaves'); exit;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO leave_requests
                    (user_id, leave_type, start_date, end_date, reason, status, reviewed_by, reviewed_at)
                 VALUES
                    (:uid, :type, :start, :end, :reason, 'approved', :adm, NOW())"
            );
            $stmt->execute([
                ':uid'    => $userId,
                ':type'   => $leaveType,
                ':start'  => $startDate,
                ':end'    => $endDate,
                ':reason' => $reason ?: null,
                ':adm'    => $adminId,
            ]);

            $shiftStatus = $leaveType === 'sick' ? 'sick' : 'vacation';
            $this->db->prepare(
                "UPDATE shifts SET status = :st
                 WHERE user_id = :uid
                   AND shift_date BETWEEN :start AND :end
                   AND status = 'active'"
            )->execute([
                ':st'    => $shiftStatus,
                ':uid'   => $userId,
                ':start' => $startDate,
                ':end'   => $endDate,
            ]);

            $this->db->commit();
            $_SESSION['success'] = 'Távollét sikeresen rögzítve!';
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $_SESSION['error'] = 'Hiba történt: ' . $e->getMessage();
        }

        header('Location: /admin/leaves?status=approved'); exit;
    }

    public function settings(): void
    {
        require_once BASE_PATH . '/app/Services/SettingsService.php';
        $settings = SettingsService::all();
        require BASE_PATH . '/app/Views/admin/settings.php';
    }

    public function saveSettings(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/settings'); exit;
        }
        require_once BASE_PATH . '/app/Services/SettingsService.php';

        $keys = [
            'mail_enabled',
            'mail_leave_new_admin',
            'mail_leave_reviewed_employee',
            'mail_swap_new',
            'mail_swap_reviewed',
        ];
        foreach ($keys as $key) {
            SettingsService::set($key, isset($_POST[$key]) ? '1' : '0');
        }

        $_SESSION['success'] = 'Beállítások mentve.';
        header('Location: /admin/settings'); exit;
    }

}
