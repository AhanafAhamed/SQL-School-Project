<?php
$pageTitle = "Books";
require_once 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch authors and categories for filters
$authors_list = $conn->query("SELECT * FROM authors ORDER BY author_name");
$categories_list = $conn->query("SELECT * FROM categories ORDER BY category_name");

$search = $_GET['search'] ?? '';
$author_id = $_GET['author_id'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$stock_status = $_GET['stock_status'] ?? '';

// Check if any filter is active
$is_filtering = !empty($search) || !empty($author_id) || !empty($category_id) || !empty($stock_status);

// Build dynamic query
$query = "SELECT * FROM book_details_view WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (title LIKE ? OR isbn LIKE ?)";
    $like_param = "%$search%";
    $params[] = $like_param;
    $params[] = $like_param;
    $types .= "ss";
}
if ($author_id) {
    $query .= " AND author_id = ?";
    $params[] = $author_id;
    $types .= "s";
}
if ($category_id) {
    $query .= " AND category_id = ?";
    $params[] = $category_id;
    $types .= "s";
}
if ($stock_status === 'in_stock') {
    $query .= " AND total_available > 0";
} elseif ($stock_status === 'out_of_stock') {
    $query .= " AND total_available = 0";
}

$query .= " ORDER BY title ASC";

$stmt = $conn->prepare($query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="card" style="margin-bottom: 2rem;">
    <div class="filter-header" onclick="toggleFilters('book-filters')">
        <h3>Filter Inventory</h3>
        <span class="filter-toggle-icon" id="toggle-icon-book-filters" style="font-size: 1.5rem;">▼</span>
    </div>
    <div id="book-filters" class="filter-content <?php echo $is_filtering ? '' : 'collapsed'; ?>">
        <form method="GET" class="filter-grid">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" placeholder="Title or ISBN..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label>Author</label>
                <select name="author_id">
                    <option value="">All Authors</option>
                    <?php while($a = $authors_list->fetch_assoc()): ?>
                        <option value="<?php echo $a['author_id']; ?>" <?php echo $author_id == $a['author_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($a['author_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Category</label>
                <select name="category_id">
                    <option value="">All Categories</option>
                    <?php while($c = $categories_list->fetch_assoc()): ?>
                        <option value="<?php echo $c['category_id']; ?>" <?php echo $category_id == $c['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Availability</label>
                <select name="stock_status">
                    <option value="">All</option>
                    <option value="in_stock" <?php echo $stock_status == 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                    <option value="out_of_stock" <?php echo $stock_status == 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn">Filter</button>
                <a href="books.php" class="btn" style="background: var(--border); color: var(--fg); text-align: center; text-decoration: none;">Clear</a>
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
        <h1>Books Inventory</h1>
        <a href="edit_book.php" class="btn">Add New Book</a>
    </div>


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
                        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; align-items: center;">
                            <a href="edit_book.php?id=<?php echo $book['book_id']; ?>" class="btn-action btn-edit">Edit</a>
                            <form action="delete_book.php" method="POST" onsubmit="return confirm('Are you sure?')">
                                <input type="hidden" name="id" value="<?php echo $book['book_id']; ?>">
                                <button type="submit" class="btn-action btn-delete">Delete</button>
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
