<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $id = substr(uniqid(), -8);
    $stmt = $conn->prepare("INSERT INTO categories (category_id, category_name) VALUES (?, ?)");
    $stmt->bind_param("ss", $id, $name);
    $stmt->execute();
    $_SESSION['flash'] = ["Category added!"];
}

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>Manage Categories</h1>
        <button onclick="document.getElementById('add-form').style.display='block'" class="btn">Add New Category</button>
    </div>

    <div id="add-form" style="display: none; margin-bottom: 2rem; padding: 1.5rem; border: 1px solid var(--border); border-radius: 12px;">
        <h3>Add New Category</h3>
        <form method="POST" style="margin-top: 1rem; display: flex; gap: 1rem;">
            <input type="text" name="name" required placeholder="Category Name" style="flex: 1; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
            <button type="submit" class="btn">Save Category</button>
            <button type="button" onclick="this.parentElement.parentElement.style.display='none'" class="btn" style="background: var(--border); color: var(--text);">Cancel</button>
        </form>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem;">
        <?php while($cat = $categories->fetch_assoc()): ?>
        <div class="card" style="margin: 0; padding: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: 500; font-size: 1.1rem;"><?php echo htmlspecialchars($cat['category_name']); ?></span>
            <form action="delete_category.php" method="POST" onsubmit="return confirm('Delete this category?')">
                <input type="hidden" name="id" value="<?php echo $cat['category_id']; ?>">
                <button type="submit" style="background: none; border: none; color: #dc3545; cursor: pointer; font-size: 0.9rem;">Delete</button>
            </form>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
