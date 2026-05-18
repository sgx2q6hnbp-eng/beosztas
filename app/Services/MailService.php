<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private static function mailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST']     ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME']  ?? '';
        $mail->Password   = $_ENV['MAIL_PASSWORD']  ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($_ENV['MAIL_PORT'] ?? 587);
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(
            $_ENV['MAIL_USERNAME'] ?? 'noreply@example.com',
            $_ENV['MAIL_FROM_NAME'] ?? 'Beosztás Rendszer'
        );
        return $mail;
    }

    /** Aktív felhasználó-e? */
    private static function isActive(int $userId): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT is_active FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        return $row && (int)$row['is_active'] === 1;
    }

    /** Új szabadságkérelem → admin értesítés */
    public static function notifyAdminNewLeave(array $employee, array $leave): void
    {
        if (!SettingsService::mailAllowed('mail_leave_new_admin')) return;

        try {
            $db    = Database::getInstance();
            $admin = $db->query("SELECT id, email, name FROM users WHERE role='admin' AND is_active=1 LIMIT 1")->fetch();
            if (!$admin || empty($admin['email'])) return;

            $typeLabel = self::leaveTypeLabel($leave['leave_type']);

            $mail = self::mailer();
            $mail->addAddress($admin['email'], $admin['name']);
            $mail->Subject = '📋 Új szabadságkérelem – ' . $employee['name'];
            $mail->isHTML(true);
            $mail->Body = self::wrapHtml('Új szabadságkérelem érkezett', "
                <p>Kedves <strong>{$admin['name']}</strong>!</p>
                <p><strong>" . htmlspecialchars($employee['name']) . "</strong> új szabadságkérelmet adott be:</p>
                " . self::leaveTable($typeLabel, $leave) . "
                <a href='" . ($_ENV['APP_URL'] ?? '') . "/admin/leaves'
                   style='display:inline-block;padding:10px 20px;background:#0C4E54;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;'>
                   Kérelem megtekintése →
                </a>
            ");
            $mail->AltBody = $employee['name'] . " új {$typeLabel} kérelmet adott be: {$leave['start_date']} – {$leave['end_date']}";
            $mail->send();
        } catch (Exception $e) {
            error_log('MailService [notifyAdminNewLeave]: ' . $e->getMessage());
        }
    }

    /** Szabadságkérelem elbírálva → dolgozó értesítés */
    public static function notifyEmployeeLeaveReviewed(array $employee, array $leave, string $status, string $adminNote = ''): void
    {
        if (!SettingsService::mailAllowed('mail_leave_reviewed_employee')) return;
        if (empty($employee['email'])) return;
        if (!self::isActive((int)$employee['id'])) return;

        try {
            $typeLabel   = self::leaveTypeLabel($leave['leave_type']);
            $isApproved  = ($status === 'approved');
            $statusLabel = $isApproved ? '✅ Jóváhagyva' : '❌ Elutasítva';
            $statusColor = $isApproved ? '#16a34a' : '#dc2626';
            $subject     = $isApproved ? '✅ Szabadságkérelmed jóváhagyták' : '❌ Szabadságkérelmed elutasították';

            $noteRow = $adminNote
                ? "<tr><td style='padding:8px;background:#f1f5f9;font-weight:600;'>Megjegyzés</td><td style='padding:8px;'>" . htmlspecialchars($adminNote) . "</td></tr>"
                : '';

            $mail = self::mailer();
            $mail->addAddress($employee['email'], $employee['name']);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = self::wrapHtml('Szabadságkérelem elbírálva', "
                <p>Kedves <strong>" . htmlspecialchars($employee['name']) . "</strong>!</p>
                <p>A szabadságkérelmed elbírálásra került:</p>
                <p style='font-size:1.1rem;font-weight:700;color:{$statusColor};'>{$statusLabel}</p>
                " . self::leaveTable($typeLabel, $leave, $noteRow) . "
                <a href='" . ($_ENV['APP_URL'] ?? '') . "/leave'
                   style='display:inline-block;padding:10px 20px;background:#0C4E54;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;'>
                   Kérelmeim megtekintése →
                </a>
            ");
            $mail->AltBody = "A(z) {$typeLabel} kérelmed ({$leave['start_date']} – {$leave['end_date']}) {$statusLabel}.";
            $mail->send();
        } catch (Exception $e) {
            error_log('MailService [notifyEmployeeLeaveReviewed]: ' . $e->getMessage());
        }
    }

    /** Új műszakcsere kérelem → célszemély értesítés */
    public static function notifySwapNew(array $target, array $requester, string $requesterDate, string $targetDate): void
    {
        if (!SettingsService::mailAllowed('mail_swap_new')) return;
        if (empty($target['email'])) return;
        if (!self::isActive((int)$target['id'])) return;

        try {
            $mail = self::mailer();
            $mail->addAddress($target['email'], $target['name']);
            $mail->Subject = '🔄 Műszakcsere kérelem – ' . $requester['name'];
            $mail->isHTML(true);
            $mail->Body = self::wrapHtml('Új műszakcsere kérelem', "
                <p>Kedves <strong>" . htmlspecialchars($target['name']) . "</strong>!</p>
                <p><strong>" . htmlspecialchars($requester['name']) . "</strong> műszakcserét kezdeményezett veled:</p>
                <table style='border-collapse:collapse;width:100%;margin:16px 0;'>
                    <tr><td style='padding:8px;background:#f1f5f9;font-weight:600;width:160px;'>Az ő műszakja</td><td style='padding:8px;border-bottom:1px solid #e2e8f0;'>{$requesterDate}</td></tr>
                    <tr><td style='padding:8px;background:#f1f5f9;font-weight:600;'>A te műszakod</td><td style='padding:8px;'>{$targetDate}</td></tr>
                </table>
                <a href='" . ($_ENV['APP_URL'] ?? '') . "/swap'
                   style='display:inline-block;padding:10px 20px;background:#0C4E54;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;'>
                   Kérelem megtekintése →
                </a>
            ");
            $mail->AltBody = $requester['name'] . " műszakcserét kér: {$requesterDate} ↔ {$targetDate}";
            $mail->send();
        } catch (Exception $e) {
            error_log('MailService [notifySwapNew]: ' . $e->getMessage());
        }
    }

    /** Műszakcsere elbírálva → kérelmező értesítés */
    public static function notifySwapReviewed(array $requester, string $status, string $requesterDate, string $targetDate): void
    {
        if (!SettingsService::mailAllowed('mail_swap_reviewed')) return;
        if (empty($requester['email'])) return;
        if (!self::isActive((int)$requester['id'])) return;

        try {
            $isApproved  = ($status === 'approved');
            $statusLabel = $isApproved ? '✅ Jóváhagyva' : '❌ Elutasítva';
            $statusColor = $isApproved ? '#16a34a' : '#dc2626';

            $mail = self::mailer();
            $mail->addAddress($requester['email'], $requester['name']);
            $mail->Subject = $isApproved ? '✅ Műszakcsere jóváhagyva' : '❌ Műszakcsere elutasítva';
            $mail->isHTML(true);
            $mail->Body = self::wrapHtml('Műszakcsere elbírálva', "
                <p>Kedves <strong>" . htmlspecialchars($requester['name']) . "</strong>!</p>
                <p>A műszakcsere kérelmed elbírálásra került:</p>
                <p style='font-size:1.1rem;font-weight:700;color:{$statusColor};'>{$statusLabel}</p>
                <table style='border-collapse:collapse;width:100%;margin:16px 0;'>
                    <tr><td style='padding:8px;background:#f1f5f9;font-weight:600;width:160px;'>A te műszakod</td><td style='padding:8px;border-bottom:1px solid #e2e8f0;'>{$requesterDate}</td></tr>
                    <tr><td style='padding:8px;background:#f1f5f9;font-weight:600;'>Csere műszak</td><td style='padding:8px;'>{$targetDate}</td></tr>
                </table>
                <a href='" . ($_ENV['APP_URL'] ?? '') . "/swap'
                   style='display:inline-block;padding:10px 20px;background:#0C4E54;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;'>
                   Cserék megtekintése →
                </a>
            ");
            $mail->AltBody = "Műszakcsere kérelmed ({$requesterDate} ↔ {$targetDate}) {$statusLabel}.";
            $mail->send();
        } catch (Exception $e) {
            error_log('MailService [notifySwapReviewed]: ' . $e->getMessage());
        }
    }

    private static function leaveTypeLabel(string $type): string
    {
        return ['vacation'=>'Szabadság','sick'=>'Táppénz','unpaid'=>'Fizetés nélküli','other'=>'Egyéb'][$type] ?? $type;
    }

    private static function leaveTable(string $typeLabel, array $leave, string $extraRow = ''): string
    {
        return "<table style='border-collapse:collapse;width:100%;margin:16px 0;'>
            <tr><td style='padding:8px;background:#f1f5f9;font-weight:600;width:140px;'>Típus</td><td style='padding:8px;border-bottom:1px solid #e2e8f0;'>{$typeLabel}</td></tr>
            <tr><td style='padding:8px;background:#f1f5f9;font-weight:600;'>Kezdés</td><td style='padding:8px;border-bottom:1px solid #e2e8f0;'>{$leave['start_date']}</td></tr>
            <tr><td style='padding:8px;background:#f1f5f9;font-weight:600;'>Befejezés</td><td style='padding:8px;border-bottom:1px solid #e2e8f0;'>{$leave['end_date']}</td></tr>
            {$extraRow}
        </table>";
    }

    private static function wrapHtml(string $title, string $body): string
    {
        $appName = $_ENV['APP_NAME'] ?? 'Beosztás Rendszer';
        return "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
        <body style='font-family:Arial,sans-serif;background:#f8fafc;margin:0;padding:0;'>
        <div style='max-width:560px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);'>
            <div style='background:#0C4E54;padding:24px 32px;'>
                <h1 style='color:#fff;margin:0;font-size:1.2rem;'>{$appName}</h1>
                <p style='color:rgba(255,255,255,.7);margin:4px 0 0;font-size:.85rem;'>{$title}</p>
            </div>
            <div style='padding:32px;'>{$body}</div>
            <div style='padding:16px 32px;background:#f1f5f9;font-size:.75rem;color:#94a3b8;text-align:center;'>
                Ez egy automatikus értesítő e-mail. Kérjük ne válaszolj erre az üzenetre.
            </div>
        </div></body></html>";
    }
}
