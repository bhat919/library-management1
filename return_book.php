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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $issue_id = (int)($_POST["issue_id"] ?? 0);

    if ($issue_id <= 0) {

        $error = "Invalid book record.";

    } else {

        $stmt = $conn->prepare(
            "UPDATE issued_books
             SET status = 'Returned',
                 return_date = CURDATE()
             WHERE issue_id = ?
             AND status = 'Issued'"
        );

        $stmt->bind_param(
            "i",
            $issue_id
        );

        if ($stmt->execute()) {

            if ($stmt->affected_rows > 0) {

                $success = "Book returned successfully.";

            } else {

                $error = "Book is already returned or not found.";

            }

        } else {

            $error = "Unable to return book.";

        }

        $stmt->close();
    }
}


$stmt = $conn->prepare(
    "SELECT
        issued_books.issue_id,
        issued_books.issue_date,
        users.student_id,
        users.name,
        books.book_no,
        books.book_name,
        books.author
     FROM issued_books

     INNER JOIN users
        ON issued_books.user_id = users.user_id

     INNER JOIN books
        ON issued_books.book_id = books.book_id

     WHERE issued_books.status = 'Issued'

     ORDER BY issued_books.issue_id DESC"
);

$stmt->execute();

$issuedBooks = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Return Book</title>

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
}

.topbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 18px 22px;

    background: rgba(255,255,255,.08);

    border-radius: 15px;

    margin-bottom: 25px;
}

.admin {

    display: flex;

    align-items: center;

    gap: 10px;
}

.admin i {

    color: #4da3ff;

    font-size: 24px;
}

.alert {

    padding: 14px;

    border-radius: 10px;

    margin-bottom: 20px;
}

.success {

    background: rgba(25,135,84,.2);

    color: #9ff0c5;
}

.error {

    background: rgba(220,53,69,.2);

    color: #ffb3bb;
}

.table-container {

    background: rgba(255,255,255,.08);

    padding: 20px;

    border-radius: 15px;

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;
}

th {

    background: #0d6efd;

    padding: 14px;

    text-align: left;

    white-space: nowrap;
}

td {

    padding: 14px;

    border-bottom:
    1px solid rgba(255,255,255,.1);

    white-space: nowrap;
}

tr:hover td {

    background:
    rgba(255,255,255,.05);
}

.book-number {

    color: #4da3ff;

    font-weight: bold;

    background:
    rgba(77,163,255,.15);

    padding: 6px 10px;

    border-radius: 7px;

    letter-spacing: 1px;
}

.return-btn {

    border: none;

    background: #198754;

    color: white;

    padding: 8px 12px;

    border-radius: 7px;

    cursor: pointer;

    transition: .3s;
}

.return-btn:hover {

    background: #20a464;

    transform: translateY(-2px);
}

.empty {

    text-align: center;

    padding: 50px;

    color: #cbd8e8;
}

.empty i {

    font-size: 45px;

    color: #4da3ff;

    margin-bottom: 10px;
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

        <a href="issue_book.php">
            <i class="fa-solid fa-arrow-right"></i>
            <span>Issue Book</span>
        </a>

        <a href="return_book.php" class="active">
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
            <i class="fa-solid fa-arrow-left"></i>
            Return Book
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


    <?php if ($success): ?>

        <div class="alert success">

            <i class="fa-solid fa-circle-check"></i>

            <?php echo htmlspecialchars($success); ?>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert error">

            <i class="fa-solid fa-circle-exclamation"></i>

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <div class="table-container">

        <?php if ($issuedBooks->num_rows > 0): ?>

        <table>

            <thead>

                <tr>

                    <th>Student ID</th>

                    <th>Student</th>

                    <th>Book No.</th>

                    <th>Book Name</th>

                    <th>Author</th>

                    <th>Issue Date</th>

                    <th>Action</th>

                </tr>

            </thead>

            <tbody>

            <?php while ($row = $issuedBooks->fetch_assoc()): ?>

                <tr>

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $row["student_id"]
                        );
                        ?>

                    </td>

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $row["name"]
                        );
                        ?>

                    </td>

                    <td>

                        <span class="book-number">

                            <?php
                            echo htmlspecialchars(
                                $row["book_no"]
                            );
                            ?>

                        </span>

                    </td>

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $row["book_name"]
                        );
                        ?>

                    </td>

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $row["author"]
                        );
                        ?>

                    </td>

                    <td>

                        <?php
                        echo date(
                            "d M Y",
                            strtotime(
                                $row["issue_date"]
                            )
                        );
                        ?>

                    </td>

                    <td>

                        <form
                            method="POST"
                            onsubmit="return confirmReturn();"
                        >

                            <input
                                type="hidden"
                                name="issue_id"
                                value="<?php
                                echo $row["issue_id"];
                                ?>"
                            >

                            <button
                                type="submit"
                                class="return-btn"
                            >

                                <i class="fa-solid fa-rotate-left"></i>

                                Return

                            </button>

                        </form>

                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

        <?php else: ?>

            <div class="empty">

                <i class="fa-solid fa-book-open"></i>

                <h2>No Currently Issued Books</h2>

                <p>
                    There are no books waiting for return.
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

function confirmReturn() {

    return confirm(
        "Are you sure you want to return this book?"
    );

}

</script>

</body>

</html>