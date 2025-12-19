<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM authors WHERE author_id = ?");
    $stmt->bind_param("s", $id);
    if ($stmt->execute()) {
        $_SESSION['flash'] = ["Author deleted!"];
    } else {
        $_SESSION['flash'] = ["Error: Could not delete author. They might have books."];
    }
}

header("Location: authors.php");
exit();
?>
