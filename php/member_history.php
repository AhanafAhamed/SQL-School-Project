<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id) {
    header("Location: members.php");
    exit();
}

require_once 'includes/header.php';

$stmt = $conn->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

if (!$member) {
    echo "<h1>Member not found.</h1>";
    require_once 'includes/footer.php';
    exit();
}

$pageTitle = $member['member_name'] . "'s History";

$stmt = $conn->prepare("SELECT * FROM loan_details_view WHERE member_id = ? ORDER BY loan_date DESC");
$stmt->bind_param("s", $id);
$stmt->execute();
$history = $stmt->get_result();


$active_loans = 0;
$total_loans = $history->num_rows;
$history_rows = [];
while($row = $history->fetch_assoc()) {
    if (!$row['return_date']) $active_loans++;
    $history_rows[] = $row;
}
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 2rem;">
        <h1>Member: <?php echo htmlspecialchars($member['member_name']); ?></h1>
        <div style="display: flex; gap: 1rem;">
            <div class="card" style="margin: 0; padding: 0.5rem 1rem; text-align: center;">
                <span style="font-size: 0.85rem; opacity: 0.6; display: block;">Total Loans</span>
                <span style="font-weight: bold; font-size: 1.25rem;"><?php echo $total_loans; ?></span>
            </div>
            <div class="card" style="margin: 0; padding: 0.5rem 1rem; text-align: center;">
                <span style="font-size: 0.85rem; opacity: 0.6; display: block;">Active</span>
                <span style="font-weight: bold; font-size: 1.25rem; color: var(--accent);"><?php echo $active_loans; ?></span>
            </div>
        </div>
    </div>

    <div style="margin-bottom: 2rem;">
        <p><strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($member['phone']); ?></p>
    </div>

    <h3>Loan History</h3>
    <div style="overflow-x: auto; margin-top: 1rem;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid var(--border);">
                    <th style="padding: 1rem;">Book Title</th>
                    <th style="padding: 1rem;">Loan Date</th>
                    <th style="padding: 1rem;">Due Date</th>
                    <th style="padding: 1rem;">Return Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($history_rows as $loan): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 1rem; font-weight: 500;"><?php echo htmlspecialchars($loan['book_title']); ?></td>
                    <td style="padding: 1rem; opacity: 0.8;"><?php echo $loan['loan_date']; ?></td>
                    <td style="padding: 1rem; opacity: 0.8;"><?php echo $loan['due_date']; ?></td>
                    <td style="padding: 1rem;">
                        <?php if ($loan['return_date']): ?>
                            <span style="color: #28a745;"><?php echo $loan['return_date']; ?></span>
                        <?php else: ?>
                            <span style="color: #007bff; font-weight: 500;">Active</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($history_rows)): ?>
                <tr><td colspan="4" style="padding: 2rem; text-align: center; opacity: 0.5;">No loan history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
