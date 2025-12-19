<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$member = null;

if ($id) {
    $stmt = $conn->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['member_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE members SET member_name=?, email=?, phone=? WHERE member_id=?");
        $stmt->bind_param("ssss", $name, $email, $phone, $id);
        $stmt->execute();
        $_SESSION['flash'] = ["Member updated!"];
    } else {
        $new_id = substr(uniqid(), -8);
        $stmt = $conn->prepare("INSERT INTO members (member_id, member_name, email, phone) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $new_id, $name, $email, $phone);
        $stmt->execute();
        $_SESSION['flash'] = ["Member added!"];
    }
    header("Location: members.php");
    exit();
}
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h1><?php echo $id ? "Edit Member" : "Add New Member"; ?></h1>
    
    <form method="POST" style="margin-top: 2rem;">
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Full Name</label>
            <input type="text" name="member_name" value="<?php echo $member ? htmlspecialchars($member['member_name']) : ''; ?>" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Email Address</label>
            <input type="email" name="email" value="<?php echo $member ? htmlspecialchars($member['email']) : ''; ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 2rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Phone Number</label>
            <input type="text" name="phone" value="<?php echo $member ? htmlspecialchars($member['phone']) : ''; ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn" style="flex: 1;"><?php echo $id ? "Update Member" : "Add Member"; ?></button>
            <a href="members.php" class="btn" style="background: var(--border); color: var(--text);">Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
