<?php

session_start();
require_once "db.php";

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit();
}

$search = trim($_GET["search"] ?? "");

if ($search !== "") {

    $stmt = $conn->prepare(
        "SELECT book_id, book_no, book_name, author, created_at
         FROM books
         WHERE book_no LIKE ?
            OR book_name LIKE ?
            OR author LIKE ?
         ORDER BY book_id DESC"
    );

    $like = "%" . $search . "%";

    $stmt->bind_param(
        "sss",
        $like,
        $like,
        $like
    );

    $stmt->execute();

    $books = $stmt->get_result();

} else {

    $books = $conn->query(
        "SELECT book_id, book_no, book_name, author, created_at
         FROM books
         ORDER BY book_id DESC"
    );
}

$countResult = $conn->query(
    "SELECT COUNT(*) AS total FROM books"
);

$totalBooks = $countResult->fetch_assoc()["total"];

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>All Books - Library Management System</title>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;

    background:
    linear-gradient(
        rgba(4, 20, 45, 0.90),
        rgba(4, 20, 45, 0.95)
    ),
    url("https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=1800&q=85")
    center / cover fixed no-repeat;

    color: #fff;
    min-height: 100vh;
}

/* SIDEBAR */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;

    width: 250px;
    height: 100vh;

    background: rgba(5, 22, 48, 0.96);

    padding: 25px 15px;

    border-right: 1px solid rgba(255,255,255,0.1);

    z-index: 1000;
}

.logo {
    text-align: center;
    margin-bottom: 30px;
}

.logo i {
    font-size: 40px;
    color: #4da3ff;
}

.logo h2 {
    margin-top: 8px;
    font-size: 20px;
}

.menu a {
    display: flex;
    align-items: center;
    gap: 14px;

    color: #dceaff;

    text-decoration: none;

    padding: 13px 15px;

    margin-bottom: 8px;

    border-radius: 10px;

    transition: 0.3s;
}

.menu a:hover,
.menu a.active {
    background: #0d6efd;
    color: white;
    transform: translateX(5px);
}

.menu i {
    width: 22px;
}

/* MAIN */

.main {
    margin-left: 250px;
    padding: 30px;
}

/* TOP BAR */

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;

    background: rgba(255,255,255,0.08);

    padding: 18px 22px;

    border-radius: 15px;

    backdrop-filter: blur(10px);

    margin-bottom: 25px;
}

.topbar h1 {
    font-size: 25px;
}

.admin {
    display: flex;
    align-items: center;
    gap: 10px;
}

.admin i {
    color: #4da3ff;
    font-size: 25px;
}

/* PAGE HEADER */

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    margin-bottom: 25px;
}

.page-header h2 {
    font-size: 30px;
}

.page-header p {
    color: #cbd8e8;
    margin-top: 5px;
}

.add-btn {
    background: #0d6efd;
    color: white;

    padding: 12px 18px;

    border-radius: 10px;

    text-decoration: none;

    transition: 0.3s;
}

.add-btn:hover {
    background: #4da3ff;
    transform: translateY(-3px);
}

/* STAT */

.stat-card {
    background: rgba(255,255,255,0.09);

    border: 1px solid rgba(255,255,255,0.12);

    padding: 22px;

    border-radius: 15px;

    width: 220px;

    margin-bottom: 25px;

    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
}

.stat-card i {
    color: #4da3ff;
    font-size: 28px;
}

.stat-card h3 {
    font-size: 30px;
    margin-top: 10px;
}

/* SEARCH */

.search-box {
    display: flex;
    gap: 10px;

    margin-bottom: 20px;
}

.search-box input {
    flex: 1;

    padding: 13px;

    border-radius: 10px;

    border: none;

    outline: none;

    font-size: 15px;
}

.search-box button {
    background: #0d6efd;

    border: none;

    color: white;

    padding: 0 22px;

    border-radius: 10px;

    cursor: pointer;

    transition: 0.3s;
}

.search-box button:hover {
    background: #4da3ff;
}

/* TABLE */

.table-container {
    background: rgba(255,255,255,0.08);

    border-radius: 15px;

    padding: 20px;

    overflow-x: auto;

    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #0d6efd;
    padding: 15px;
    text-align: left;
}

td {
    padding: 14px;

    border-bottom: 1px solid rgba(255,255,255,0.1);

    color: #e5edf7;
}

tr:hover td {
    background: rgba(255,255,255,0.05);
}

/* BOOK NUMBER */

.book-number {
    display: inline-block;

    background: rgba(77,163,255,0.15);

    color: #4da3ff;

    padding: 6px 10px;

    border-radius: 7px;

    font-weight: bold;

    letter-spacing: 1px;
}

/* DELETE */

.delete-btn {
    display: inline-block;

    background: #dc3545;

    color: white;

    padding: 8px 12px;

    border-radius: 7px;

    text-decoration: none;

    transition: 0.3s;
}

.delete-btn:hover {
    background: #ff4d5d;
    transform: translateY(-2px);
}

/* EMPTY */

.empty {
    text-align: center;

    padding: 40px;

    color: #cbd8e8;
}

.empty i {
    font-size: 45px;

    color: #4da3ff;

    margin-bottom: 10px;
}

/* FOOTER */

footer {
    text-align: center;

    color: #b7c7da;

    padding: 30px 10px;
}

/* MOBILE */

@media(max-width: 800px) {

    .sidebar {
        width: 70px;
        padding: 20px 8px;
    }

    .logo h2,
    .menu span {
        display: none;
    }

    .logo i {
        font-size: 28px;
    }

    .menu a {
        justify-content: center;
    }

    .main {
        margin-left: 70px;
        padding: 15px;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .topbar h1 {
        font-size: 18px;
    }

    .admin span {
        display: none;
    }

}

</style>

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

    <div class="logo">

        <i class="fa-solid fa-book-open"></i>

        <h2>Library</h2>

    </div>

    <div class="menu">

        <a href="admin_dashboard.php">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>

        <a href="add_student.php">
            <i class="fa-solid fa-user-plus"></i>
            <span>Add Student</span>
        </a>

        <a href="students.php">
            <i class="fa-solid fa-users"></i>
            <span>All Students</span>
        </a>

        <a href="add_book.php">
            <i class="fa-solid fa-book-medical"></i>
            <span>Add Book</span>
        </a>

        <a href="books.php" class="active">
            <i class="fa-solid fa-book"></i>
            <span>All Books</span>
        </a>

        <a href="issue_book.php">
            <i class="fa-solid fa-arrow-right"></i>
            <span>Issue Book</span>
        </a>

        <a href="return_book.php">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Return Book</span>
        </a>

        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>

    </div>

</div>


<!-- MAIN -->

<div class="main">

    <div class="topbar">

        <h1>
            <i class="fa-solid fa-book"></i>
            All Books
        </h1>

        <div class="admin">

            <i class="fa-solid fa-user-shield"></i>

            <span>
                <?php echo htmlspecialchars($_SESSION["name"]); ?>
            </span>

        </div>

    </div>


    <div class="page-header">

        <div>

            <h2>Book Management</h2>

            <p>
                View and manage all library books.
            </p>

        </div>

        <a href="add_book.php" class="add-btn">

            <i class="fa-solid fa-plus"></i>

            Add New Book

        </a>

    </div>


    <div class="stat-card">

        <i class="fa-solid fa-book"></i>

        <h3>
            <?php echo $totalBooks; ?>
        </h3>

        <p>Total Books</p>

    </div>


    <!-- SEARCH -->

    <form method="GET" class="search-box">

        <input
            type="text"
            name="search"
            placeholder="Search Book No., Book Name or Author..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit">

            <i class="fa-solid fa-search"></i>

            Search

        </button>

    </form>


    <!-- TABLE -->

    <div class="table-container">

        <?php if ($books && $books->num_rows > 0): ?>

        <table>

            <thead>

                <tr>

                    <th>Book No.</th>

                    <th>Book Name</th>

                    <th>Author</th>

                    <th>Added</th>

                    <th>Action</th>

                </tr>

            </thead>

            <tbody>

            <?php while ($book = $books->fetch_assoc()): ?>

                <tr>

                    <td>

                        <span class="book-number">

                            <?php
                            echo htmlspecialchars(
                                $book["book_no"]
                            );
                            ?>

                        </span>

                    </td>

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $book["book_name"]
                        );
                        ?>

                    </td>

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $book["author"]
                        );
                        ?>

                    </td>

                    <td>

                        <?php
                        echo date(
                            "d M Y",
                            strtotime(
                                $book["created_at"]
                            )
                        );
                        ?>

                    </td>

                    <td>

                        <a
                            href="delete_book.php?id=<?php echo $book["book_id"]; ?>"
                            class="delete-btn"
                            onclick="return confirmDelete();"
                        >

                            <i class="fa-solid fa-trash"></i>

                            Delete

                        </a>

                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

        <?php else: ?>

            <div class="empty">

                <i class="fa-solid fa-book-open"></i>

                <h3>No Books Found</h3>

                <p>
                    Add a book to your library.
                </p>

            </div>

        <?php endif; ?>

    </div>


    <footer>

        © <?php echo date("Y"); ?>
        Library Management System

    </footer>

</div>


<script>

function confirmDelete() {

    return confirm(
        "Are you sure you want to delete this book?"
    );

}

</script>

</body>

</html>