<?php

session_start();
require_once "db.php";

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];


/* STUDENT PROFILE */

$stmt = $conn->prepare(
    "SELECT
        user_id,
        student_id,
        name,
        username,
        email
     FROM users
     WHERE user_id = ?
     AND role = 'student'
     LIMIT 1"
);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();

$stmt->close();


/* CURRENTLY ISSUED */

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE user_id = ?
     AND status = 'Issued'"
);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$currentIssued =
    $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();


/* TOTAL HISTORY */

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE user_id = ?"
);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$totalHistory =
    $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();


/* RETURNED */

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE user_id = ?
     AND status = 'Returned'"
);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$returned =
    $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();


/* BOOK HISTORY */

$stmt = $conn->prepare(
    "SELECT
        issued_books.issue_id,
        issued_books.issue_date,
        issued_books.return_date,
        issued_books.status,
        books.book_no,
        books.book_name,
        books.author
     FROM issued_books

     INNER JOIN books
        ON issued_books.book_id = books.book_id

     WHERE issued_books.user_id = ?

     ORDER BY issued_books.issue_id DESC"
);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$history = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Student Dashboard</title>

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

/* SIDEBAR */

.sidebar {

    position: fixed;

    left: 0;
    top: 0;

    width: 250px;

    height: 100vh;

    background: rgba(5,22,48,.97);

    padding: 25px 15px;

    border-right:
    1px solid rgba(255,255,255,.1);
}

.logo {

    text-align: center;

    margin-bottom: 35px;
}

.logo i {

    font-size: 42px;

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

/* MAIN */

.main {

    margin-left: 250px;

    padding: 30px;
}

/* TOPBAR */

.topbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 18px 22px;

    background: rgba(255,255,255,.08);

    border-radius: 15px;

    margin-bottom: 25px;
}

.student-profile {

    display: flex;

    align-items: center;

    gap: 10px;
}

.student-profile i {

    font-size: 25px;

    color: #4da3ff;
}

/* WELCOME */

.welcome {

    background:
    linear-gradient(
        135deg,
        rgba(13,110,253,.35),
        rgba(77,163,255,.10)
    );

    border: 1px solid rgba(255,255,255,.12);

    padding: 30px;

    border-radius: 18px;

    margin-bottom: 25px;

    box-shadow:
    0 15px 40px rgba(0,0,0,.25);
}

.welcome h2 {

    font-size: 30px;

    margin-bottom: 8px;
}

.welcome p {

    color: #d3dfed;
}

.student-id {

    display: inline-block;

    margin-top: 18px;

    padding: 8px 14px;

    background: rgba(255,255,255,.12);

    border-radius: 8px;

    color: #4da3ff;

    font-weight: bold;
}

/* STATS */

.stats {

    display: grid;

    grid-template-columns:
    repeat(3,1fr);

    gap: 20px;

    margin-bottom: 30px;
}

.stat {

    background: rgba(255,255,255,.09);

    border: 1px solid rgba(255,255,255,.1);

    padding: 25px;

    border-radius: 16px;

    transition: .3s;
}

.stat:hover {

    transform: translateY(-5px);
}

.stat i {

    color: #4da3ff;

    font-size: 28px;
}

.stat h3 {

    font-size: 30px;

    margin: 10px 0 5px;
}

/* HISTORY */

.history {

    background: rgba(255,255,255,.08);

    border-radius: 16px;

    padding: 20px;

    overflow-x: auto;
}

.history h2 {

    margin-bottom: 20px;
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

/* BOOK NUMBER */

.book-number {

    display: inline-block;

    background:
    rgba(77,163,255,.15);

    color: #4da3ff;

    padding: 6px 10px;

    border-radius: 7px;

    font-weight: bold;

    letter-spacing: 1px;
}

/* STATUS */

.status {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 13px;

    font-weight: bold;
}

.status.issued {

    background: rgba(255,193,7,.18);

    color: #ffd35a;
}

.status.returned {

    background: rgba(25,135,84,.18);

    color: #8de5b7;
}

/* EMPTY */

.empty {

    text-align: center;

    padding: 45px;

    color: #cbd8e8;
}

.empty i {

    color: #4da3ff;

    font-size: 45px;

    margin-bottom: 12px;
}

/* FOOTER */

footer {

    text-align: center;

    color: #b7c7da;

    padding: 30px;
}

/* MOBILE */

@media(max-width:900px) {

    .stats {

        grid-template-columns: 1fr;
    }
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

    .student-profile span {

        display: none;
    }

    .welcome h2 {

        font-size: 24px;
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

        <a href="student_dashboard.php"
           class="active">

            <i class="fa-solid fa-house"></i>

            <span>Dashboard</span>

        </a>

        <a href="#my-books">

            <i class="fa-solid fa-book"></i>

            <span>My Books</span>

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

            <i class="fa-solid fa-graduation-cap"></i>

            Student Dashboard

        </h1>

        <div class="student-profile">

            <i class="fa-solid fa-user"></i>

            <span>

                <?php
                echo htmlspecialchars(
                    $student["name"]
                );
                ?>

            </span>

        </div>

    </div>


    <!-- WELCOME -->

    <div class="welcome">

        <h2>

            Welcome,
            <?php
            echo htmlspecialchars(
                $student["name"]
            );
            ?>!

        </h2>

        <p>

            View your library books and issue/return history here.

        </p>

        <div class="student-id">

            <i class="fa-solid fa-id-card"></i>

            Student ID:

            <?php
            echo htmlspecialchars(
                $student["student_id"]
            );
            ?>

        </div>

    </div>


    <!-- STATS -->

    <div class="stats">

        <div class="stat">

            <i class="fa-solid fa-book-open"></i>

            <h3>
                <?php echo $currentIssued; ?>
            </h3>

            <p>Currently Issued</p>

        </div>


        <div class="stat">

            <i class="fa-solid fa-clock-rotate-left"></i>

            <h3>
                <?php echo $totalHistory; ?>
            </h3>

            <p>Total History</p>

        </div>


        <div class="stat">

            <i class="fa-solid fa-check-circle"></i>

            <h3>
                <?php echo $returned; ?>
            </h3>

            <p>Returned Books</p>

        </div>

    </div>


    <!-- HISTORY -->

    <div class="history" id="my-books">

        <h2>

            <i class="fa-solid fa-book"></i>

            My Book History

        </h2>


        <?php if ($history->num_rows > 0): ?>

        <table>

            <thead>

                <tr>

                    <th>Book No.</th>

                    <th>Book Name</th>

                    <th>Author</th>

                    <th>Issue Date</th>

                    <th>Return Date</th>

                    <th>Status</th>

                </tr>

            </thead>

            <tbody>

            <?php while ($row = $history->fetch_assoc()): ?>

                <tr>

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

                        <?php if ($row["return_date"]): ?>

                            <?php
                            echo date(
                                "d M Y",
                                strtotime(
                                    $row["return_date"]
                                )
                            );
                            ?>

                        <?php else: ?>

                            —

                        <?php endif; ?>

                    </td>

                    <td>

                        <?php if ($row["status"] === "Issued"): ?>

                            <span class="status issued">

                                Issued

                            </span>

                        <?php else: ?>

                            <span class="status returned">

                                Returned

                            </span>

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

        <?php else: ?>

            <div class="empty">

                <i class="fa-solid fa-book-open"></i>

                <h3>No Book History</h3>

                <p>
                    You have no issued or returned books yet.
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

document.querySelector(
    'a[href="#my-books"]'
).addEventListener(
    "click",
    function(e) {

        e.preventDefault();

        document.getElementById(
            "my-books"
        ).scrollIntoView({
            behavior: "smooth"
        });

    }
);

</script>

</body>

</html>s