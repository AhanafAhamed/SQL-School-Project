<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['loan_id'])) {
    $loan_id = $_POST['loan_id'];
    $book_id = $_POST['book_id'];
    $today = date('Y-m-d');

    $conn->begin_transaction();
    try {
        // Update loan
        $stmt = $conn->prepare("UPDATE loans SET return_date = ? WHERE loan_id = ?");
        $stmt->bind_param("ss", $today, $loan_id);
        $stmt->execute();

        // Update book availability
        $stmt = $conn->prepare("UPDATE books SET total_available = total_available + 1, total_loaned = total_loaned - 1 WHERE book_id = ?");
        $stmt->bind_param("s", $book_id);
        $stmt->execute();

        $conn->commit();
        $_SESSION['flash'] = ["Book returned successfully!"];
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash'] = ["Error returning book."];
    }
}

header("Location: loans.php");
exit();
?>
