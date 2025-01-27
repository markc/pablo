<?php

declare(strict_types=1);

//use PDO;
//use PDOException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

// ========== begin configuration ==========

// Database configuration
$db_type = 'sqlite'; // Use 'sqlite' for SQLite database
$db_host = ''; // Leave empty for SQLite
$db_name = '/path/to/your/database.sqlite'; // Path to SQLite database file
$db_username = ''; // Not needed for SQLite
$db_password = ''; // Not needed for SQLite

$vacation_domain = 'autoreply.example.org';
$recipient_delimiter = '+';

// SMTP configuration
$smtp_server = 'localhost';
$smtp_server_port = 25;
$smtp_client = 'localhost';
$smtp_helo = 'localhost.localdomain';
$smtp_ssl = false; // Use 'ssl', 'starttls', or false
$smtp_timeout = 120;
$smtp_authid = '';
$smtp_authpwd = '';

$friendly_from = '';
$accountname_check = false;
$account_name = '';

$syslog = true;
$logfile = '/var/log/vacation.log';
$log_level = 2; // 2 = debug + info, 1 = info only, 0 = error only
$log_to_file = false;

$interval = 0; // Notification interval in seconds

$custom_noreply_pattern = false;
$noreply_pattern = 'bounce|do-not-reply|facebook|linkedin|list-|myspace|twitter';
$no_vacation_pattern = 'info@example.org';

$replace_from = "<%From_Date>";
$replace_until = "<%Until_Date>";
$date_format = 'Y-m-d';

// =========== end configuration ===========

// Initialize logging
if ($syslog && !$log_to_file) {
    openlog('vacation', LOG_PID | LOG_PERROR, LOG_MAIL);
}

function log_message(string $message, string $level = 'info'): void {
    global $logfile, $log_to_file, $syslog;

    if ($log_to_file && $logfile) {
        file_put_contents($logfile, date('Y-m-d H:i:s') . " [$level] $message\n", FILE_APPEND);
    }

    if ($syslog) {
        syslog($level === 'error' ? LOG_ERR : LOG_INFO, $message);
    }
}

// Database connection
try {
    $dsn = "sqlite:$db_name";
    $dbh = new PDO($dsn, $db_username, $db_password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    log_message("Could not connect to database: " . $e->getMessage(), 'error');
    exit(1);
}

// Function to check if the user is on vacation
function check_for_vacation(string $email): bool {
    global $dbh;

    $stmt = $dbh->prepare("SELECT email FROM vacation WHERE email = ? AND active = 1 AND activefrom <= datetime('now') AND activeuntil >= datetime('now')");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

// Function to send vacation email
function send_vacation_email(string $email, string $orig_from, string $orig_to, string $orig_messageid, string $orig_subject, bool $test_mode = false): void {
    global $dbh, $smtp_server, $smtp_server_port, $smtp_client, $smtp_helo, $smtp_ssl, $smtp_timeout, $smtp_authid, $smtp_authpwd, $friendly_from, $accountname_check, $account_name;

    $stmt = $dbh->prepare("SELECT subject, body FROM vacation WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $subject = $row['subject'];
        $body = $row['body'];

        // Replace placeholders in the body
        $body = replace_string($email, $body);

        $from = $email;
        $to = $orig_from;

        $transport = Transport::fromDsn("smtp://$smtp_server:$smtp_server_port");
        $mailer = new Mailer($transport);

        $email = (new Email())
            ->from(new Address($from, $friendly_from))
            ->to($to)
            ->subject($subject)
            ->text($body)
            ->header('Precedence', 'junk')
            ->header('X-Loop', 'Postfix Admin Virtual Vacation')
            ->header('Auto-Submitted', 'auto-replied');

        if ($test_mode) {
            log_message("** TEST MODE ** : Vacation response sent to $to from $from subject $subject (not) sent", 'info');
        } else {
            try {
                $mailer->send($email);
                log_message("Vacation response sent to $to from $from subject $subject", 'info');
            } catch (Exception $e) {
                log_message("Failed to send vacation response to $to from $from subject $subject: " . $e->getMessage(), 'error');
            }
        }
    }
}

// Function to replace placeholders in the body
function replace_string(string $email, string $body): string {
    global $dbh, $replace_from, $replace_until, $date_format;

    $stmt = $dbh->prepare("SELECT DATE(activefrom) as activefrom, DATE(activeuntil) as activeuntil FROM vacation WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $activefrom = date($date_format, strtotime($row['activefrom']));
        $activeuntil = date($date_format, strtotime($row['activeuntil']));

        $body = str_replace($replace_from, $activefrom, $body);
        $body = str_replace($replace_until, $activeuntil, $body);
    }

    return $body;
}

// Main script logic
$smtp_sender = $_SERVER['argv'][2] ?? '';
$smtp_recipient = $_SERVER['argv'][3] ?? '';

if (!$smtp_sender || !$smtp_recipient) {
    log_message("Missing sender or recipient arguments", 'error');
    exit(1);
}

if (check_for_vacation($smtp_recipient)) {
    send_vacation_email($smtp_recipient, $smtp_sender, $smtp_recipient, uniqid(), 'Vacation Auto-Reply');
} else {
    log_message("Recipient $smtp_recipient does not have an active vacation", 'debug');
}

0;
