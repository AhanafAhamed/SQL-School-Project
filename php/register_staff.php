<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] != 'Admin') {
    $_SESSION['flash'] = ["Only administrators can register new staff."];
    header("Location: index.php");
    exit();
}

require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $new_id = substr(uniqid(), -8);

    $stmt = $conn->prepare("SELECT * FROM staff WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $error = "Username already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO staff (staff_id, name, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $new_id, $name, $username, $password, $role);
        $stmt->execute();
        $_SESSION['flash'] = ["Staff member registered successfully!"];
        header("Location: index.php");
        exit();
    }
}
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h1>Register New Staff</h1>
    
    <?php if (isset($error)): ?>
    <div class="alert" style="margin-top: 1rem;"><span><?php echo $error; ?></span></div>
    <?php endif; ?>

    <form method="POST" style="margin-top: 2rem;">
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Full Name</label>
            <input type="text" name="name" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Username</label>
            <input type="text" name="username" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Password</label>
            <input type="password" name="password" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 2rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Role</label>
            <select name="role" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
                <option value="Staff">Staff</option>
                <option value="Admin">Administrator</option>
            </select>
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn" style="flex: 1;">Register Staff</button>
            <a href="index.php" class="btn" style="background: var(--border); color: var(--text);">Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
