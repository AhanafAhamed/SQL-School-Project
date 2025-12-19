<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
    $stmt->bind_param("s", $id);
    if ($stmt->execute()) {
        $_SESSION['flash'] = ["Book deleted!"];
    } else {
        $_SESSION['flash'] = ["Error: Could not delete book. It might be referenced by loans."];
    }
}

header("Location: books.php");
exit();
?>
