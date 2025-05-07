<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';
$conn = new mysqli('localhost', 'root', '', 'github_timeline');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function generateVerificationCode(): string {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function ensureDirectoryExists(string $path): void {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function sendVerificationEmail(string $email, string $code, bool $isUnsubscribe = false): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'umangjajal@gmail.com';
        $mail->Password   = 'gtnf modx ktzt pngv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@example.com', 'GitHub Timeline');
        $mail->addAddress($email);

        $subject = $isUnsubscribe ? "Confirm Unsubscription from GitHub Timeline" : "Confirm Your GitHub Timeline Subscription";
        $action = $isUnsubscribe ? "unsubscribe" : "subscribe";

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "
            <html>
            <body>
                <h2>Please confirm your request to $action</h2>
                <p>Your verification code is:</p>
                <h3 style='color:blue;'>$code</h3>
                <p>Enter this code on the website to continue.</p>
            </body>
            </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function registerEmail(string $email): bool {
    $file = __DIR__ . '/registered_emails.txt';
    $emails = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

    if (!in_array($email, $emails)) {
        $emails[] = $email;
        return file_put_contents($file, implode(PHP_EOL, $emails) . PHP_EOL) !== false;
    }
    return false;
}

function unsubscribeEmail(string $email): bool {
    $file = __DIR__ . '/registered_emails.txt';
    if (!file_exists($file)) return false;

    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $filtered = array_filter($emails, fn($e) => trim($e) !== trim($email));

    return file_put_contents($file, implode(PHP_EOL, $filtered) . PHP_EOL) !== false;
}

function verifyCode(string $email, string $code, bool $isUnsubscribe = false): bool {
    $prefix = $isUnsubscribe ? 'unsub_' : '';
    $codeFile = __DIR__ . "/codes/{$prefix}{$email}.txt";

    if (file_exists($codeFile)) {
        $savedCode = trim(file_get_contents($codeFile));
        if ($savedCode === $code) {
            unlink($codeFile);
            return true;
        }
    }
    return false;
}

function fetchGitHubTimeline(): array {
    $username = 'umangjajal';
    $url = "https://github.com/umangjajal";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'GitHubTimelineApp'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true) ?? [];
}

function formatGitHubData(array $data): string {
    $html = "<h2>Latest GitHub Activity</h2><ul style='font-family:sans-serif;'>";

    foreach (array_slice($data, 0, 5) as $event) {
        $type = $event['type'] ?? 'Event';
        $repo = $event['repo']['name'] ?? 'Unknown Repo';
        $time = isset($event['created_at']) ? date('d M Y, H:i', strtotime($event['created_at'])) : 'Unknown Time';
        $html .= "<li><strong>$type</strong> on <code>$repo</code> at <em>$time</em></li>";
    }

    $html .= "</ul><p style='font-size:12px;color:gray;'>GitHub Timeline Bot</p>";
    return $html;
}

function sendGitHubUpdatesToSubscribers(): void {
    $file = __DIR__ . '/registered_emails.txt';
    if (!file_exists($file)) return;

    $subscribers = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($subscribers)) return;

    $events = fetchGitHubTimeline();
    if (empty($events)) return;

    $body = formatGitHubData($events);
    $subject = "Your GitHub Timeline Updates";
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: GitHub Timeline <no-reply@example.com>\r\n";

    foreach ($subscribers as $email) {
        if (!mail($email, $subject, $body, $headers)) {
            ensureDirectoryExists(__DIR__ . '/logs');
            file_put_contents(__DIR__ . "/logs/mail_errors.txt", "Failed to send update to $email\n", FILE_APPEND);
        }
    }
}
