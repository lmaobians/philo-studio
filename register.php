<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

$error = '';
$redirect = $_GET['redirect'] ?? 'dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Convert full name to ALL CAPS on submit
    $name = mb_strtoupper(trim($_POST['name'] ?? ''), 'UTF-8');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($name) && !empty($email) && !empty($password)) {
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            $database = new Database();
            $db = $database->getConnection();

            // Check if email already exists in customers
            $stmt = $db->prepare("SELECT customer_id FROM customers WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'Email address is already registered.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
$insert = $db->prepare("INSERT INTO customers (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
                if ($insert->execute([$name, $email, $phone, $hashed_password])) {
                    $customer_id = $db->lastInsertId();
                    
                    $_SESSION['customer_id']    = $customer_id;
                    $_SESSION['customer_name']  = $name; // Stores ALL CAPS name
                    $_SESSION['customer_email'] = $email;

                    header("Location: " . $redirect);
                    exit();
                } else {
                    $error = 'Something went wrong. Please try again.';
                }
            }
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Philo Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="auth-container">
        <div class="auth-card">
            <h2>Create Account</h2>
            <p class="auth-subtitle">Sign up to book your studio session</p>

            <?php if (!empty($error)): ?>
                <div class="auth-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="register.php?redirect=<?= urlencode($redirect) ?>" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required placeholder="JUAN DELA CRUZ" style="text-transform: uppercase;">
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required placeholder="you@example.com">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" placeholder="0912 345 6789">
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn-primary full-width">CREATE ACCOUNT</button>
            </form>

            <p class="auth-footer">
                Already have an account? <a href="login.php?redirect=<?= urlencode($redirect) ?>">Log in here</a>
            </p>
        </div>
    </section>

</body>
</html>