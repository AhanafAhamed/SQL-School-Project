<?php
$pageTitle = "Books";
require_once 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT * FROM book_details_view";

if ($search) {
    $search_param = "%$search%";
    $stmt = $conn->prepare("$query WHERE title LIKE ? OR isbn LIKE ?");
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>Books Inventory</h1>
        <a href="edit_book.php" class="btn">Add New Book</a>
    </div>

    <form method="GET" style="display: flex; gap: 0.5rem; margin-bottom: 2rem;">
        <input type="text" name="search" placeholder="Search by title or ISBN..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        <button type="submit" class="btn">Search</button>
    </form>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid var(--border);">
                    <th style="padding: 1rem;">Title</th>
                    <th style="padding: 1rem;">Author</th>
                    <th style="padding: 1rem;">Category</th>
                    <th style="padding: 1rem;">ISBN</th>
                    <th style="padding: 1rem; text-align: center;">Stock</th>
                    <th style="padding: 1rem; text-align: center;">Available</th>
                    <th style="padding: 1rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($book = $result->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 1rem; font-weight: 500;"><?php echo htmlspecialchars($book['title']); ?></td>
                    <td style="padding: 1rem; opacity: 0.8;"><?php echo htmlspecialchars($book['author_name']); ?></td>
                    <td style="padding: 1rem; opacity: 0.8;"><?php echo htmlspecialchars($book['category_name']); ?></td>
                    <td style="padding: 1rem; font-family: monospace;"><?php echo htmlspecialchars($book['isbn']); ?></td>
                    <td style="padding: 1rem; text-align: center;"><?php echo $book['total_stock']; ?></td>
                    <td style="padding: 1rem; text-align: center;">
                        <span style="padding: 0.25rem 0.5rem; border-radius: 4px; background: <?php echo $book['total_available'] > 0 ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $book['total_available'] > 0 ? '#155724' : '#721c24'; ?>;">
                            <?php echo $book['total_available']; ?>
                        </span>
                    </td>
                    <td style="padding: 1rem; text-align: right;">
                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                            <a href="edit_book.php?id=<?php echo $book['book_id']; ?>" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: var(--border); color: var(--text);">Edit</a>
                            <form action="delete_book.php" method="POST" onsubmit="return confirm('Are you sure?')">
                                <input type="hidden" name="id" value="<?php echo $book['book_id']; ?>">
                                <button type="submit" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: #dc3545;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
