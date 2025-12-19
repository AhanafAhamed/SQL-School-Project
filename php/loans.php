<?php
$pageTitle = "Loans";
require_once 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$today = date('Y-m-d');
$query = "SELECT * FROM loan_details_view ORDER BY return_date IS NULL DESC, due_date ASC";
$result = $conn->query($query);
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>Loan Management</h1>
        <a href="issue_loan.php" class="btn">Issue New Book</a>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid var(--border);">
                    <th style="padding: 1rem;">Book Title</th>
                    <th style="padding: 1rem;">Member</th>
                    <th style="padding: 1rem;">Staff</th>
                    <th style="padding: 1rem;">Loan Date</th>
                    <th style="padding: 1rem;">Due Date</th>
                    <th style="padding: 1rem;">Status</th>
                    <th style="padding: 1rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($loan = $result->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 1rem; font-weight: 500;"><?php echo htmlspecialchars($loan['book_title']); ?></td>
                    <td style="padding: 1rem;"><?php echo htmlspecialchars($loan['member_name']); ?></td>
                    <td style="padding: 1rem; opacity: 0.8; font-size: 0.9rem;"><?php echo htmlspecialchars($loan['staff_name']); ?></td>
                    <td style="padding: 1rem; opacity: 0.8;"><?php echo $loan['loan_date']; ?></td>
                    <td style="padding: 1rem; opacity: 0.8;"><?php echo $loan['due_date']; ?></td>
                    <td style="padding: 1rem;">
                        <?php if ($loan['return_date']): ?>
                            <span style="padding: 0.25rem 0.5rem; border-radius: 4px; background: #e2e3e5; color: #383d41; font-size: 0.85rem;">Returned (<?php echo $loan['return_date']; ?>)</span>
                        <?php else: ?>
                            <?php if ($loan['due_date'] < $today): ?>
                                <span style="padding: 0.25rem 0.5rem; border-radius: 4px; background: #f8d7da; color: #721c24; font-size: 0.85rem;">OVERDUE</span>
                            <?php else: ?>
                                <span style="padding: 0.25rem 0.5rem; border-radius: 4px; background: #d1ecf1; color: #0c5460; font-size: 0.85rem;">Active</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 1rem; text-align: right;">
                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                            <?php if (!$loan['return_date']): ?>
                            <form action="return_loan.php" method="POST">
                                <input type="hidden" name="loan_id" value="<?php echo $loan['loan_id']; ?>">
                                <input type="hidden" name="book_id" value="<?php echo $loan['book_id']; ?>">
                                <button type="submit" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: #28a745;">Return</button>
                            </form>
                            <?php endif; ?>
                            <a href="edit_loan.php?id=<?php echo $loan['loan_id']; ?>" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: var(--border); color: var(--text);">Edit</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
