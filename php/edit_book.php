<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$book = null;

if ($id) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $cat_id = $_POST['category_id'];
    $auth_id = $_POST['author_id'];
    $year = $_POST['published_year'];
    $isbn = $_POST['isbn'];
    $stock = $_POST['total_stock'];

    if ($id) {
        // Update existing
        $old_stock = $book['total_stock'];
        $active_loans = $old_stock - $book['total_available'];
        $new_available = $stock - $active_loans;

        $stmt = $conn->prepare("UPDATE books SET title=?, category_id=?, author_id=?, published_year=?, isbn=?, total_stock=?, total_available=? WHERE book_id=?");
        $stmt->bind_param("sssssiis", $title, $cat_id, $auth_id, $year, $isbn, $stock, $new_available, $id);
        $stmt->execute();
        $_SESSION['flash'] = ["Book updated!"];
    } else {
        // Insert new
        $new_id = substr(uniqid(), -8);
        $stmt = $conn->prepare("INSERT INTO books (book_id, title, category_id, author_id, published_year, isbn, total_stock, total_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssii", $new_id, $title, $cat_id, $auth_id, $year, $isbn, $stock, $stock);
        $stmt->execute();
        $_SESSION['flash'] = ["Book added!"];
    }
    header("Location: books.php");
    exit();
}

$authors = $conn->query("SELECT * FROM authors ORDER BY author_name");
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h1><?php echo $id ? "Edit Book" : "Add New Book"; ?></h1>
    
    <form method="POST" style="margin-top: 2rem;">
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Title</label>
            <input type="text" name="title" value="<?php echo $book ? htmlspecialchars($book['title']) : ''; ?>" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem;">Author</label>
                <select name="author_id" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
                    <?php while($author = $authors->fetch_assoc()): ?>
                    <option value="<?php echo $author['author_id']; ?>" <?php echo ($book && $book['author_id'] == $author['author_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($author['author_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem;">Category</label>
                <select name="category_id" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
                    <?php while($cat = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $cat['category_id']; ?>" <?php echo ($book && $book['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem;">Published Year</label>
                <input type="number" name="published_year" value="<?php echo $book ? $book['published_year'] : date('Y'); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem;">ISBN</label>
                <input type="text" name="isbn" value="<?php echo $book ? htmlspecialchars($book['isbn']) : ''; ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="margin-bottom: 2rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Total Stock</label>
            <input type="number" name="total_stock" value="<?php echo $book ? $book['total_stock'] : '1'; ?>" min="1" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn" style="flex: 1;"><?php echo $id ? "Update Book" : "Add Book"; ?></button>
            <a href="books.php" class="btn" style="background: var(--border); color: var(--text);">Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
