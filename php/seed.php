<?php
require_once 'includes/db.php';
require_once 'includes/config.php';

echo "<h2>Initializing Database...</h2>";

//if ?drop=true then drop all tables
if (isset($_GET['drop']) && $_GET['drop'] == 'true') {
    $conn->query("SET FOREIGN_KEY_CHECKS=0;");
    $r = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'library_db'");
    while ($row = $r->fetch_assoc()) {
        $conn->query("DROP TABLE IF EXISTS " . $row['table_name'] . " CASCADE");
    }
    $conn->query("SET FOREIGN_KEY_CHECKS=1;");
    echo "Tables dropped.<br>";
}

// 1. Create Tables
$tables = [
    "CREATE TABLE IF NOT EXISTS authors (
        author_id VARCHAR(8) PRIMARY KEY,
        author_name VARCHAR(100) NOT NULL
    )",
    "CREATE TABLE IF NOT EXISTS categories (
        category_id VARCHAR(8) PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL
    )",
    "CREATE TABLE IF NOT EXISTS books (
        book_id VARCHAR(8) PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        category_id VARCHAR(8),
        author_id VARCHAR(8),
        published_year INT,
        isbn VARCHAR(20),
        total_stock INT DEFAULT 0,
        total_available INT DEFAULT 0,
        total_loaned INT DEFAULT 0,
        FOREIGN KEY (category_id) REFERENCES categories(category_id),
        FOREIGN KEY (author_id) REFERENCES authors(author_id)
    )",
    "CREATE TABLE IF NOT EXISTS members (
        member_id VARCHAR(8) PRIMARY KEY,
        member_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20)
    )",
    "CREATE TABLE IF NOT EXISTS staff (
        staff_id VARCHAR(8) PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20)
    )",
    "CREATE TABLE IF NOT EXISTS loans (
        loan_id VARCHAR(8) PRIMARY KEY,
        loan_date DATE,
        due_date DATE NOT NULL,
        return_date DATE,
        staff_id VARCHAR(8),
        book_id VARCHAR(8),
        member_id VARCHAR(8),
        FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
        FOREIGN KEY (book_id) REFERENCES books(book_id),
        FOREIGN KEY (member_id) REFERENCES members(member_id)
    )"
];

foreach ($tables as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table created/exists.<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

// 2. Create Views
$views = [
    "DROP VIEW IF EXISTS overdue_books",
    "CREATE VIEW overdue_books AS
    SELECT l.loan_id, b.title, m.member_name, l.loan_date, l.due_date
    FROM loans l
    LEFT JOIN books b ON l.book_id = b.book_id
    LEFT JOIN members m ON l.member_id = m.member_id
    WHERE l.return_date IS NULL 
    AND CURDATE() > l.due_date",

    "DROP VIEW IF EXISTS book_details_view",
    "CREATE VIEW book_details_view AS
    SELECT b.book_id, b.title, a.author_name, c.category_name, b.isbn, b.total_stock, b.total_available
    FROM books b
    LEFT JOIN authors a ON b.author_id = a.author_id
    LEFT JOIN categories c ON b.category_id = c.category_id",

    "DROP VIEW IF EXISTS loan_details_view",
    "CREATE VIEW loan_details_view AS
    SELECT l.loan_id, b.title AS book_title, m.member_name, s.name AS staff_name, s.username AS staff_username,
           l.loan_date, l.due_date, l.return_date, l.book_id, l.member_id
    FROM loans l
    LEFT JOIN books b ON l.book_id = b.book_id
    LEFT JOIN members m ON l.member_id = m.member_id
    LEFT JOIN staff s ON l.staff_id = s.staff_id"
];

foreach ($views as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "View created/updated.<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

// 3. Seeding Data
echo "<h2>Seeding Initial Data...</h2>";

$conn->begin_transaction();

// Configuration
$num_authors = 50;
$num_books = 2500;
$num_members = 200;
$num_loans = 1500;

// Helper to generate a unique 8-char ID
if (!function_exists('generate_id')) {
    function generate_id() {
        return substr(md5(uniqid(mt_rand(), true)), 0, 8);
    }
}

// Check if staff exists
$result = $conn->query("SELECT staff_id FROM staff");
$staff_ids = [];
if ($result->num_rows == 0) {
    $admin_id = generate_id();
    $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO staff (staff_id, name, username, password, role) VALUES ('$admin_id', 'Admin', 'admin', '$admin_pass', 'Admin')");
    
    $staff1_id = generate_id();
    $staff1_pass = password_hash('staff123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO staff (staff_id, name, username, password, role) VALUES ('$staff1_id', 'Alice Librarian', 'alice', '$staff1_pass', 'Staff')");
    $staff_ids = [$admin_id, $staff1_id];
    echo "Staff accounts created.<br>";
} else {
    while($row = $result->fetch_assoc()) $staff_ids[] = $row['staff_id'];
}

// Categories
$cat_names = ["Fiction", "Non-Fiction", "Science", "History", "Technology", "Biography", "Fantasy", "Mystery", "Romance", "Self-Help"];
$category_ids = [];
foreach ($cat_names as $name) {
    $id = generate_id();
    $conn->query("INSERT INTO categories (category_id, category_name) VALUES ('$id', '$name')");
    $category_ids[] = $id;
}
echo "Categories seeded.<br>";

// Authors
$author_ids = [];
for($i=1; $i<=$num_authors; $i++) {
    $id = generate_id();
    $name = "Author $i";
    $conn->query("INSERT INTO authors (author_id, author_name) VALUES ('$id', '$name')");
    $author_ids[] = $id;
}
echo "Authors seeded.<br>";

// Members
$member_ids = [];
for($i=1; $i<=$num_members; $i++) {
    $id = generate_id();
    $name = "Member $i";
    $email = "member$i@example.com";
    $phone = "555-" . mt_rand(1000, 9999);
    $conn->query("INSERT INTO members (member_id, member_name, email, phone) VALUES ('$id', '$name', '$email', '$phone')");
    $member_ids[] = $id;
}
echo "Members seeded.<br>";

// Books
$book_titles = ["The Silent", "Echoes of", "Rising", "Lost in", "Secrets of", "Midnight", "Journey to", "Eternal", "Shadows", "Beyond the"];
$book_nouns = ["Ocean", "Forest", "Empire", "Time", "Desert", "City", "Galaxy", "Soul", "Heart", "Legacy"];
$book_ids = [];

$stmt = $conn->prepare("INSERT INTO books (book_id, title, category_id, author_id, published_year, isbn, total_stock, total_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

for($i=1; $i<=$num_books; $i++) {
    $id = generate_id();
    $title = $book_titles[array_rand($book_titles)] . " " . $book_nouns[array_rand($book_nouns)] . " Part $i";
    $cat_id = $category_ids[array_rand($category_ids)];
    $auth_id = $author_ids[array_rand($author_ids)];
    $year = mt_rand(1950, 2024);
    $isbn = mt_rand(100, 999) . "-" . mt_rand(1000, 9999);
    $stock = mt_rand(5, 50);
    
    $stmt->bind_param("ssssssii", $id, $title, $cat_id, $auth_id, $year, $isbn, $stock, $stock);
    $stmt->execute();
    $book_ids[] = $id;
}
echo "$num_books Books seeded.<br>";

// Loans
$loan_stmt = $conn->prepare("INSERT INTO loans (loan_id, book_id, member_id, staff_id, loan_date, due_date, return_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
$update_book_stmt = $conn->prepare("UPDATE books SET total_available = total_available - 1, total_loaned = total_loaned + 1 WHERE book_id = ?");

$loans_created = 0;
for($i=0; $i<$num_loans; $i++) {
    $book_id = $book_ids[array_rand($book_ids)];
    
    $loan_date = date('Y-m-d', strtotime('-' . mt_rand(1, 100) . ' days'));
    $due_date = date('Y-m-d', strtotime($loan_date . ' + 14 days'));
    
    $return_date = null;
    if (mt_rand(1, 10) > 3) {
        $return_date = date('Y-m-d', strtotime($loan_date . ' + ' . mt_rand(1, 13) . ' days'));
    } else {
        $update_book_stmt->bind_param("s", $book_id);
        $update_book_stmt->execute();
    }
    
    $loan_id = generate_id();
    $member_id = $member_ids[array_rand($member_ids)];
    $staff_id = $staff_ids[array_rand($staff_ids)];
    
    $loan_stmt->bind_param("sssssss", $loan_id, $book_id, $member_id, $staff_id, $loan_date, $due_date, $return_date);
    $loan_stmt->execute();
    $loans_created++;
}
echo "$loans_created Loans seeded.<br>";

$conn->commit();

echo "<h3>Database Seeded Successfully!</h3>";
echo "<p>To reset and re-seed, use: <a href='seed.php?drop=true'>seed.php?drop=true</a></p>";
echo "<a href='index.php'>Go to Dashboard</a>";
?>
