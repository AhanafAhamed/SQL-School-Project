<?php
$pageTitle = "Dashboard";
require_once 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Aggregate stats
$book_count = $conn->query("SELECT COUNT(*) FROM books")->fetch_row()[0];
$member_count = $conn->query("SELECT COUNT(*) FROM members")->fetch_row()[0];
$loan_count = $conn->query("SELECT COUNT(*) FROM loans WHERE return_date IS NULL")->fetch_row()[0];

// Popular books
$popular_books_query = "
    SELECT b.title, COUNT(l.loan_id) as loan_count
    FROM books b
    LEFT JOIN loans l ON b.book_id = l.book_id
    GROUP BY b.book_id
    ORDER BY loan_count DESC LIMIT 5
";
$popular_books = $conn->query($popular_books_query);
?>

<div class="card">
    <h1>Welcome to Library Management System</h1>
    <p>Manage your books, members, and loans efficiently.</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 2rem;">
        <div class="card" style="text-align: center;">
            <h3>Books</h3>
            <p style="font-size: 2rem; font-weight: bold;"><?php echo $book_count; ?></p>
            <a href="books.php" class="btn">View All</a>
        </div>
        <div class="card" style="text-align: center;">
            <h3>Members</h3>
            <p style="font-size: 2rem; font-weight: bold;"><?php echo $member_count; ?></p>
            <a href="members.php" class="btn">View All</a>
        </div>
        <div class="card" style="text-align: center;">
            <h3>Active Loans</h3>
            <p style="font-size: 2rem; font-weight: bold;"><?php echo $loan_count; ?></p>
            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                <a href="loans.php" class="btn">View All</a>
                <a href="overdue.php" class="btn" style="background: #dc3545;">Overdue</a>
            </div>
        </div>
    </div>
    <div style="margin-top: 3rem; border-top: 1px solid var(--border); padding-top: 2rem;">
        <h3>Most Popular Books</h3>
        <ul style="list-style: none; padding: 0; margin-top: 1rem; display: grid; gap: 1rem;">
            <?php while($book = $popular_books->fetch_assoc()): ?>
            <li class="card" style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; margin: 0;">
                <span style="font-weight: 500;"><?php echo htmlspecialchars($book['title']); ?></span>
                <span style="font-size: 0.85rem; padding: 0.25rem 0.5rem; background: var(--accent); color: white; border-radius: 12px;"><?php echo $book['loan_count']; ?> Loans</span>
            </li>
            <?php endwhile; ?>
        </ul>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
