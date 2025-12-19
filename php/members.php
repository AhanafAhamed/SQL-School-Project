<?php
$pageTitle = "Members";
require_once 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT * FROM members";

if ($search) {
    $search_param = "%$search%";
    $stmt = $conn->prepare("$query WHERE member_name LIKE ? OR email LIKE ?");
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
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
                    <td style="padding: 1rem; text-align: right;">
                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                            <a href="edit_member.php?id=<?php echo $member['member_id']; ?>" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: var(--border); color: var(--text);">Edit</a>
                            <a href="member_history.php?id=<?php echo $member['member_id']; ?>" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">History</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
