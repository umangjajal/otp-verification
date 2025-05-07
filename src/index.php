<?php
require_once __DIR__ . '/functions.php';

$message = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && !isset($_POST['verification_code'])) {
        $email = trim($_POST['email']);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $code = generateVerificationCode();
            $codePath = __DIR__ . "/codes";
            if (!is_dir($codePath)) {
                mkdir($codePath, 0755, true);
            }
            file_put_contents($codePath . "/{$email}.txt", $code);
            if (sendVerificationEmail($email, $code)) {
                $message = "Verification code sent to <strong>$email</strong>";
            } else {
                $message = "Failed to send email. Check SMTP settings.";
            }
        } else {
            $message = "Invalid email address.";
        }
    } elseif (isset($_POST['verification_code']) && isset($_POST['email'])) {
        $email = trim($_POST['email']);
        $code = trim($_POST['verification_code']);
        if (verifyCode($email, $code)) {
            registerEmail($email);
            $successMessage = "Successfully subscribed to GitHub Timeline!";
        } else {
            $message = "Invalid verification code.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>GitHub Timeline Subscription</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f7f9fc;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .container {
            background: white;
            padding: 30px 40px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 10px;
            width: 350px;
            text-align: center;
        }
        input[type="email"], input[type="text"] {
            padding: 10px;
            width: 100%;
            margin: 10px 0 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .message {
            margin-top: 20px;
            color: green;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>GitHub Timeline Subscription</h2>

    <?php if (!$message && !$successMessage): ?>
        <form method="POST">
            <input type="email" name="email" required placeholder="Enter your email">
            <button type="submit">Send Verification Code</button>
        </form>
    <?php elseif ($message && !$successMessage): ?>
        <form method="POST">
            <p>We've sent a verification code to your email. Please enter it below to confirm your subscription.</p>
            <input type="email" name="email" required placeholder="Enter your email" value="<?= htmlspecialchars($email ?? '') ?>">
            <input type="text" name="verification_code" required placeholder="Enter verification code">
            <button type="submit">Verify Code</button>
        </form>
    <?php elseif ($successMessage): ?>
        <div class="message"><?= $successMessage ?></div>
    <?php endif; ?>

    <?php if ($message && !$successMessage): ?>
        <div class="error"><?= $message ?></div>
    <?php endif; ?>
</div>
</body>
</html>
