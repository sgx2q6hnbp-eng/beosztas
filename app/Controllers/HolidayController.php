<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/Services/Database.php';
require_once BASE_PATH . '/app/Services/AuthService.php';

class HolidayController
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
        $year = (int)($_GET['year'] ?? date('Y'));
        $year = max(2020, min(2100, $year));

        $stmt = $this->db->prepare(
            "SELECT * FROM holidays WHERE YEAR(holiday_date) = :year ORDER BY holiday_date ASC"
        );
        $stmt->execute([':year' => $year]);
        $holidays = $stmt->fetchAll();

        require BASE_PATH . '/app/Views/admin/holidays.php';
    }

    public function store(): void
    {
        header('Content-Type: application/json');

        $date = trim($_POST['holiday_date'] ?? '');
        $name = trim($_POST['name']         ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || empty($name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Hiányzó vagy hibás adatok.']);
            return;
        }

        $chk = $this->db->prepare("SELECT id FROM holidays WHERE holiday_date = :d");
        $chk->execute([':d' => $date]);
        if ($chk->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Ez a nap már rögzítve van.']);
            return;
        }

        $this->db->prepare("INSERT INTO holidays (holiday_date, name) VALUES (:d, :n)")
                 ->execute([':d' => $date, ':n' => $name]);

        $newId = (int)$this->db->lastInsertId();
        echo json_encode(['success' => true, 'id' => $newId, 'date' => $date, 'name' => $name]);
    }

    public function delete(): void
    {
        header('Content-Type: application/json');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Érvénytelen azonosító.']);
            return;
        }

        $this->db->prepare("DELETE FROM holidays WHERE id = :id")->execute([':id' => $id]);
        echo json_encode(['success' => true]);
    }

    public function update(): void
    {
        header('Content-Type: application/json');

        $id   = (int)trim($_POST['id']   ?? 0);
        $name = trim($_POST['name']       ?? '');

        if ($id <= 0 || empty($name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Hiányzó adatok.']);
            return;
        }

        $this->db->prepare("UPDATE holidays SET name = :n WHERE id = :id")
                 ->execute([':n' => $name, ':id' => $id]);

        echo json_encode(['success' => true]);
    }

    /**
     * Publikusan elérhető segédfüggvény más controllereknek.
     * Visszaadja az adott hónap ünnepnapjait tömbként: ['2026-03-15' => 'Nemzeti ünnep']
     */
    public static function getHolidaysForMonth(PDO $db, int $year, int $month): array
    {
        $first = sprintf('%04d-%02d-01', $year, $month);
        $last  = date('Y-m-t', strtotime($first));
        $stmt  = $db->prepare(
            "SELECT holiday_date, name FROM holidays WHERE holiday_date BETWEEN :first AND :last"
        );
        $stmt->execute([':first' => $first, ':last' => $last]);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['holiday_date']] = $row['name'];
        }
        return $result;
    }
}
