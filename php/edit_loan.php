<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id) {
    header("Location: loans.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM loans WHERE loan_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();

if (!$loan) {
    header("Location: loans.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_id = $_POST['book_id'];
    $member_id = $_POST['member_id'];
    $loan_date = $_POST['loan_date'];
    $due_date = $_POST['due_date'];
    $return_date = !empty($_POST['return_date']) ? $_POST['return_date'] : null;

    $stmt = $conn->prepare("UPDATE loans SET book_id=?, member_id=?, loan_date=?, due_date=?, return_date=? WHERE loan_id=?");
    $stmt->bind_param("ssssss", $book_id, $member_id, $loan_date, $due_date, $return_date, $id);
    $stmt->execute();

    $_SESSION['flash'] = ["Loan updated!"];
    header("Location: loans.php");
    exit();
}

$books = $conn->query("SELECT * FROM books ORDER BY title");
$members = $conn->query("SELECT * FROM members ORDER BY member_name");
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h1>Edit Loan</h1>
    
    <form method="POST" style="margin-top: 2rem;">
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Book</label>
            <select name="book_id" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
                <?php while($book = $books->fetch_assoc()): ?>
                <option value="<?php echo $book['book_id']; ?>" <?php echo $loan['book_id'] == $book['book_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($book['title']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Member</label>
            <select name="member_id" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
                <?php while($member = $members->fetch_assoc()): ?>
                <option value="<?php echo $member['member_id']; ?>" <?php echo $loan['member_id'] == $member['member_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($member['member_name']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem;">Loan Date</label>
                <input type="date" name="loan_date" value="<?php echo $loan['loan_date']; ?>" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem;">Due Date</label>
                <input type="date" name="due_date" value="<?php echo $loan['due_date']; ?>" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="margin-bottom: 2rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Return Date (optional)</label>
            <input type="date" name="return_date" value="<?php echo $loan['return_date']; ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn" style="flex: 1;">Update Loan</button>
            <a href="loans.php" class="btn" style="background: var(--border); color: var(--text);">Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
