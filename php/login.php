<?php
$pageTitle = "Login";
require_once 'includes/config.php';
session_start();

if (isset($_SESSION['staff_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM staff WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $staff = $result->fetch_assoc();

    if ($staff && password_verify($password, $staff['password'])) {
        $_SESSION['staff_id'] = $staff['staff_id'];
        $_SESSION['staff_name'] = $staff['name'];
        $_SESSION['staff_role'] = $staff['role'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/base.css">
    <style>
        body {
            justify-content: center;
            align-items: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
        }
        input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: 4px;
            background: var(--card);
            color: var(--fg);
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <h2 style="text-align: center;">Staff Login</h2>
        
        <?php if (isset($error)): ?>
        <p style="color: #dc3545; margin-bottom: 1rem; text-align: center;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem;">Username</label>
                <input type="text" name="username" required>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem;">Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" style="width: 100%;">Login</button>
        </form>
        <div style="font-size: 0.8rem; margin-top: 1rem; opacity: 0.7; text-align: center;">
            <p style="margin: 0.2rem 0;">Admin: admin / admin123</p>
            <p style="margin: 0.2rem 0;">Staff: alice / staff123</p>
        </div>
    </div>
</body>
</html>
