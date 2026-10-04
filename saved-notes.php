<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION["user_id"];


/* Remove Saved Note */

if (
    isset($_GET["remove"]) &&
    is_numeric($_GET["remove"])
) {

    $note_id = intval($_GET["remove"]);

    $stmt = $conn->prepare("
        DELETE FROM saved_notes
        WHERE user_id = ?
        AND note_id = ?
    ");

    $stmt->bind_param(
        "ii",
        $user_id,
        $note_id
    );

    $stmt->execute();

    header("Location: saved-notes.php");

    exit;
}


/* Get Saved Notes */

$stmt = $conn->prepare("
    SELECT
        notes.id,
        notes.title,
        notes.description,
        notes.file_name,
        notes.created_at,
        categories.category_name,
        saved_notes.saved_at

    FROM saved_notes

    INNER JOIN notes
        ON saved_notes.note_id = notes.id

    INNER JOIN categories
        ON notes.category_id = categories.id

    WHERE saved_notes.user_id = ?

    ORDER BY saved_notes.saved_at DESC
");

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$saved_notes = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Saved Notes</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f7fb;
            color: #222;
        }

        .navbar {
            background: white;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 15px rgba(0,0,0,0.06);
        }

        .logo {
            font-size: 23px;
            font-weight: bold;
            color: #4f46e5;
        }

        .nav-links {
            display: flex;
            gap: 12px;
        }

        .nav-links a {
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 9px;
            font-weight: bold;
            color: #333;
        }

        .dashboard {
            background: #4f46e5;
            color: white !important;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 45px 20px;
        }

        .heading {
            margin-bottom: 30px;
        }

        .heading h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .heading p {
            color: #777;
        }

        .notes-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .note-card {
            background: white;
            padding: 22px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            transition: 0.25s;
            display: flex;
            flex-direction: column;
        }

        .note-card:hover {
            transform: translateY(-5px);
        }

        .pdf-icon {
            width: 50px;
            height: 50px;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .category {
            display: inline-block;
            width: fit-content;
            padding: 6px 11px;
            background: #eef2ff;
            color: #4338ca;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .note-card h2 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .description {
            color: #666;
            line-height: 1.5;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .date {
            color: #999;
            font-size: 12px;
            margin-bottom: 18px;
        }

        .actions {
            margin-top: auto;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .actions a {
            text-decoration: none;
            padding: 9px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
        }

        .view {
            background: #e0f2fe;
            color: #0369a1;
        }

        .download {
            background: #dcfce7;
            color: #166534;
        }

        .remove {
            background: #fee2e2;
            color: #b91c1c;
        }

        .empty {
            background: white;
            padding: 60px 30px;
            text-align: center;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
        }

        .empty h2 {
            margin-bottom: 10px;
        }

        .empty p {
            color: #777;
            margin-bottom: 20px;
        }

        .browse {
            display: inline-block;
            text-decoration: none;
            background: #4f46e5;
            color: white;
            padding: 11px 18px;
            border-radius: 9px;
            font-weight: bold;
        }

        @media (max-width: 900px) {

            .notes-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 650px) {

            .navbar {
                padding: 15px 20px;
            }

            .nav-links a:not(.dashboard) {
                display: none;
            }

            .notes-grid {
                grid-template-columns: 1fr;
            }

            .heading h1 {
                font-size: 27px;
            }

        }

    </style>

</head>

<body>


<nav class="navbar">

    <div class="logo">
        Notes Management
    </div>

    <div class="nav-links">

        <a href="../notes.php">
            Browse Notes
        </a>

        <a
            href="dashboard.php"
            class="dashboard"
        >
            Dashboard
        </a>

    </div>

</nav>


<div class="container">

    <div class="heading">

        <h1>
            Saved Notes
        </h1>

        <p>
            Your saved study materials are available here.
        </p>

    </div>


    <?php if ($saved_notes->num_rows > 0): ?>

        <div class="notes-grid">

            <?php while ($note = $saved_notes->fetch_assoc()): ?>

                <div class="note-card">

                    <div class="pdf-icon">
                        PDF
                    </div>

                    <span class="category">

                        <?php
                        echo htmlspecialchars(
                            $note["category_name"]
                        );
                        ?>

                    </span>

                    <h2>

                        <?php
                        echo htmlspecialchars(
                            $note["title"]
                        );
                        ?>

                    </h2>

                    <p class="description">

                        <?php

                        $description =
                            $note["description"];

                        if (strlen($description) > 100) {

                            echo htmlspecialchars(
                                substr($description, 0, 100)
                            ) . "...";

                        } else {

                            echo htmlspecialchars(
                                $description
                            );
                        }

                        ?>

                    </p>

                    <div class="date">

                        Saved on

                        <?php
                        echo date(
                            "d M Y",
                            strtotime($note["saved_at"])
                        );
                        ?>

                    </div>


                    <div class="actions">

                        <a
                            href="../uploads/notes/<?php echo urlencode($note["file_name"]); ?>"
                            target="_blank"
                            class="view"
                        >
                            View
                        </a>

                        <a
                            href="../download.php?id=<?php echo $note["id"]; ?>"
                            class="download"
                        >
                            Download
                        </a>

                        <a
                            href="?remove=<?php echo $note["id"]; ?>"
                            class="remove"
                            onclick="return confirm('Remove this note from saved notes?');"
                        >
                            Remove
                        </a>

                    </div>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="empty">

            <h2>
                No Saved Notes
            </h2>

            <p>
                You have not saved any notes yet.
            </p>

            <a
                href="../notes.php"
                class="browse"
            >
                Browse Notes
            </a>

        </div>

    <?php endif; ?>

</div>

</body>

</html>