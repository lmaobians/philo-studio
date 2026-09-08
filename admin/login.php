<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            // Fetch record matching username (case-insensitive)
            $stmt = $db->prepare("SELECT admin_id, username, password, full_name FROM admins WHERE LOWER(username) = LOWER(:username) LIMIT 1");
            $stmt->execute([':username' => $username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if user exists and verify password against the hash
            if ($admin && password_verify($password, $admin['password'])) {
                // Regenerate session ID for extra session fixation security
                session_regenerate_id(true);

                $_SESSION['admin_id']   = $admin['admin_id'];
                $_SESSION['admin_user'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | PHILO Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="admin-login-page">

<div class="login-card">
    <h2>PHILO Studio</h2>
    <p class="subtitle">Admin Portal Access</p>

    <?php if (!empty($error)): ?>
        <div class="alert-error" style="color: #dc2626; background: #fef2f2; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 0.9rem;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn-submit">LOG IN</button>
    </form>
</div>

</body>
</html>