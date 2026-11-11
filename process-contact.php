<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Load Symfony-style .env / .env.local values when this legacy script is used
// outside the Symfony front controller. Real environment variables still win.
if (class_exists(\Symfony\Component\Dotenv\Dotenv::class)) {
    try {
        (new \Symfony\Component\Dotenv\Dotenv())->bootEnv(__DIR__ . '/.env');
    } catch (\Throwable) {
        // Fall back to real environment variables below.
    }
}

$env = static function (string $key, string $default = ''): string {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    return ($value === false || $value === null || $value === '') ? $default : (string) $value;
};

if (($_SERVER["REQUEST_METHOD"] ?? 'GET') === "POST") {

    $name    = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone   = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $package = htmlspecialchars(trim($_POST['package'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (empty($name) || empty($email) || empty($message)) {
        header("Location: contact.php?status=error");
        exit();
    }

    $smtpHost = $env('SMTP_HOST', 'smtp.gmail.com');
    $smtpUser = $env('SMTP_USER');
    $smtpPass = $env('SMTP_PASS');
    $smtpPort = (int) $env('SMTP_PORT', '587');
    $contactTo = $env('CONTACT_TO_EMAIL', 'philo.studio@gmail.com');

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;

        $mail->setFrom($smtpUser, 'PHILO Studio Website');
        $mail->addAddress($contactTo, 'Admin');
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