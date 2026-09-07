<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name    = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone   = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $package = htmlspecialchars(trim($_POST['package'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (empty($name) || empty($email) || empty($message)) {
        header("Location: contact.php?status=error");
        exit();
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'biance.cruz@norsu.edu.ph';
        $mail->Password   = 'xefy otwa iweo ansi';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('biance.cruz@norsu.edu.ph', 'PHILO Studio Website');
        $mail->addAddress('philo.studio@gmail.com', 'Admin');
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "New Inquiry from " . $name;
        $mail->Body    = "
            <h2>New Contact Form Submission</h2>
            <p><strong>Name:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Phone:</strong> {$phone}</p>
            <p><strong>Interested Package:</strong> {$package}</p>
            <p><strong>Message:</strong><br>" . nl2br($message) . "</p>
        ";

        $mail->send();
        header("Location: contact.php?status=success");
        exit();

    } catch (Exception $e) {
        header("Location: contact.php?status=error");
        exit();
    }
}