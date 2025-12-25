<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$author_id = $_GET['id'] ?? null;
if (!$author_id) {
    header("Location: authors.php");
    exit();
}


$stmt = $conn->prepare("SELECT author_name FROM authors WHERE author_id = ?");
$stmt->bind_param("s", $author_id);
$stmt->execute();
$author = $stmt->get_result()->fetch_assoc();

if (!$author) {
    header("Location: authors.php");
    exit();
}

$pageTitle = $author['author_name'] . " Stats";
require_once 'includes/header.php';


$stmt = $conn->prepare("SELECT COUNT(*) as book_count FROM books WHERE author_id = ?");
$stmt->bind_param("s", $author_id);
$stmt->execute();
$published_books = $stmt->get_result()->fetch_assoc()['book_count'];


$stmt = $conn->prepare("
    SELECT COUNT(*) as unloaned_count 
    FROM books b 
    WHERE author_id = ? 
    AND NOT EXISTS (SELECT 1 FROM loans l WHERE l.book_id = b.book_id)
");
$stmt->bind_param("s", $author_id);
$stmt->execute();
$unloaned_books = $stmt->get_result()->fetch_assoc()['unloaned_count'];


$genres = [];
if ($published_books > 0) {
    $stmt = $conn->prepare("
        SELECT c.category_name, COUNT(b.book_id) as book_count
        FROM books b
        JOIN categories c ON b.category_id = c.category_id
        JOIN authors a ON b.author_id = a.author_id
        WHERE a.author_id = ?
        GROUP BY b.category_id
        HAVING book_count = (
            SELECT MAX(cnt) FROM (
                SELECT COUNT(*) as cnt FROM books WHERE author_id = ? GROUP BY category_id
            ) as counts
        )
    ");
    $stmt->bind_param("ss", $author_id, $author_id);
    $stmt->execute();
    $genres_res = $stmt->get_result();
    while($row = $genres_res->fetch_assoc()) $genres[] = $row['category_name'];
}


$stmt = $conn->prepare("
    SELECT AVG(loan_count) as avg_loans
    FROM (
        SELECT b.book_id, COUNT(l.loan_id) as loan_count
        FROM books b
        LEFT JOIN loans l ON b.book_id = l.book_id
        WHERE b.author_id = ?
        GROUP BY b.book_id
    ) as book_loans
");
$stmt->bind_param("s", $author_id);
$stmt->execute();
$avg_loans_per_book = $stmt->get_result()->fetch_assoc()['avg_loans'] ?? 0;


$stmt = $conn->prepare("SELECT MIN(published_year) as earliest_year FROM books WHERE author_id = ? AND published_year > 0");
$stmt->bind_param("s", $author_id);
$stmt->execute();
$earliest_year = $stmt->get_result()->fetch_assoc()['earliest_year'];


$stmt = $conn->prepare("
    SELECT COUNT(l.loan_id) as total_loans
    FROM loans l
    JOIN books b ON l.book_id = b.book_id
    WHERE b.author_id = ?
");
$stmt->bind_param("s", $author_id);
$stmt->execute();
$total_loans = $stmt->get_result()->fetch_assoc()['total_loans'] ?? 0;


$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT l.member_id) as member_count
    FROM loans l
    JOIN books b ON l.book_id = b.book_id
    WHERE b.author_id = ?
");
$stmt->bind_param("s", $author_id);
$stmt->execute();
$unique_members = $stmt->get_result()->fetch_assoc()['member_count'];
$avg_loans_per_member = $unique_members > 0 ? $total_loans / $unique_members : 0;


$stmt = $conn->prepare("
    SELECT b.title, b.isbn, COUNT(l.loan_id) as loan_count
    FROM books b
    JOIN loans l ON b.book_id = l.book_id
    WHERE b.author_id = ?
    GROUP BY b.book_id
    ORDER BY loan_count DESC
    LIMIT 1
");
$stmt->bind_param("s", $author_id);
$stmt->execute();
$max_loaned_book = $stmt->get_result()->fetch_assoc();


$stmt = $conn->prepare("
    SELECT b.title, b.isbn, COUNT(l.loan_id) as loan_count
    FROM books b
    JOIN loans l ON b.book_id = l.book_id
    WHERE b.author_id = ?
    GROUP BY b.book_id
    ORDER BY loan_count ASC
    LIMIT 1
");
$stmt->bind_param("s", $author_id);
$stmt->execute();
$min_loaned_book = $stmt->get_result()->fetch_assoc();

?>

<div style="margin-bottom: 1.5rem;">
    <a href="authors.php" class="btn" style="background: var(--border); color: var(--fg);">&larr; Back to Authors</a>
</div>

<div class="card">
    <h1 style="margin-bottom: 2rem;"><?php echo htmlspecialchars($author['author_name']); ?> Statistics</h1>
    
    <?php if ($published_books == 0): ?>
        <p style="text-align: center; font-size: 1.2rem; opacity: 0.7; padding: 3rem;">This author has no books in the library yet.</p>
    <?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
        <div class="card" style="margin:0; border-color: var(--accent); border-width: 2px;">
            <p style="opacity: 0.7; margin: 0;">Total Published Books</p>
            <h2 style="margin: 0.5rem 0 0;"><?php echo $published_books; ?></h2>
            <small style="opacity: 0.6;"><?php echo $unloaned_books; ?> never borrowed</small>
        </div>
        <div class="card" style="margin:0;">
            <p style="opacity: 0.7; margin: 0;">Most Published Genre(s)</p>
            <h2 style="margin: 0.5rem 0 0;"><?php echo !empty($genres) ? implode(', ', $genres) : 'N/A'; ?></h2>
        </div>
        <div class="card" style="margin:0;">
            <p style="opacity: 0.7; margin: 0;">Total Loans</p>
            <h2 style="margin: 0.5rem 0 0;"><?php echo $total_loans; ?></h2>
        </div>
    </div>

    <div style="margin-top: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
        <div class="card" style="margin:0;">
            <h3>Engagement Metrics</h3>
            <div style="margin-top: 1rem;">
                <p><strong>Avg Loans per Book:</strong> <?php echo number_format($avg_loans_per_book, 2); ?></p>
                <p><strong>Earliest Publication:</strong> <?php echo $earliest_year ? $earliest_year : 'N/A'; ?></p>
                <p><strong>Avg Loans per Member:</strong> <?php echo number_format($avg_loans_per_member, 2); ?></p>
                <p style="font-size: 0.85rem; opacity: 0.6;">(Members who loaned author: <?php echo $unique_members; ?>)</p>
            </div>
        </div>

        <?php if ($max_loaned_book): ?>
        <div class="card" style="margin:0;">
             <h3>Bestseller & Underperformer</h3>
             <div style="margin-top: 1rem;">
                <p style="color: #28a745;"><strong>Most Borrowed:</strong><br>
                    "<?php echo htmlspecialchars($max_loaned_book['title']); ?>" (<?php echo $max_loaned_book['loan_count']; ?> loans)<br>
                    <small>ISBN: <?php echo htmlspecialchars($max_loaned_book['isbn']); ?></small>
                </p>
                <?php if ($min_loaned_book && $min_loaned_book['title'] !== $max_loaned_book['title']): ?>
                <p style="color: #dc3545; margin-top: 1rem;"><strong>Least Borrowed:</strong><br>
                    "<?php echo htmlspecialchars($min_loaned_book['title']); ?>" (<?php echo $min_loaned_book['loan_count']; ?> loans)<br>
                    <small>ISBN: <?php echo htmlspecialchars($min_loaned_book['isbn']); ?></small>
                </p>
                <?php endif; ?>
             </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
