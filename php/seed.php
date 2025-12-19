<?php
require_once 'includes/db.php';
require_once 'includes/config.php';

// --- PROGRESS BAR INITIALIZATION ---
if (ob_get_level() == 0) ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Seeder</title>
    <link rel="stylesheet" href="css/base.css">
    <style>
        .progress-container { width: 100%; background: var(--border); border-radius: 10px; margin: 2rem 0; overflow: hidden; height: 25px; position: relative; }
        .progress-bar { width: 0%; height: 100%; background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%); transition: width 0.3s ease; }
        .progress-text { position: absolute; width: 100%; text-align: center; line-height: 25px; font-weight: bold; color: var(--text); font-size: 0.8rem; text-shadow: 0 0 5px rgba(255,255,255,0.5); }
        .log-container { background: var(--card); padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.9rem; max-height: 300px; overflow-y: auto; border: 1px solid var(--border); }
    </style>
</head>
<body style="padding: 2rem; max-width: 800px; margin: 0 auto;">
    <h1>Database Initialization</h1>
    <div class="progress-container">
        <div id="pb" class="progress-bar"></div>
        <div id="pt" class="progress-text">0%</div>
    </div>
    <div id="status" style="margin-bottom: 1rem; font-weight: 500;">Initializing...</div>
    <div id="log" class="log-container"></div>

<?php
function updateProgress($percent, $msg, $log = null, $is_error = false) {
    $color = $is_error ? "#dc3545" : "linear-gradient(90deg, #4facfe 0%, #00f2fe 100%)";
    echo "<script>
        document.getElementById('pb').style.width = '$percent%';
        document.getElementById('pb').style.background = '$color';
        document.getElementById('pt').innerHTML = '$percent%';
        document.getElementById('status').innerHTML = '" . addslashes($msg) . "';
        " . ($log ? "document.getElementById('log').innerHTML += '<div" . ($is_error ? " style=\\'color:#dc3545;font-weight:bold;\\'" : "") . ">" . addslashes($log) . "</div>'; document.getElementById('log').scrollTop = document.getElementById('log').scrollHeight;" : "") . "
    </script>";
    ob_flush();
    flush();
}

updateProgress(0, "Starting initialization...", "Session started.");

try {
    // 1. Initialize SQL buffer
    $sql_script = "SET FOREIGN_KEY_CHECKS=0;\n";

    // DROP existing tables if requested
    if (isset($_GET['drop']) && $_GET['drop'] == 'true') {
        updateProgress(5, "Dropping existing tables...", "Checking for old data...");
        $r = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'library_db'");
        while ($row = $r->fetch_assoc()) {
            $sql_script .= "DROP TABLE IF EXISTS " . $row['table_name'] . ";\n";
        }
    }

    updateProgress(10, "Preparing table definitions...", "Schema generation...");

    // 1. Create Tables
    $tables = [
        "CREATE TABLE IF NOT EXISTS authors (
            author_id VARCHAR(8) PRIMARY KEY,
            author_name VARCHAR(100) NOT NULL
        );",
        "CREATE TABLE IF NOT EXISTS categories (
            category_id VARCHAR(8) PRIMARY KEY,
            category_name VARCHAR(100) NOT NULL
        );",
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
        );",
        "CREATE TABLE IF NOT EXISTS members (
            member_id VARCHAR(8) PRIMARY KEY,
            member_name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20)
        );",
        "CREATE TABLE IF NOT EXISTS staff (
            staff_id VARCHAR(8) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20)
        );",
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
        );"
    ];

    foreach ($tables as $sql) $sql_script .= $sql . "\n";

    // 2. Create Views
    $views = [
        "DROP VIEW IF EXISTS overdue_books;",
        "CREATE VIEW overdue_books AS
        SELECT l.loan_id, b.title, m.member_name, l.loan_date, l.due_date
        FROM loans l
        LEFT JOIN books b ON l.book_id = b.book_id
        LEFT JOIN members m ON l.member_id = m.member_id
        WHERE l.return_date IS NULL 
        AND CURDATE() > l.due_date;",

        "DROP VIEW IF EXISTS book_details_view;",
        "CREATE VIEW book_details_view AS
        SELECT b.book_id, b.title, a.author_name, c.category_name, b.isbn, b.total_stock, b.total_available
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.author_id
        LEFT JOIN categories c ON b.category_id = c.category_id;",

        "DROP VIEW IF EXISTS loan_details_view;",
        "CREATE VIEW loan_details_view AS
        SELECT l.loan_id, b.title AS book_title, m.member_name, s.name AS staff_name, s.username AS staff_username,
            l.loan_date, l.due_date, l.return_date, l.book_id, l.member_id
        FROM loans l
        LEFT JOIN books b ON l.book_id = b.book_id
        LEFT JOIN members m ON l.member_id = m.member_id
        LEFT JOIN staff s ON l.staff_id = s.staff_id;"
    ];

    foreach ($views as $sql) $sql_script .= $sql . "\n";

    updateProgress(15, "Generating staff accounts...", "Creating Admin and Librarian accounts.");

    // 3. Data Seeding
    $sql_script .= "START TRANSACTION;\n";

    // Staff
    $admin_id = substr(md5(uniqid(mt_rand(), true)), 0, 8);
    $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $sql_script .= "INSERT INTO staff (staff_id, name, username, password, role) VALUES ('$admin_id', 'Admin', 'admin', '$admin_pass', 'Admin');\n";

    $staff1_id = substr(md5(uniqid(mt_rand(), true)), 0, 8);
    $staff1_pass = password_hash('staff123', PASSWORD_DEFAULT);
    $sql_script .= "INSERT INTO staff (staff_id, name, username, password, role) VALUES ('$staff1_id', 'Alice Librarian', 'alice', '$staff1_pass', 'Staff');\n";
    $staff_ids = [$admin_id, $staff1_id];

    updateProgress(20, "Seeding categories and authors...", "Populating metadata.");

    // Categories
    $cat_names = ["Fiction", "Non-Fiction", "Science", "History", "Technology", "Biography", "Fantasy", "Mystery", "Romance", "Self-Help"];
    $category_ids = [];
    foreach ($cat_names as $name) {
        $id = substr(md5(uniqid(mt_rand(), true)), 0, 8);
        $sql_script .= "INSERT INTO categories (category_id, category_name) VALUES ('$id', '".addslashes($name)."');\n";
        $category_ids[] = $id;
    }

    // Authors
    $author_ids = [];
    for($i=1; $i<=50; $i++) {
        $id = substr(md5(uniqid(mt_rand(), true)), 0, 8);
        $name = "Author $i";
        $sql_script .= "INSERT INTO authors (author_id, author_name) VALUES ('$id', '$name');\n";
        $author_ids[] = $id;
    }

    updateProgress(25, "Seeding members...", "200 members generated.");

    // Members
    $member_ids = [];
    for($i=1; $i<=200; $i++) {
        $id = substr(md5(uniqid(mt_rand(), true)), 0, 8);
        $name = "Member $i";
        $email = "member$i@example.com";
        $phone = "555-" . mt_rand(1000, 9999);
        $sql_script .= "INSERT INTO members (member_id, member_name, email, phone) VALUES ('$id', '$name', '$email', '$phone');\n";
        $member_ids[] = $id;
    }

    // Books
    updateProgress(30, "Generating books (2500 total)...", "Please wait...");
    $book_titles = ["The Silent", "Echoes of", "Rising", "Lost in", "Secrets of", "Midnight", "Journey to", "Eternal", "Shadows", "Beyond the"];
    $book_nouns = ["Ocean", "Forest", "Empire", "Time", "Desert", "City", "Galaxy", "Soul", "Heart", "Legacy"];
    $book_ids = [];
    $out_of_stock_targets = [];

    for($i=1; $i<=2500; $i++) {
        $id = substr(md5(uniqid(mt_rand(), true)), 0, 8);
        $title = $book_titles[array_rand($book_titles)] . " " . $book_nouns[array_rand($book_nouns)] . " Part $i";
        $cat_id = $category_ids[array_rand($category_ids)];
        $auth_id = $author_ids[array_rand($author_ids)];
        $year = mt_rand(1950, 2024);
        $isbn = mt_rand(100, 999) . "-" . mt_rand(1000, 9999);
        $stock = mt_rand(5, 50);
        
        $sql_script .= "INSERT INTO books (book_id, title, category_id, author_id, published_year, isbn, total_stock, total_available) VALUES ('$id', '".addslashes($title)."', '$cat_id', '$auth_id', $year, '$isbn', $stock, $stock);\n";
        $book_ids[] = $id;

        // Pick 5 random books to deplete later
        if (count($out_of_stock_targets) < 5 && mt_rand(1, 100) > 95) {
            $out_of_stock_targets[] = ['id' => $id, 'stock' => $stock, 'title' => $title];
        }
        
        if ($i % 500 == 0) {
            $p = 30 + (round($i / 2500, 2) * 30);
            updateProgress($p, "Generating books ($i/2500)...", "$i books buffered.");
        }
    }

    // Loans
    updateProgress(60, "Generating loans (1500 total)...", "Simulating library history.");
    for($i=1; $i<=1500; $i++) {
        $book_id = $book_ids[array_rand($book_ids)];
        $loan_date = date('Y-m-d', strtotime('-' . mt_rand(1, 100) . ' days'));
        $due_date = date('Y-m-d', strtotime($loan_date . ' + 14 days'));
        
        $return_date = "NULL";
        if (mt_rand(1, 10) > 3) {
            $r_date = date('Y-m-d', strtotime($loan_date . ' + ' . mt_rand(1, 13) . ' days'));
            $return_date = "'$r_date'";
        } else {
            $sql_script .= "UPDATE books SET total_available = total_available - 1, total_loaned = total_loaned + 1 WHERE book_id = '$book_id';\n";
        }
        
        $loan_id = substr(md5(uniqid(mt_rand(), true)), 0, 8);
        $member_id = $member_ids[array_rand($member_ids)];
        $staff_id = $staff_ids[array_rand($staff_ids)];
        
        $sql_script .= "INSERT INTO loans (loan_id, book_id, member_id, staff_id, loan_date, due_date, return_date) VALUES ('$loan_id', '$book_id', '$member_id', '$staff_id', '$loan_date', '$due_date', $return_date);\n";

        if ($i % 300 == 0) {
            $p = 60 + (round($i / 1500, 2) * 20);
            updateProgress($p, "Generating loans ($i/1500)...", "$i loans buffered.");
        }
    }

    // Deplete targets for "Out of Stock" demo
    updateProgress(85, "Depleting target books...", "Making 5 books out of stock.");
    foreach ($out_of_stock_targets as $target) {
        for ($j = 0; $j < $target['stock']; $j++) {
            $loan_id = substr(md5(uniqid(mt_rand(), true)), 0, 8);
            $member_id = $member_ids[array_rand($member_ids)];
            $staff_id = $staff_ids[array_rand($staff_ids)];
            $loan_date = date('Y-m-d', strtotime('-' . mt_rand(1, 5) . ' days'));
            $due_date = date('Y-m-d', strtotime($loan_date . ' + 14 days'));
            
            $sql_script .= "INSERT INTO loans (loan_id, book_id, member_id, staff_id, loan_date, due_date, return_date) VALUES ('$loan_id', '{$target['id']}', '$member_id', '$staff_id', '$loan_date', '$due_date', NULL);\n";
            $sql_script .= "UPDATE books SET total_available = total_available - 1, total_loaned = total_loaned + 1 WHERE book_id = '{$target['id']}';\n";
        }
        updateProgress(85, "Depleting targets...", "Book '{$target['title']}' is now out of stock.");
    }

    $sql_script .= "COMMIT;\n";
    $sql_script .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // 4. Save to file
    updateProgress(90, "Finalizing SQL script...", "Writing to database.sql file.");
    file_put_contents('database.sql', $sql_script);

    // 5. Execute SQL
    updateProgress(95, "Executing SQL Script...", "Sending multi-query to MySQL server. Please wait...");
    if ($conn->multi_query($sql_script)) {
        do {
            if ($res = $conn->store_result()) {
                $res->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        if ($conn->errno) {
            throw new Exception($conn->error);
        } else {
            updateProgress(100, "Initialization Complete!", "Database is ready.");
            echo "<div class='alert' style='background:#d4edda; color:#155724; padding:1rem; margin-top:1rem;'>Database Initialized Successfully!</div>";
        }
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    updateProgress(100, "Initialization Failed!", "Error: " . $e->getMessage(), true);
    echo "<div class='alert' style='background:#f8d7da; color:#721c24; padding:1.5rem; margin-top:1rem; border: 2px solid #dc3545;'>";
    echo "<h3 style='margin-bottom:0.5rem;'>Critical Seeding Error</h3>";
    echo "<p style='margin-bottom:1rem;'><strong>Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='margin-bottom:1rem;'>This usually happens if the database already contains data. Please use the <strong>Reset & Re-seed</strong> button below to clear all tables and start fresh.</p>";
    echo "</div>";
}

echo "<div style='margin-top:2rem; display:flex; gap:1rem;'>";
echo "<a href='seed.php?drop=true' class='btn' style='background:var(--border); color:var(--text);'>Reset & Re-seed</a>";
echo "<a href='index.php' class='btn'>Back to Dashboard</a>";
echo "</div>";
?>
</body>
</html>
