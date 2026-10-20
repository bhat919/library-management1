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

$error = "";
$success = "";

$students = $conn->query(
    "SELECT user_id, student_id, name, username
     FROM users
     WHERE role = 'student'
     ORDER BY name ASC"
);

$books = $conn->query(
    "SELECT book_id, book_no, book_name, author
     FROM books
     WHERE book_id NOT IN (
         SELECT book_id
         FROM issued_books
         WHERE status = 'Issued'
     )
     ORDER BY book_name ASC"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = (int)($_POST["user_id"] ?? 0);
    $book_id = (int)($_POST["book_id"] ?? 0);

    if ($user_id <= 0 || $book_id <= 0) {

        $error = "Please select a student and a book.";

    } else {

        $stmt = $conn->prepare(
            "SELECT issue_id
             FROM issued_books
             WHERE user_id = ?
             AND book_id = ?
             AND status = 'Issued'
             LIMIT 1"
        );

        $stmt->bind_param(
            "ii",
            $user_id,
            $book_id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $error = "This student already has this book.";

            $stmt->close();

        } else {

            $stmt->close();

            $stmt = $conn->prepare(
                "SELECT issue_id
                 FROM issued_books
                 WHERE book_id = ?
                 AND status = 'Issued'
                 LIMIT 1"
            );

            $stmt->bind_param(
                "i",
                $book_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $error = "This book is already issued.";

                $stmt->close();

            } else {

                $stmt->close();

                $stmt = $conn->prepare(
                    "INSERT INTO issued_books
                    (book_id, user_id, issue_date, status)
                    VALUES (?, ?, CURDATE(), 'Issued')"
                );

                $stmt->bind_param(
                    "ii",
                    $book_id,
                    $user_id
                );

                if ($stmt->execute()) {

                    $success = "Book issued successfully.";

                } else {

                    $error = "Unable to issue book.";

                }

                $stmt->close();

                /* Refresh available books */

                $books = $conn->query(
                    "SELECT book_id, book_no, book_name, author
                     FROM books
                     WHERE book_id NOT IN (
                         SELECT book_id
                         FROM issued_books
                         WHERE status = 'Issued'
                     )
                     ORDER BY book_name ASC"
                );
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Issue Book</title>

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

    min-height: 100vh;

    background:
    linear-gradient(
        rgba(4,20,45,.90),
        rgba(4,20,45,.95)
    ),
    url("https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=1800&q=85")
    center/cover fixed no-repeat;

    color: white;
}

.sidebar {

    position: fixed;

    left: 0;
    top: 0;

    width: 250px;

    height: 100vh;

    background: rgba(5,22,48,.97);

    padding: 25px 15px;

    border-right: 1px solid rgba(255,255,255,.1);
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
}

.menu a {

    display: flex;

    align-items: center;

    gap: 14px;

    padding: 13px 15px;

    margin-bottom: 8px;

    color: #dceaff;

    text-decoration: none;

    border-radius: 10px;

    transition: .3s;
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

.main {

    margin-left: 250px;

    padding: 30px;

    min-height: 100vh;
}

.topbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 18px 22px;

    background: rgba(255,255,255,.08);

    border-radius: 15px;

    margin-bottom: 30px;
}

.admin {

    display: flex;

    gap: 10px;

    align-items: center;
}

.admin i {

    color: #4da3ff;

    font-size: 24px;
}

.card {

    max-width: 750px;

    background: rgba(255,255,255,.09);

    border: 1px solid rgba(255,255,255,.12);

    padding: 30px;

    border-radius: 18px;

    box-shadow: 0 15px 40px rgba(0,0,0,.3);
}

.card h2 {

    margin-bottom: 10px;
}

.card p {

    color: #cbd8e8;

    margin-bottom: 25px;
}

.form-group {

    margin-bottom: 20px;
}

.form-group label {

    display: block;

    margin-bottom: 8px;

    font-weight: bold;
}

.form-group label i {

    color: #4da3ff;

    margin-right: 6px;
}

select {

    width: 100%;

    padding: 14px;

    border-radius: 10px;

    border: none;

    outline: none;

    font-size: 15px;

    background: white;

    color: #222;

    cursor: pointer;
}

button {

    width: 100%;

    padding: 14px;

    border: none;

    border-radius: 10px;

    background: #0d6efd;

    color: white;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;

    transition: .3s;
}

button:hover {

    background: #4da3ff;

    transform: translateY(-2px);
}

.alert {

    padding: 13px;

    border-radius: 10px;

    margin-bottom: 20px;
}

.error {

    background: rgba(220,53,69,.2);

    color: #ffb3bb;
}

.success {

    background: rgba(25,135,84,.2);

    color: #9ff0c5;
}

footer {

    text-align: center;

    color: #b7c7da;

    padding: 30px;
}

@media(max-width:800px) {

    .sidebar {

        width: 70px;

        padding: 20px 8px;
    }

    .logo h2,
    .menu span {

        display: none;
    }

    .menu a {

        justify-content: center;
    }

    .main {

        margin-left: 70px;

        padding: 15px;
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

        <a href="books.php">
            <i class="fa-solid fa-book"></i>
            <span>All Books</span>
        </a>

        <a href="issue_book.php" class="active">
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

<div class="main">

    <div class="topbar">

        <h1>
            <i class="fa-solid fa-arrow-right"></i>
            Issue Book
        </h1>

        <div class="admin">

            <i class="fa-solid fa-user-shield"></i>

            <span>
                <?php
                echo htmlspecialchars(
                    $_SESSION["name"]
                );
                ?>
            </span>

        </div>

    </div>


    <div class="card">

        <h2>
            <i class="fa-solid fa-book-open"></i>
            Issue a Book
        </h2>

        <p>
            Select a student and an available book.
        </p>


        <?php if ($error): ?>

            <div class="alert error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <?php if ($success): ?>

            <div class="alert success">

                <i class="fa-solid fa-circle-check"></i>

                <?php echo htmlspecialchars($success); ?>

            </div>

        <?php endif; ?>


        <form method="POST" onsubmit="return validateForm();">


            <div class="form-group">

                <label>

                    <i class="fa-solid fa-user"></i>

                    Select Student

                </label>

                <select name="user_id" id="user_id">

                    <option value="">
                        -- Select Student --
                    </option>

                    <?php if ($students && $students->num_rows > 0): ?>

                        <?php while ($student = $students->fetch_assoc()): ?>

                            <option
                                value="<?php echo $student["user_id"]; ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $student["student_id"]
                                );
                                ?>

                                -

                                <?php
                                echo htmlspecialchars(
                                    $student["name"]
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>

            </div>


            <div class="form-group">

                <label>

                    <i class="fa-solid fa-book"></i>

                    Select Book

                </label>

                <select name="book_id" id="book_id">

                    <option value="">
                        -- Select Available Book --
                    </option>

                    <?php if ($books && $books->num_rows > 0): ?>

                        <?php while ($book = $books->fetch_assoc()): ?>

                            <option
                                value="<?php echo $book["book_id"]; ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $book["book_no"]
                                );
                                ?>

                                -

                                <?php
                                echo htmlspecialchars(
                                    $book["book_name"]
                                );
                                ?>

                                |

                                <?php
                                echo htmlspecialchars(
                                    $book["author"]
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <option value="" disabled>
                            No available books
                        </option>

                    <?php endif; ?>

                </select>

            </div>


            <button type="submit">

                <i class="fa-solid fa-arrow-right"></i>

                Issue Book

            </button>

        </form>

    </div>


    <footer>

        © <?php echo date("Y"); ?>
        Library Management System

    </footer>

</div>


<script>

function validateForm() {

    const student =
        document.getElementById("user_id").value;

    const book =
        document.getElementById("book_id").value;

    if (student === "") {

        alert("Please select a student.");

        return false;
    }

    if (book === "") {

        alert("Please select a book.");

        return false;
    }

    return true;
}

</script>

</body>

</html>