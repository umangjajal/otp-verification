<?php
session_start();
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['unsubscribe_email'])) {
        $email = trim($_POST['unsubscribe_email']);
        $code = generateVerificationCode();
        file_put_contents(__DIR__ . "/codes/unsub_{$email}.txt", $code);
        sendVerificationEmail($email, $code, true);
        $_SESSION['unsubscribe_email'] = $email;
        $msg = "Unsubscribe code sent to your email.";
    } elseif (isset($_POST['unsubscribe_verification_code'])) {
        $email = $_SESSION['unsubscribe_email'] ?? '';
        $code = trim($_POST['unsubscribe_verification_code']);
        if ($email && verifyCode($email, $code, true)) {
            unsubscribeEmail($email);
            $msg = "You have successfully unsubscribed.";
        } else {
            $msg = "Invalid unsubscribe code.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Unsubscribe</title>
    <style>
        body { font-family: Arial; background: #fff8f6; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .form-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.2); width: 350px; }
        h2 { text-align: center; }
        input, button { width: 100%; padding: 10px; margin-top: 15px; border-radius: 5px; border: 1px solid #ccc; }
        button { background-color: #dc3545; color: white; border: none; }
        button:hover { background-color: #c82333; }
        .message { margin-top: 10px; text-align: center; color: green; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Unsubscribe</h2>
    <?php if (isset($msg)) echo "<div class='message'>$msg</div>"; ?>
    <form method="post">
        <input type="email" name="unsubscribe_email" required placeholder="Enter email to unsubscribe">
        <button id="submit-unsubscribe" type="submit">Unsubscribe</button>
    </form>
    <form method="post">
        <input type="text" name="unsubscribe_verification_code" placeholder="Enter verification code">
        <button id="verify-un
