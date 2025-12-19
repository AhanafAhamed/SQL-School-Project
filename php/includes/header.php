<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . " - " . APP_NAME : APP_NAME; ?></title>
    <link rel="stylesheet" href="css/base.css">
</head>

<body>
    <header>
        <div class="logo"><?php echo APP_NAME; ?></div>
        <nav>
            <?php if (isset($_SESSION['staff_id'])): ?>
            <a href="index.php">Dashboard</a>
            <a href="books.php">Books</a>
            <a href="members.php">Members</a>
            <a href="loans.php">Loans</a>
            <a href="authors.php">Authors</a>
            <a href="categories.php">Categories</a>
            <?php if ($_SESSION['staff_role'] == 'Admin'): ?>
            <a href="register_staff.php">Register Staff</a>
            <?php endif; ?>
            <span style="margin-left: 1rem; opacity: 0.5;">|</span>
            <a href="logout.php" style="color: #dc3545;">Logout (<?php echo $_SESSION['staff_name']; ?>)</a>
            <?php else: ?>
            <a href="login.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <?php if (isset($_SESSION['flash'])): ?>
        <div id="flash-container">
            <?php foreach ($_SESSION['flash'] as $flash): ?>
            <div class="alert">
                <span><?php echo $flash; ?></span>
                <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
            <?php endforeach; ?>
            <?php unset($_SESSION['flash']); ?>
        </div>
        <script>
            setTimeout(() => {
                const container = document.getElementById('flash-container');
                if (container) {
                    Array.from(container.children).forEach(alert => {
                        alert.style.opacity = '0';
                        alert.style.transition = 'opacity 0.5s ease-out';
                        setTimeout(() => alert.remove(), 500);
                    });
                }
            }, 3000);
        </script>
        <?php endif; ?>
