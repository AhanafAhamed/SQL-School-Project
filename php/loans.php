<?php
$pageTitle = "Loans";
require_once 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$today = date('Y-m-d');

// Filter parameters
$due_start = $_GET['due_start'] ?? '';
$due_end = $_GET['due_end'] ?? '';
$return_start = $_GET['return_start'] ?? '';
$return_end = $_GET['return_end'] ?? '';
$status = $_GET['status'] ?? '';

// Check if any filter is active
$is_filtering = !empty($due_start) || !empty($due_end) || !empty($return_start) || !empty($return_end) || !empty($status);

// Build dynamic query
$query = "SELECT * FROM loan_details_view WHERE 1=1";
$params = [];
$types = "";

if ($due_start) {
    $query .= " AND due_date >= ?";
    $params[] = $due_start;
    $types .= "s";
}
if ($due_end) {
    $query .= " AND due_date <= ?";
    $params[] = $due_end;
    $types .= "s";
}
if ($return_start) {
    $query .= " AND return_date >= ?";
    $params[] = $return_start;
    $types .= "s";
}
if ($return_end) {
    $query .= " AND return_date <= ?";
    $params[] = $return_end;
    $types .= "s";
}

if ($status === 'active') {
    $query .= " AND return_date IS NULL AND due_date >= ?";
    $params[] = $today;
    $types .= "s";
} elseif ($status === 'overdue') {
    $query .= " AND return_date IS NULL AND due_date < ?";
    $params[] = $today;
    $types .= "s";
} elseif ($status === 'returned') {
    $query .= " AND return_date IS NOT NULL";
}

$query .= " ORDER BY return_date IS NULL DESC, due_date ASC";

$stmt = $conn->prepare($query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="card" style="margin-bottom: 2rem;">
    <div class="filter-header" onclick="toggleFilters('loan-filters')">
        <h3>Filter Loans</h3>
        <span class="filter-toggle-icon" id="toggle-icon-loan-filters" style="font-size: 1.5rem;">▼</span>
    </div>
    <div id="loan-filters" class="filter-content <?php echo $is_filtering ? '' : 'collapsed'; ?>">
        <form method="GET" class="filter-grid">
            <div class="filter-group">
                <label>Due Date From</label>
                <input type="date" name="due_start" value="<?php echo htmlspecialchars($due_start); ?>">
            </div>
            <div class="filter-group">
                <label>Due Date To</label>
                <input type="date" name="due_end" value="<?php echo htmlspecialchars($due_end); ?>">
            </div>
            <div class="filter-group">
                <label>Returned From</label>
                <input type="date" name="return_start" value="<?php echo htmlspecialchars($return_start); ?>">
            </div>
            <div class="filter-group">
                <label>Returned To</label>
                <input type="date" name="return_end" value="<?php echo htmlspecialchars($return_end); ?>">
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active (In Progress)</option>
                    <option value="overdue" <?php echo $status == 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                    <option value="returned" <?php echo $status == 'returned' ? 'selected' : ''; ?>>Returned</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn">Filter</button>
                <a href="loans.php" class="btn" style="background: var(--border); color: var(--fg); text-align: center; text-decoration: none;">Clear</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleFilters(id) {
    const content = document.getElementById(id);
    content.classList.toggle('collapsed');
}
</script>

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
                        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; align-items: center;">
                            <?php if (!$loan['return_date']): ?>
                            <form action="return_loan.php" method="POST">
                                <input type="hidden" name="loan_id" value="<?php echo $loan['loan_id']; ?>">
                                <input type="hidden" name="book_id" value="<?php echo $loan['book_id']; ?>">
                                <button type="submit" class="btn-action btn-return">Return</button>
                            </form>
                            <?php endif; ?>
                            <a href="edit_loan.php?id=<?php echo $loan['loan_id']; ?>" class="btn-action btn-edit">Edit</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
