<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_id = $_POST['book_id'];
    $member_id = $_POST['member_id'];
    $due_date = $_POST['due_date'];
    $loan_date = date('Y-m-d');
    $loan_id = substr(uniqid(), -8);
    $staff_id = $_SESSION['staff_id'];

    $conn->begin_transaction();
    try {
        
        $check_stmt = $conn->prepare("SELECT total_available FROM books WHERE book_id = ?");
        $check_stmt->bind_param("s", $book_id);
        $check_stmt->execute();
        $book = $check_stmt->get_result()->fetch_assoc();

        if ($book && $book['total_available'] > 0) {
            
            $stmt = $conn->prepare("INSERT INTO loans (loan_id, book_id, member_id, staff_id, loan_date, due_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $loan_id, $book_id, $member_id, $staff_id, $loan_date, $due_date);
            $stmt->execute();

            
            $update_stmt = $conn->prepare("UPDATE books SET total_available = total_available - 1, total_loaned = total_loaned + 1 WHERE book_id = ?");
            $update_stmt->bind_param("s", $book_id);
            $update_stmt->execute();

            $conn->commit();
            $_SESSION['flash'] = ["Book issued successfully!"];
            header("Location: loans.php");
            exit();
        } else {
            $error = "Book not available for loan.";
            $conn->rollback();
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error issuing loan: " . $e->getMessage();
    }
}

$books = $conn->query("SELECT * FROM books WHERE total_available > 0 ORDER BY title");
$members = $conn->query("SELECT * FROM members ORDER BY member_name");
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h1>Issue New Book</h1>
    
    <?php if (isset($error)): ?>
    <div class="alert" style="margin-top: 1rem;"><span><?php echo $error; ?></span></div>
    <?php endif; ?>

    <form method="POST" style="margin-top: 2rem;">
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Select Book</label>
            <select name="book_id" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
                <?php while($book = $books->fetch_assoc()): ?>
                <option value="<?php echo $book['book_id']; ?>"><?php echo htmlspecialchars($book['title']); ?> (<?php echo $book['total_available']; ?> available)</option>
                <?php endwhile; ?>
            </select>
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Select Member</label>
            <select name="member_id" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
                <?php while($member = $members->fetch_assoc()): ?>
                <option value="<?php echo $member['member_id']; ?>"><?php echo htmlspecialchars($member['member_name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div style="margin-bottom: 2rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Due Date</label>
            <input type="date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn" style="flex: 1;">Issue Book</button>
            <a href="loans.php" class="btn" style="background: var(--border); color: var(--text);">Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
