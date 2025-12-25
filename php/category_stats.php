<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$category_id = $_GET['id'] ?? null;
if (!$category_id) {
    header("Location: categories.php");
    exit();
}


$stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
$stmt->bind_param("s", $category_id);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();

if (!$category) {
    header("Location: categories.php");
    exit();
}

$pageTitle = $category['category_name'] . " Stats";
require_once 'includes/header.php';

$stmt = $conn->prepare("SELECT COUNT(*) as book_count FROM books WHERE category_id = ?");
$stmt->bind_param("s", $category_id);
$stmt->execute();
$total_books = $stmt->get_result()->fetch_assoc()['book_count'];

$stmt = $conn->prepare("
    SELECT AVG(loan_count) as avg_loans
    FROM (
        SELECT b.book_id, COUNT(l.loan_id) as loan_count
        FROM books b
        LEFT JOIN loans l ON b.book_id = l.book_id
        WHERE b.category_id = ?
        GROUP BY b.book_id
    ) as book_loans
");
$stmt->bind_param("s", $category_id);
$stmt->execute();
$avg_loans = $stmt->get_result()->fetch_assoc()['avg_loans'] ?? 0;

$stmt = $conn->prepare("
    SELECT a.author_name, COUNT(b.book_id) as book_count
    FROM books b
    JOIN authors a ON b.author_id = a.author_id
    WHERE b.category_id = ?
    GROUP BY a.author_id
    ORDER BY book_count DESC
    LIMIT 5
");
$stmt->bind_param("s", $category_id);
$stmt->execute();
$top_authors = $stmt->get_result();

$stmt = $conn->prepare("
    SELECT b.title, b.isbn, r.loan_count, r.global_rank
    FROM books b
    JOIN (
        SELECT book_id, COUNT(*) as loan_count,
               RANK() OVER (ORDER BY COUNT(*) DESC) as global_rank
        FROM loans
        GROUP BY book_id
    ) as r ON b.book_id = r.book_id
    WHERE b.category_id = ?
    AND b.book_id IN (
        SELECT book_id FROM (
            SELECT book_id 
            FROM loans 
            GROUP BY book_id 
            ORDER BY COUNT(*) DESC 
            LIMIT 100
        ) as top_titles_subquery
    )
    ORDER BY r.global_rank ASC
");
$stmt->bind_param("s", $category_id);
$stmt->execute();
$popular_books_res = $stmt->get_result();
$popular_books = [];
while($row = $popular_books_res->fetch_assoc()) $popular_books[] = $row;
$popular_count = count($popular_books);

?>

<div style="margin-bottom: 1.5rem;">
    <a href="categories.php" class="btn" style="background: var(--border); color: var(--fg);">&larr; Back to Categories</a>
</div>

<div class="card">
    <h1 style="margin-bottom: 2rem;"><?php echo htmlspecialchars($category['category_name']); ?> Statistics</h1>
    
    <?php if ($total_books == 0): ?>
        <p style="text-align: center; font-size: 1.2rem; opacity: 0.7; padding: 3rem;">No books found in this category.</p>
    <?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
        <div class="card" style="margin:0; border-color: var(--accent); border-width: 2px;">
            <p style="opacity: 0.7; margin: 0;">Total Books in Genre</p>
            <h2 style="margin: 0.5rem 0 0;"><?php echo $total_books; ?></h2>
        </div>
        <div class="card" style="margin:0;">
            <p style="opacity: 0.7; margin: 0;">Average Loans per Book</p>
            <h2 style="margin: 0.5rem 0 0;"><?php echo number_format($avg_loans, 2); ?></h2>
        </div>
    </div>

    <div style="margin-top: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <div class="card" style="margin:0;">
            <h3>Top Authors in Genre</h3>
            <table style="margin-top: 1rem;">
                <thead>
                    <tr>
                        <th>Author</th>
                        <th>Books</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $top_authors->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['author_name']); ?></td>
                        <td><?php echo $row['book_count']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="card" style="margin:0;">
            <h3>Genre Bestsellers (<?php echo $popular_count; ?>)</h3>
            <p style="font-size: 0.9rem; opacity: 0.7; margin-bottom: 1rem;">
                Books in this genre currently appearing in the Top 100 overall most borrowed list.
            </p>
            <?php if ($popular_count > 0): ?>
                <table style="margin-top: 0;">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Rank</th>
                            <th>Book Title</th>
                            <th>Loans</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($popular_books as $row): ?>
                        <tr>
                            <td>#<?php echo $row['global_rank']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['title']); ?></strong><br>
                                <small style="opacity: 0.6;">ISBN: <?php echo htmlspecialchars($row['isbn']); ?></small>
                            </td>
                            <td><?php echo $row['loan_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="font-style: italic; opacity: 0.6; padding: 1rem; border: 1px dashed var(--border); border-radius: 8px;">
                    No books from this genre are currently in the global top 100.
                </p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
