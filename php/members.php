<?php
$pageTitle = "Members";
require_once 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$today = date('Y-m-d');

$query = "SELECT m.*,
    COUNT(l.loan_id) as total_borrowed,
    SUM(CASE WHEN l.return_date IS NULL AND l.due_date >= ? THEN 1 ELSE 0 END) as active_borrowed,
    SUM(CASE WHEN l.return_date IS NULL AND l.due_date < ? THEN 1 ELSE 0 END) as overdue_borrowed,
    SUM(CASE WHEN l.return_date IS NOT NULL THEN 1 ELSE 0 END) as returned
    FROM members m
    LEFT JOIN loans l ON m.member_id = l.member_id";

if ($search) {
    $search_param = "%$search%";
    $stmt = $conn->prepare("$query WHERE m.member_name LIKE ? OR m.email LIKE ? GROUP BY m.member_id ORDER BY m.member_name");
    $stmt->bind_param("ssss", $today, $today, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $conn->prepare("$query GROUP BY m.member_id ORDER BY m.member_name");
    $stmt->bind_param("ss", $today, $today);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>Library Members</h1>
        <a href="edit_member.php" class="btn">Add New Member</a>
    </div>

    <form method="GET" style="display: flex; gap: 0.5rem; margin-bottom: 2rem;">
        <input type="text" name="search" placeholder="Search members..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        <button type="submit" class="btn">Search</button>
    </form>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid var(--border);">
                    <th style="padding: 1rem;">Name</th>
                    <th style="padding: 1rem;">Email</th>
                    <th style="padding: 1rem;">Phone</th>
                    <th style="padding: 1rem; text-align: center;">Total</th>
                    <th style="padding: 1rem; text-align: center;">Active</th>
                    <th style="padding: 1rem; text-align: center;">Overdue</th>
                    <th style="padding: 1rem; text-align: center;">Returned</th>
                    <th style="padding: 1rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($member = $result->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 1rem; font-weight: 500;">
                        <a href="member_history.php?id=<?php echo $member['member_id']; ?>" style="text-decoration: none; color: inherit;"><?php echo htmlspecialchars($member['member_name']); ?></a>
                    </td>
                    <td style="padding: 1rem; opacity: 0.8;"><?php echo htmlspecialchars($member['email']); ?></td>
                    <td style="padding: 1rem; opacity: 0.8;"><?php echo htmlspecialchars($member['phone']); ?></td>
                    <td style="padding: 1rem; text-align: center; font-weight: 500;"><?php echo $member['total_borrowed']; ?></td>
                    <td style="padding: 1rem; text-align: center;">
                        <span style="padding: 0.25rem 0.5rem; border-radius: 4px; background: #d1ecf1; color: #0c5460; font-size: 0.85rem;"><?php echo $member['active_borrowed']; ?></span>
                    </td>
                    <td style="padding: 1rem; text-align: center;">
                        <?php if ($member['overdue_borrowed'] > 0): ?>
                            <span style="padding: 0.25rem 0.5rem; border-radius: 4px; background: #f8d7da; color: #721c24; font-size: 0.85rem;"><?php echo $member['overdue_borrowed']; ?></span>
                        <?php else: ?>
                            <span style="opacity: 0.5;">0</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 1rem; text-align: center; opacity: 0.7;"><?php echo $member['returned']; ?></td>
                    <td style="padding: 1rem; text-align: right;">
                        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; align-items: center;">
                            <a href="edit_member.php?id=<?php echo $member['member_id']; ?>" class="btn-action btn-edit">Edit</a>
                            <a href="member_history.php?id=<?php echo $member['member_id']; ?>" class="btn-action" style="color: var(--accent);">History</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
