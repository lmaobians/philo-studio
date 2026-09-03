<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

$error = '';
$redirect = $_GET['redirect'] ?? 'dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $database = new Database();
        $db = $database->getConnection();

// Change 'password' to your actual database column name (e.g., password_hash)
        $stmt = $db->prepare("SELECT customer_id, full_name AS name, email, password_hash AS password FROM customers WHERE email = ? LIMIT 1");        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Email not found in the database
            $error = 'No account found with this email. Please create an account first.';
        } elseif (password_verify($password, $user['password'])) {
            // Credentials correct
            $_SESSION['customer_id']    = $user['customer_id'];
            $_SESSION['customer_name']  = $user['name'];
            $_SESSION['customer_email'] = $user['email'];

            header("Location: " . $redirect);
            exit();
        } else {
            // Email exists, but password was incorrect
            $error = 'Incorrect password. Please try again.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Philo Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="auth-container">
        <div class="auth-card">
            <h2>Welcome Back</h2>
            <p class="auth-subtitle">Log in to manage your studio bookings</p>

            <?php if (!empty($error)): ?>
                <div class="auth-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="login.php?redirect=<?= urlencode($redirect) ?>" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn-primary full-width">LOG IN</button>
            </form>

            <p class="auth-footer">
                Don't have an account? <a href="register.php?redirect=<?= urlencode($redirect) ?>">Sign up here</a>
            </p>
        </div>
    </section>

</body>
</html>