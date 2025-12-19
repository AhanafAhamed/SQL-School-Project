<?php
$pageTitle = "Overdue Books";
require_once 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$query = "SELECT * FROM overdue_books";
$result = $conn->query($query);
?>

<div class="card">
    <h1>Overdue Books</h1>
    <p>The following books are currently past their due date and have not been returned.</p>

    <div style="overflow-x: auto; margin-top: 2rem;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid var(--border);">
                    <th style="padding: 1rem;">Book Title</th>
                    <th style="padding: 1rem;">Member</th>
                    <th style="padding: 1rem;">Loan Date</th>
                    <th style="padding: 1rem;">Due Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem; font-weight: 500;"><?php echo htmlspecialchars($row['title']); ?></td>
                        <td style="padding: 1rem;"><?php echo htmlspecialchars($row['member_name']); ?></td>
                        <td style="padding: 1rem; opacity: 0.8;"><?php echo $row['loan_date']; ?></td>
                        <td style="padding: 1rem; color: #dc3545; font-weight: bold;"><?php echo $row['due_date']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="padding: 2rem; text-align: center; opacity: 0.5;">No overdue books found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
