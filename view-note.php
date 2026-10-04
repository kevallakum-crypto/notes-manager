<?php

session_start();

require_once "config/database.php";

/* ==============================
   LOGIN PROTECTION
================================ */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


/* ==============================
   CHECK NOTE ID
================================ */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid note ID.");
}

$note_id = intval($_GET["id"]);


/* ==============================
   GET NOTE
================================ */

$stmt = $conn->prepare("
    SELECT
        notes.id,
        notes.title,
        notes.description,
        notes.file_name,
        notes.created_at,
        categories.category_name
    FROM notes
    INNER JOIN categories
        ON notes.category_id = categories.id
    WHERE notes.id = ?
");

$stmt->bind_param("i", $note_id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows !== 1) {
    die("Note not found.");
}


$note = $result->fetch_assoc();


/* ==============================
   CHECK PDF FILE
================================ */

$file_path = __DIR__ . "/uploads/notes/" . $note["file_name"];

if (!file_exists($file_path)) {
    die("PDF file not found.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo htmlspecialchars($note["title"]); ?> - NoteHub
    </title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f1f5f9;
            color: #111827;
        }


        /* =========================
           NAVBAR
        ========================= */

        .navbar {

            height: 70px;

            background: white;

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 0 5%;

            box-shadow: 0 2px 12px rgba(0,0,0,0.07);

            position: sticky;

            top: 0;

            z-index: 100;

        }


        .logo {

            text-decoration: none;

            font-size: 22px;

            font-weight: bold;

            color: #111827;

        }


        .logo span {

            color: #2563eb;

        }


        .nav-right {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .nav-btn {

            text-decoration: none;

            padding: 9px 16px;

            border-radius: 7px;

            font-size: 13px;

            color: #374151;

            background: #f8fafc;

            transition: 0.3s;

        }


        .nav-btn:hover {

            background: #e2e8f0;

        }


        .download-btn {

            background: #2563eb;

            color: white;

        }


        .download-btn:hover {

            background: #1d4ed8;

        }


        /* =========================
           MAIN
        ========================= */

        .container {

            width: 94%;

            max-width: 1400px;

            margin: 25px auto;

        }


        /* =========================
           NOTE HEADER
        ========================= */

        .note-header {

            background: white;

            padding: 25px;

            border-radius: 12px;

            margin-bottom: 20px;

            box-shadow: 0 2px 10px rgba(0,0,0,0.05);

        }


        .category {

            display: inline-block;

            background: #dbeafe;

            color: #1d4ed8;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 12px;

            margin-bottom: 12px;

        }


        .note-header h1 {

            font-size: 27px;

            margin-bottom: 10px;

            word-break: break-word;

        }


        .description {

            color: #64748b;

            font-size: 14px;

            line-height: 1.7;

            margin-bottom: 15px;

        }


        .date {

            color: #94a3b8;

            font-size: 12px;

        }


        /* =========================
           PDF VIEWER
        ========================= */

        .viewer {

            background: #334155;

            border-radius: 12px;

            padding: 12px;

            box-shadow: 0 5px 20px rgba(0,0,0,0.12);

        }


        .viewer-top {

            display: flex;

            justify-content: space-between;

            align-items: center;

            color: white;

            padding: 8px 10px 15px;

        }


        .viewer-top span {

            font-size: 13px;

            color: #cbd5e1;

        }


        .fullscreen-btn {

            border: none;

            background: #475569;

            color: white;

            padding: 8px 13px;

            border-radius: 6px;

            cursor: pointer;

            font-size: 12px;

        }


        .fullscreen-btn:hover {

            background: #64748b;

        }


        .pdf-frame {

            width: 100%;

            height: 750px;

            border: none;

            border-radius: 8px;

            background: white;

        }


        /* =========================
           BOTTOM ACTIONS
        ========================= */

        .actions {

            background: white;

            padding: 20px;

            margin-top: 20px;

            border-radius: 12px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            box-shadow: 0 2px 10px rgba(0,0,0,0.05);

        }


        .actions p {

            color: #64748b;

            font-size: 13px;

        }


        .action-buttons {

            display: flex;

            gap: 10px;

        }


        .action-btn {

            text-decoration: none;

            padding: 11px 18px;

            border-radius: 7px;

            font-size: 13px;

            font-weight: 500;

        }


        .back-btn {

            background: #f1f5f9;

            color: #334155;

        }


        .download-action {

            background: #2563eb;

            color: white;

        }


        .download-action:hover {

            background: #1d4ed8;

        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 700px) {

            .navbar {

                padding: 0 4%;

            }


            .nav-btn {

                padding: 8px 10px;

                font-size: 11px;

            }


            .container {

                width: 96%;

            }


            .note-header {

                padding: 20px;

            }


            .note-header h1 {

                font-size: 22px;

            }


            .pdf-frame {

                height: 600px;

            }


            .actions {

                flex-direction: column;

                align-items: stretch;

            }


            .action-buttons {

                width: 100%;

            }


            .action-btn {

                flex: 1;

                text-align: center;

            }

        }

    </style>

</head>


<body>


<!-- =========================
     NAVBAR
========================= -->

<nav class="navbar">


    <a href="index.php" class="logo">

        Note<span>Hub</span>

    </a>


    <div class="nav-right">


        <a
            href="notes.php"
            class="nav-btn"
        >
            Browse Notes
        </a>


        <a
            href="download.php?id=<?php echo $note["id"]; ?>"
            class="nav-btn download-btn"
        >
            Download PDF
        </a>


    </div>


</nav>



<!-- =========================
     MAIN CONTAINER
========================= -->

<div class="container">


    <!-- NOTE INFORMATION -->

    <div class="note-header">


        <span class="category">

            <?php

            echo htmlspecialchars(
                $note["category_name"]
            );

            ?>

        </span>


        <h1>

            <?php

            echo htmlspecialchars(
                $note["title"]
            );

            ?>

        </h1>


        <?php if (!empty($note["description"])): ?>

            <div class="description">

                <?php

                echo nl2br(
                    htmlspecialchars(
                        $note["description"]
                    )
                );

                ?>

            </div>

        <?php endif; ?>


        <div class="date">

            Uploaded on

            <?php

            echo date(
                "d M Y",
                strtotime(
                    $note["created_at"]
                )
            );

            ?>

        </div>


    </div>



    <!-- PDF VIEWER -->

    <div class="viewer">


        <div class="viewer-top">

            <span>
                PDF Document
            </span>


            <button
                class="fullscreen-btn"
                onclick="openFullscreen()"
            >
                Fullscreen
            </button>

        </div>


        <iframe
            id="pdfViewer"
            class="pdf-frame"
            src="uploads/notes/<?php echo rawurlencode($note["file_name"]); ?>"
        ></iframe>


    </div>



    <!-- ACTIONS -->

    <div class="actions">


        <p>
            Need this document later? Download the PDF for offline study.
        </p>


        <div class="action-buttons">


            <a
                href="notes.php"
                class="action-btn back-btn"
            >
                ← Back to Notes
            </a>


            <a
                href="download.php?id=<?php echo $note["id"]; ?>"
                class="action-btn download-action"
            >
                Download PDF
            </a>


        </div>


    </div>


</div>



<script>

function openFullscreen() {

    const viewer = document.getElementById("pdfViewer");

    if (viewer.requestFullscreen) {

        viewer.requestFullscreen();

    } else if (viewer.webkitRequestFullscreen) {

        viewer.webkitRequestFullscreen();

    } else if (viewer.msRequestFullscreen) {

        viewer.msRequestFullscreen();

    }

}

</script>


</body>

</html>