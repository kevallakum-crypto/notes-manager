<?php

session_start();

require_once "../config/database.php";


/* ==============================
   ADMIN CHECK
================================ */

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}


/* ==============================
   TOTAL DOWNLOADS
================================ */

$total_downloads = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM downloads
");

if ($result) {
    $row = $result->fetch_assoc();
    $total_downloads = $row["total"];
}


/* ==============================
   DOWNLOADS TODAY
================================ */

$today_downloads = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM downloads
    WHERE DATE(downloaded_at) = CURDATE()
");

if ($result) {
    $row = $result->fetch_assoc();
    $today_downloads = $row["total"];
}


/* ==============================
   DOWNLOADS THIS MONTH
================================ */

$month_downloads = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM downloads
    WHERE MONTH(downloaded_at) = MONTH(CURDATE())
    AND YEAR(downloaded_at) = YEAR(CURDATE())
");

if ($result) {
    $row = $result->fetch_assoc();
    $month_downloads = $row["total"];
}


/* ==============================
   TOTAL USERS
================================ */

$total_users = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
");

if ($result) {
    $row = $result->fetch_assoc();
    $total_users = $row["total"];
}


/* ==============================
   MOST DOWNLOADED NOTES
================================ */

$popular_notes = $conn->query("
    SELECT
        notes.id,
        notes.title,
        categories.category_name,
        COUNT(downloads.id) AS download_count
    FROM notes
    LEFT JOIN downloads
        ON notes.id = downloads.note_id
    INNER JOIN categories
        ON notes.category_id = categories.id
    GROUP BY
        notes.id,
        notes.title,
        categories.category_name
    ORDER BY download_count DESC
    LIMIT 10
");


/* ==============================
   CATEGORY DOWNLOADS
================================ */

$category_downloads = $conn->query("
    SELECT
        categories.category_name,
        COUNT(downloads.id) AS download_count
    FROM categories
    LEFT JOIN notes
        ON categories.id = notes.category_id
    LEFT JOIN downloads
        ON notes.id = downloads.note_id
    GROUP BY
        categories.id,
        categories.category_name
    ORDER BY download_count DESC
");


/* ==============================
   RECENT DOWNLOADS
================================ */

$recent_downloads = $conn->query("
    SELECT
        downloads.downloaded_at,
        users.name AS user_name,
        users.email,
        notes.title AS note_title
    FROM downloads
    INNER JOIN users
        ON downloads.user_id = users.id
    INNER JOIN notes
        ON downloads.note_id = notes.id
    ORDER BY downloads.downloaded_at DESC
    LIMIT 15
");

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Analytics - NoteHub</title>


<style>

/* =========================================
   RESET
========================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f5f7fb;
    color: #1f2937;
}


/* =========================================
   SIDEBAR
========================================= */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;

    width: 250px;
    height: 100vh;

    background: #111827;

    padding: 25px 15px;

    overflow-y: auto;

    z-index: 1000;
}


.sidebar-logo {
    color: #ffffff;

    font-size: 26px;
    font-weight: 800;

    padding: 10px 15px 30px;

    letter-spacing: 0.5px;
}


.sidebar a {
    display: flex;

    align-items: center;

    width: 100%;

    padding: 13px 15px;

    margin-bottom: 7px;

    color: #cbd5e1;

    text-decoration: none;

    border-radius: 10px;

    font-size: 15px;

    font-weight: 500;

    transition: 0.25s;
}


.sidebar a:hover {
    background: #1f2937;

    color: #ffffff;

    transform: translateX(3px);
}


.sidebar a.active {
    background: #ffffff;

    color: #111827;

    font-weight: 700;
}


.sidebar .logout-link {
    margin-top: 25px;

    color: #fca5a5;
}


.sidebar .logout-link:hover {
    background: #7f1d1d;

    color: #ffffff;
}


/* =========================================
   MAIN CONTENT
========================================= */

.main-content {
    margin-left: 250px;

    padding: 35px;

    min-height: 100vh;
}


/* =========================================
   HEADER
========================================= */

.page-header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 30px;
}


.page-header h1 {
    font-size: 32px;

    font-weight: 800;

    color: #111827;

    margin-bottom: 7px;
}


.page-header p {
    color: #6b7280;

    font-size: 15px;
}


/* =========================================
   STATISTICS GRID
========================================= */

.analytics-grid {
    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 20px;

    margin-bottom: 30px;
}


.analytics-card {
    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 18px;

    padding: 25px;

    box-shadow:
        0 5px 20px rgba(0, 0, 0, 0.05);

    transition: 0.25s;
}


.analytics-card:hover {
    transform: translateY(-4px);

    box-shadow:
        0 10px 25px rgba(0, 0, 0, 0.08);
}


.analytics-card h3 {
    font-size: 14px;

    font-weight: 600;

    color: #6b7280;

    margin-bottom: 12px;
}


.analytics-number {
    font-size: 32px;

    font-weight: 800;

    color: #111827;
}


/* =========================================
   CONTENT SECTIONS
========================================= */

.analytics-section {
    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 18px;

    padding: 25px;

    margin-bottom: 25px;

    box-shadow:
        0 5px 20px rgba(0, 0, 0, 0.05);

    overflow: hidden;
}


.analytics-section h2 {
    font-size: 20px;

    font-weight: 750;

    color: #111827;

    margin-bottom: 22px;
}


/* =========================================
   TABLE
========================================= */

.analytics-table {
    width: 100%;

    border-collapse: collapse;
}


.analytics-table th {
    background: #f8fafc;

    color: #64748b;

    font-size: 13px;

    font-weight: 700;

    text-align: left;

    padding: 14px;

    border-bottom: 1px solid #e5e7eb;
}


.analytics-table td {
    padding: 15px 14px;

    font-size: 14px;

    color: #374151;

    border-bottom: 1px solid #f1f5f9;
}


.analytics-table tr:last-child td {
    border-bottom: none;
}


.analytics-table tbody tr {
    transition: 0.2s;
}


.analytics-table tbody tr:hover {
    background: #f8fafc;
}


/* =========================================
   DOWNLOAD BADGE
========================================= */

.download-badge {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 42px;

    padding: 6px 11px;

    background: #111827;

    color: #ffffff;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 700;
}


/* =========================================
   CATEGORY BARS
========================================= */

.bar-row {
    margin-bottom: 22px;
}


.bar-row:last-child {
    margin-bottom: 0;
}


.bar-info {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 9px;

    font-size: 14px;

    color: #374151;
}


.bar-info strong {
    font-size: 14px;

    color: #111827;
}


.bar-container {
    width: 100%;

    height: 11px;

    background: #e5e7eb;

    border-radius: 20px;

    overflow: hidden;
}


.bar {
    height: 100%;

    background: #111827;

    border-radius: 20px;

    min-width: 2px;

    transition:
        width 0.5s ease;
}


/* =========================================
   EMPTY MESSAGE
========================================= */

.empty-message {
    text-align: center;

    padding: 35px 20px !important;

    color: #9ca3af !important;

    font-size: 14px;
}


/* =========================================
   RESPONSIVE - TABLET
========================================= */

@media (max-width: 1100px) {

    .analytics-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

}


/* =========================================
   RESPONSIVE - MOBILE
========================================= */

@media (max-width: 750px) {

    .sidebar {
        position: relative;

        width: 100%;

        height: auto;

        min-height: auto;

        padding: 15px;

    }


    .sidebar-logo {
        padding:
            10px 10px 20px;

        font-size: 23px;
    }


    .sidebar a {
        display: inline-flex;

        width: auto;

        margin:
            3px 2px;

        padding:
            10px 12px;

    }


    .sidebar .logout-link {
        margin-top: 5px;
    }


    .main-content {
        margin-left: 0;

        padding: 20px 15px;
    }


    .page-header h1 {
        font-size: 27px;
    }


    .analytics-grid {
        grid-template-columns: 1fr;

        gap: 15px;
    }


    .analytics-card {
        padding: 20px;
    }


    .analytics-section {
        padding: 18px;

        overflow-x: auto;
    }


    .analytics-table {
        min-width: 650px;
    }

}


/* =========================================
   SMALL MOBILE
========================================= */

@media (max-width: 450px) {

    .main-content {
        padding: 15px 10px;
    }


    .page-header h1 {
        font-size: 24px;
    }


    .page-header p {
        font-size: 13px;
    }


    .analytics-number {
        font-size: 28px;
    }


    .analytics-section h2 {
        font-size: 18px;
    }

}

</style>

</head>


<body>


<!-- =========================================
     SIDEBAR
========================================= -->

<div class="sidebar">

    <div class="sidebar-logo">
        NoteHub
    </div>


    <a href="dashboard.php">
        Dashboard
    </a>


    <a href="add-note.php">
        Add Note
    </a>


    <a href="manage-notes.php">
        Manage Notes
    </a>


    <a href="categories.php">
        Categories
    </a>


    <a href="users.php">
        Users
    </a>


    <a
        href="analytics.php"
        class="active"
    >
        Analytics
    </a>


    <a
        href="../logout.php"
        class="logout-link"
    >
        Logout
    </a>

</div>



<!-- =========================================
     MAIN CONTENT
========================================= -->

<div class="main-content">


    <!-- HEADER -->

    <div class="page-header">

        <div>

            <h1>
                Analytics
            </h1>

            <p>
                Monitor downloads and user activity.
            </p>

        </div>

    </div>



    <!-- =====================================
         STAT CARDS
    ====================================== -->

    <div class="analytics-grid">


        <div class="analytics-card">

            <h3>
                Total Downloads
            </h3>

            <div class="analytics-number">
                <?php
                echo $total_downloads;
                ?>
            </div>

        </div>



        <div class="analytics-card">

            <h3>
                Downloads Today
            </h3>

            <div class="analytics-number">
                <?php
                echo $today_downloads;
                ?>
            </div>

        </div>



        <div class="analytics-card">

            <h3>
                Downloads This Month
            </h3>

            <div class="analytics-number">
                <?php
                echo $month_downloads;
                ?>
            </div>

        </div>



        <div class="analytics-card">

            <h3>
                Total Users
            </h3>

            <div class="analytics-number">
                <?php
                echo $total_users;
                ?>
            </div>

        </div>

    </div>



    <!-- =====================================
         MOST DOWNLOADED NOTES
    ====================================== -->

    <div class="analytics-section">

        <h2>
            Most Downloaded Notes
        </h2>


        <table class="analytics-table">

            <thead>

                <tr>

                    <th>
                        #
                    </th>

                    <th>
                        Note
                    </th>

                    <th>
                        Category
                    </th>

                    <th>
                        Downloads
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php

            $rank = 1;

            if (
                $popular_notes &&
                $popular_notes->num_rows > 0
            ):

                while (
                    $note =
                    $popular_notes->fetch_assoc()
                ):

            ?>

                <tr>

                    <td>
                        <?php
                        echo $rank++;
                        ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $note["title"]
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $note["category_name"]
                        );
                        ?>
                    </td>


                    <td>

                        <span class="download-badge">

                            <?php
                            echo $note["download_count"];
                            ?>

                        </span>

                    </td>

                </tr>

            <?php

                endwhile;

            else:

            ?>

                <tr>

                    <td
                        colspan="4"
                        class="empty-message"
                    >
                        No download data available.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>



    <!-- =====================================
         CATEGORY DOWNLOADS
    ====================================== -->

    <div class="analytics-section">

        <h2>
            Category-wise Downloads
        </h2>


        <?php

        $category_data = [];

        $max_downloads = 0;


        if (
            $category_downloads &&
            $category_downloads->num_rows > 0
        ) {

            while (
                $category =
                $category_downloads->fetch_assoc()
            ) {

                $category_data[] = $category;

                if (
                    $category["download_count"]
                    > $max_downloads
                ) {

                    $max_downloads =
                        $category["download_count"];

                }

            }

        }


        if (
            count($category_data) > 0
        ):

            foreach (
                $category_data
                as $category
            ):

                $percentage = 0;


                if ($max_downloads > 0) {

                    $percentage =
                        (
                            $category["download_count"]
                            / $max_downloads
                        ) * 100;

                }

        ?>

            <div class="bar-row">

                <div class="bar-info">

                    <span>

                        <?php

                        echo htmlspecialchars(
                            $category["category_name"]
                        );

                        ?>

                    </span>


                    <strong>

                        <?php

                        echo $category["download_count"];

                        ?>

                    </strong>

                </div>


                <div class="bar-container">

                    <div
                        class="bar"
                        style="
                            width:
                            <?php
                            echo $percentage;
                            ?>%;
                        "
                    ></div>

                </div>

            </div>

        <?php

            endforeach;

        else:

        ?>

            <div class="empty-message">

                No category data available.

            </div>

        <?php endif; ?>

    </div>



    <!-- =====================================
         RECENT DOWNLOADS
    ====================================== -->

    <div class="analytics-section">

        <h2>
            Recent Download Activity
        </h2>


        <table class="analytics-table">

            <thead>

                <tr>

                    <th>
                        User
                    </th>

                    <th>
                        Email
                    </th>

                    <th>
                        Note
                    </th>

                    <th>
                        Date & Time
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php

            if (
                $recent_downloads &&
                $recent_downloads->num_rows > 0
            ):

                while (
                    $download =
                    $recent_downloads->fetch_assoc()
                ):

            ?>

                <tr>

                    <td>
                        <?php

                        echo htmlspecialchars(
                            $download["user_name"]
                        );

                        ?>
                    </td>


                    <td>
                        <?php

                        echo htmlspecialchars(
                            $download["email"]
                        );

                        ?>
                    </td>


                    <td>
                        <?php

                        echo htmlspecialchars(
                            $download["note_title"]
                        );

                        ?>
                    </td>


                    <td>
                        <?php

                        echo date(
                            "d M Y, h:i A",
                            strtotime(
                                $download["downloaded_at"]
                            )
                        );

                        ?>
                    </td>

                </tr>

            <?php

                endwhile;

            else:

            ?>

                <tr>

                    <td
                        colspan="4"
                        class="empty-message"
                    >

                        No download activity yet.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


</div>


</body>

</html>