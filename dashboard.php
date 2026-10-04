<?php

session_start();

require_once "../config/database.php";


/* ==============================
   USER CHECK
================================ */

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["user_role"] !== "user"
) {
    header("Location: ../login.php");
    exit;
}


$user_id = intval($_SESSION["user_id"]);

$user_name = $_SESSION["user_name"];


/* ==============================
   TOTAL NOTES
================================ */

$total_notes = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM notes
");

if ($result) {

    $row = $result->fetch_assoc();

    $total_notes = $row["total"];

}


/* ==============================
   SAVED NOTES
================================ */

$saved_notes = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM saved_notes
    WHERE user_id = ?
");

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result) {

    $row = $result->fetch_assoc();

    $saved_notes = $row["total"];

}


/* ==============================
   TOTAL CATEGORIES
================================ */

$total_categories = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM categories
");

if ($result) {

    $row = $result->fetch_assoc();

    $total_categories = $row["total"];

}


/* ==============================
   USER DOWNLOADS
================================ */

$user_downloads = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM downloads
    WHERE user_id = ?
");

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result) {

    $row = $result->fetch_assoc();

    $user_downloads = $row["total"];

}


/* ==============================
   RECENT NOTES
================================ */

$recent_notes = $conn->query("
    SELECT
        notes.id,
        notes.title,
        notes.description,
        categories.category_name,
        notes.created_at
    FROM notes

    INNER JOIN categories
        ON notes.category_id = categories.id

    ORDER BY notes.created_at DESC

    LIMIT 6
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

<title>User Dashboard - NoteHub</title>


<style>

/* =================================
   RESET
================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


body {
    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f5f7fb;

    color: #1f2937;
}


/* =================================
   NAVBAR
================================= */

.navbar {
    width: 100%;

    background: #111827;

    padding:
        16px 40px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,0.08);
}


.logo {
    color: #ffffff;

    font-size: 24px;

    font-weight: 800;

    text-decoration: none;
}


.nav-links {
    display: flex;

    align-items: center;

    gap: 8px;
}


.nav-links a {
    color: #cbd5e1;

    text-decoration: none;

    padding:
        9px 13px;

    border-radius: 8px;

    font-size: 14px;

    transition: 0.25s;
}


.nav-links a:hover {
    background: #1f2937;

    color: #ffffff;
}


.nav-links .logout {
    color: #fca5a5;
}


/* =================================
   MAIN
================================= */

.container {
    max-width: 1250px;

    margin: auto;

    padding:
        40px 25px;
}


/* =================================
   WELCOME
================================= */

.welcome {
    margin-bottom: 30px;
}


.welcome h1 {
    font-size: 32px;

    color: #111827;

    margin-bottom: 8px;
}


.welcome p {
    color: #6b7280;

    font-size: 15px;
}


/* =================================
   STAT GRID
================================= */

.stats-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 20px;

    margin-bottom: 30px;
}


.stat-card {
    background: #ffffff;

    border:
        1px solid #e5e7eb;

    border-radius: 18px;

    padding: 24px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,0.05);

    transition: 0.25s;
}


.stat-card:hover {
    transform:
        translateY(-4px);

    box-shadow:
        0 10px 25px
        rgba(0,0,0,0.08);
}


.stat-title {
    color: #6b7280;

    font-size: 14px;

    font-weight: 600;

    margin-bottom: 10px;
}


.stat-number {
    color: #111827;

    font-size: 30px;

    font-weight: 800;
}


/* =================================
   QUICK ACTIONS
================================= */

.section {
    background: #ffffff;

    border:
        1px solid #e5e7eb;

    border-radius: 18px;

    padding: 25px;

    margin-bottom: 25px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,0.05);
}


.section h2 {
    font-size: 20px;

    color: #111827;

    margin-bottom: 20px;
}


.quick-actions {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 14px;
}


.action-card {
    text-decoration: none;

    padding: 18px;

    background: #f8fafc;

    border:
        1px solid #e5e7eb;

    border-radius: 12px;

    color: #111827;

    font-weight: 700;

    font-size: 14px;

    transition: 0.25s;
}


.action-card:hover {
    background: #111827;

    color: #ffffff;

    transform:
        translateY(-3px);
}


/* =================================
   NOTES GRID
================================= */

.notes-grid {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;
}


.note-card {
    border:
        1px solid #e5e7eb;

    border-radius: 15px;

    padding: 20px;

    background: #ffffff;

    transition: 0.25s;
}


.note-card:hover {
    transform:
        translateY(-4px);

    box-shadow:
        0 10px 25px
        rgba(0,0,0,0.07);
}


.note-category {
    display: inline-block;

    background: #f1f5f9;

    color: #475569;

    padding:
        5px 10px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 700;

    margin-bottom: 12px;
}


.note-card h3 {
    font-size: 17px;

    color: #111827;

    margin-bottom: 8px;
}


.note-description {
    color: #6b7280;

    font-size: 13px;

    line-height: 1.5;

    margin-bottom: 15px;
}


.note-date {
    color: #9ca3af;

    font-size: 12px;

    margin-bottom: 15px;
}


.view-btn {
    display: inline-block;

    text-decoration: none;

    background: #111827;

    color: #ffffff;

    padding:
        9px 14px;

    border-radius: 8px;

    font-size: 12px;

    font-weight: 700;
}


.view-btn:hover {
    background: #374151;
}


/* =================================
   EMPTY
================================= */

.empty {
    text-align: center;

    padding: 35px;

    color: #9ca3af;

    font-size: 14px;
}


/* =================================
   RESPONSIVE
================================= */

@media (max-width: 1050px) {

    .stats-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }


    .quick-actions {
        grid-template-columns:
            repeat(2, 1fr);
    }


    .notes-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

}


@media (max-width: 700px) {

    .navbar {
        padding:
            15px 18px;

        flex-direction: column;

        gap: 15px;
    }


    .nav-links {
        flex-wrap: wrap;

        justify-content: center;
    }


    .container {
        padding:
            25px 15px;
    }


    .welcome h1 {
        font-size: 26px;
    }


    .stats-grid {
        grid-template-columns: 1fr;
    }


    .quick-actions {
        grid-template-columns: 1fr;
    }


    .notes-grid {
        grid-template-columns: 1fr;
    }

}

</style>

</head>


<body>


<!-- =================================
     NAVBAR
================================= -->

<nav class="navbar">


    <a
        href="dashboard.php"
        class="logo"
    >
        NoteHub
    </a>


    <div class="nav-links">

        <a href="../notes.php">
            Browse Notes
        </a>


        <a href="saved-notes.php">
            Saved Notes
        </a>


        <a href="profile.php">
            Profile
        </a>


        <a
            href="../logout.php"
            class="logout"
        >
            Logout
        </a>

    </div>

</nav>



<!-- =================================
     MAIN
================================= -->

<div class="container">


    <!-- WELCOME -->

    <div class="welcome">

        <h1>
            Welcome,
            <?php
            echo htmlspecialchars($user_name);
            ?>!
        </h1>

        <p>
            Access your notes and manage your saved resources.
        </p>

    </div>



    <!-- =================================
         STATISTICS
    ================================== -->

    <div class="stats-grid">


        <div class="stat-card">

            <div class="stat-title">
                Available Notes
            </div>

            <div class="stat-number">
                <?php
                echo $total_notes;
                ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-title">
                Saved Notes
            </div>

            <div class="stat-number">
                <?php
                echo $saved_notes;
                ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-title">
                Categories
            </div>

            <div class="stat-number">
                <?php
                echo $total_categories;
                ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-title">
                My Downloads
            </div>

            <div class="stat-number">
                <?php
                echo $user_downloads;
                ?>
            </div>

        </div>

    </div>



    <!-- =================================
         QUICK ACTIONS
    ================================== -->

    <div class="section">

        <h2>
            Quick Actions
        </h2>


        <div class="quick-actions">


            <a
                href="../notes.php"
                class="action-card"
            >
                Browse Notes
            </a>


            <a
                href="saved-notes.php"
                class="action-card"
            >
                Saved Notes
            </a>


            <a
                href="profile.php"
                class="action-card"
            >
                My Profile
            </a>


            <a
                href="../index.php"
                class="action-card"
            >
                Home
            </a>

        </div>

    </div>



    <!-- =================================
         RECENT NOTES
    ================================== -->

    <div class="section">

        <h2>
            Latest Notes
        </h2>


        <div class="notes-grid">


        <?php

        if (
            $recent_notes &&
            $recent_notes->num_rows > 0
        ):

            while (
                $note =
                $recent_notes->fetch_assoc()
            ):

        ?>

            <div class="note-card">


                <span class="note-category">

                    <?php

                    echo htmlspecialchars(
                        $note["category_name"]
                    );

                    ?>

                </span>


                <h3>

                    <?php

                    echo htmlspecialchars(
                        $note["title"]
                    );

                    ?>

                </h3>


                <p class="note-description">

                    <?php

                    $description =
                        $note["description"];

                    if (
                        strlen($description) > 100
                    ) {

                        $description =
                            substr(
                                $description,
                                0,
                                100
                            ) . "...";

                    }

                    echo htmlspecialchars(
                        $description
                    );

                    ?>

                </p>


                <div class="note-date">

                    <?php

                    echo date(
                        "d M Y",
                        strtotime(
                            $note["created_at"]
                        )
                    );

                    ?>

                </div>


                <a
                    href="../view-note.php?id=<?php echo $note["id"]; ?>"
                    class="view-btn"
                >
                    View Note
                </a>


            </div>

        <?php

            endwhile;

        else:

        ?>

            <div class="empty">

                No notes available yet.

            </div>

        <?php endif; ?>


        </div>

    </div>


</div>


</body>

</html>