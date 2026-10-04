<?php

session_start();

require_once "../config/database.php";


/* ==============================
   ADMIN PROTECTION
================================ */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}


/* ==============================
   NOTE ID VALIDATION
================================ */

if (
    !isset($_GET["id"]) ||
    !ctype_digit($_GET["id"])
) {
    header("Location: manage-notes.php");
    exit;
}

$note_id = (int) $_GET["id"];

if ($note_id <= 0) {
    header("Location: manage-notes.php");
    exit;
}


$error = "";
$success = "";


/* ==============================
   GET NOTE
================================ */

$stmt = $conn->prepare(
    "SELECT
        id,
        title,
        description,
        category_id,
        file_name
     FROM notes
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Unable to load note.");
}

$stmt->bind_param("i", $note_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();

    header("Location: manage-notes.php");
    exit;
}

$note = $result->fetch_assoc();

$stmt->close();


/* ==============================
   GET CATEGORIES
================================ */

$categories = $conn->query(
    "SELECT id, category_name
     FROM categories
     ORDER BY category_name ASC"
);


/* ==============================
   UPDATE NOTE
================================ */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = isset($_POST["title"])
        ? trim($_POST["title"])
        : "";

    $description = isset($_POST["description"])
        ? trim($_POST["description"])
        : "";

    $category_id = isset($_POST["category_id"])
        ? filter_var(
            $_POST["category_id"],
            FILTER_VALIDATE_INT
        )
        : false;


    /* ==============================
       TEXT VALIDATION
    ================================ */

    if ($title === "") {

        $error = "Please enter a note title.";

    } elseif (strlen($title) > 200) {

        $error = "Note title cannot exceed 200 characters.";

    } elseif (strlen($description) > 5000) {

        $error = "Description cannot exceed 5000 characters.";

    } elseif (
        $category_id === false ||
        $category_id <= 0
    ) {

        $error = "Please select a valid category.";

    } else {


        /* ==============================
           CATEGORY VALIDATION
        ================================ */

        $category_check = $conn->prepare(
            "SELECT id
             FROM categories
             WHERE id = ?
             LIMIT 1"
        );

        if (!$category_check) {

            $error = "Unable to validate category.";

        } else {

            $category_check->bind_param(
                "i",
                $category_id
            );

            $category_check->execute();

            $category_result =
                $category_check->get_result();


            if ($category_result->num_rows !== 1) {

                $error =
                    "Selected category does not exist.";

            }

            $category_check->close();
        }


        /* ==============================
           FILE VARIABLES
        ================================ */

        $old_file_name = $note["file_name"];
        $new_file_name = $old_file_name;

        $new_file_uploaded = false;
        $new_file_path = "";


        /* ==============================
           PDF REPLACEMENT
        ================================ */

        if ($error === "") {

            $has_file =
                isset($_FILES["pdf_file"]) &&
                is_array($_FILES["pdf_file"]) &&
                $_FILES["pdf_file"]["error"]
                !== UPLOAD_ERR_NO_FILE;


            if ($has_file) {

                $file = $_FILES["pdf_file"];


                /* ==============================
                   UPLOAD ERROR
                ================================ */

                if (
                    $file["error"]
                    !== UPLOAD_ERR_OK
                ) {

                    $error =
                        "File upload failed. Please try again.";

                } elseif ($file["size"] <= 0) {

                    $error =
                        "The selected file is empty.";

                } elseif (
                    $file["size"]
                    > 10 * 1024 * 1024
                ) {

                    $error =
                        "PDF size must not exceed 10 MB.";

                } elseif (
                    !is_uploaded_file(
                        $file["tmp_name"]
                    )
                ) {

                    $error =
                        "Invalid file upload.";

                } else {


                    /* ==============================
                       EXTENSION CHECK
                    ================================ */

                    $extension = strtolower(
                        pathinfo(
                            $file["name"],
                            PATHINFO_EXTENSION
                        )
                    );


                    if ($extension !== "pdf") {

                        $error =
                            "Only PDF files are allowed.";

                    } else {


                        /* ==============================
                           MIME TYPE CHECK
                        ================================ */

                        $finfo =
                            finfo_open(
                                FILEINFO_MIME_TYPE
                            );

                        $mime_type = "";

                        if ($finfo) {

                            $mime_type =
                                finfo_file(
                                    $finfo,
                                    $file["tmp_name"]
                                );

                            finfo_close($finfo);
                        }


                        if (
                            $mime_type
                            !== "application/pdf"
                        ) {

                            $error =
                                "Invalid PDF file. Please upload a valid PDF.";

                        } else {


                            /* ==============================
                               PDF HEADER CHECK
                            ================================ */

                            $file_handle =
                                fopen(
                                    $file["tmp_name"],
                                    "rb"
                                );

                            $file_header = "";


                            if ($file_handle) {

                                $file_header =
                                    fread(
                                        $file_handle,
                                        4
                                    );

                                fclose($file_handle);
                            }


                            if (
                                $file_header !== "%PDF"
                            ) {

                                $error =
                                    "The uploaded file is not a valid PDF.";

                            } else {


                                /* ==============================
                                   UPLOAD DIRECTORY
                                ================================ */

                                $upload_dir =
                                    "../uploads/notes/";


                                if (!is_dir($upload_dir)) {

                                    if (
                                        !mkdir(
                                            $upload_dir,
                                            0755,
                                            true
                                        )
                                    ) {

                                        $error =
                                            "Unable to create upload folder.";
                                    }
                                }


                                if ($error === "") {


                                    /* ==============================
                                       SECURE RANDOM FILE NAME
                                    ================================ */

                                    try {

                                        $new_file_name =
                                            bin2hex(
                                                random_bytes(16)
                                            )
                                            . ".pdf";

                                    } catch (Exception $e) {

                                        $error =
                                            "Unable to create secure file name.";
                                    }


                                    if ($error === "") {

                                        $new_file_path =
                                            $upload_dir
                                            . $new_file_name;


                                        /* ==============================
                                           MOVE NEW PDF
                                        ================================ */

                                        if (
                                            move_uploaded_file(
                                                $file["tmp_name"],
                                                $new_file_path
                                            )
                                        ) {

                                            $new_file_uploaded = true;

                                        } else {

                                            $error =
                                                "Unable to upload new PDF.";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }


            /* ==============================
               UPDATE DATABASE
            ================================ */

            if ($error === "") {

                $update = $conn->prepare(
                    "UPDATE notes
                     SET
                        title = ?,
                        description = ?,
                        category_id = ?,
                        file_name = ?
                     WHERE id = ?"
                );


                if (!$update) {

                    $error =
                        "Unable to prepare note update.";

                } else {

                    $update->bind_param(
                        "ssisi",
                        $title,
                        $description,
                        $category_id,
                        $new_file_name,
                        $note_id
                    );


                    if ($update->execute()) {


                        /* ==============================
                           DELETE OLD PDF
                           ONLY AFTER DB SUCCESS
                        ================================ */

                        if (
                            $new_file_uploaded &&
                            $old_file_name !== "" &&
                            $old_file_name
                            !== $new_file_name
                        ) {

                            $old_file_path =
                                "../uploads/notes/"
                                . basename(
                                    $old_file_name
                                );

                            if (
                                is_file(
                                    $old_file_path
                                )
                            ) {

                                unlink(
                                    $old_file_path
                                );
                            }
                        }


                        /* ==============================
                           UPDATE CURRENT DATA
                        ================================ */

                        $note["title"] =
                            $title;

                        $note["description"] =
                            $description;

                        $note["category_id"] =
                            $category_id;

                        $note["file_name"] =
                            $new_file_name;


                        $success =
                            "Note updated successfully.";

                    } else {


                        /* ==============================
                           DELETE NEW PDF IF DB FAILED
                        ================================ */

                        if (
                            $new_file_uploaded &&
                            is_file(
                                $new_file_path
                            )
                        ) {

                            unlink(
                                $new_file_path
                            );
                        }

                        $error =
                            "Unable to update note.";
                    }


                    $update->close();
                }
            }
        }
    }
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

<title>Edit Note - Admin</title>


<style>

/* ==============================
   RESET
================================ */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
}


/* ==============================
   BODY
================================ */

body {

    background: #f4f7fb;

    min-height: 100vh;

    padding: 40px 20px;
}


/* ==============================
   CONTAINER
================================ */

.container {

    max-width: 700px;

    margin: auto;
}


/* ==============================
   CARD
================================ */

.card {

    background: white;

    padding: 32px;

    border-radius: 18px;

    box-shadow:
        0 10px 30px
        rgba(0,0,0,0.08);
}

h1 {

    margin-bottom: 8px;

    color: #222;
}

.subtitle {

    color: #777;

    margin-bottom: 25px;
}


/* ==============================
   MESSAGES
================================ */

.success {

    background: #dcfce7;

    color: #166534;

    padding: 12px;

    border-radius: 10px;

    margin-bottom: 20px;
}

.error {

    background: #fee2e2;

    color: #991b1b;

    padding: 12px;

    border-radius: 10px;

    margin-bottom: 20px;
}


/* ==============================
   FORM
================================ */

label {

    display: block;

    margin-bottom: 8px;

    font-weight: bold;

    color: #333;
}

input,
textarea,
select {

    width: 100%;

    padding: 13px;

    margin-bottom: 20px;

    border: 1px solid #ddd;

    border-radius: 10px;

    font-size: 15px;

    background: white;
}

textarea {

    height: 130px;

    resize: vertical;
}

input:focus,
textarea:focus,
select:focus {

    outline: none;

    border-color: #4f46e5;

    box-shadow:
        0 0 0 3px
        rgba(79,70,229,0.10);
}


/* ==============================
   CURRENT FILE
================================ */

.current-file {

    background: #f1f5ff;

    padding: 13px;

    border-radius: 10px;

    margin-bottom: 20px;

    color: #444;

    word-break: break-all;
}


/* ==============================
   FILE INFO
================================ */

.file-info {

    display: block;

    color: #777;

    font-size: 13px;

    margin-top: -12px;

    margin-bottom: 22px;

    line-height: 1.5;
}


/* ==============================
   BUTTONS
================================ */

.buttons {

    display: flex;

    gap: 12px;
}

button,
.back-btn {

    border: none;

    padding: 13px 22px;

    border-radius: 10px;

    cursor: pointer;

    text-decoration: none;

    font-weight: bold;

    font-size: 14px;
}

button {

    background: #4f46e5;

    color: white;

    flex: 1;
}

button:hover {

    background: #3730a3;
}

.back-btn {

    background: #e5e7eb;

    color: #222;

    display: flex;

    align-items: center;

    justify-content: center;
}

.back-btn:hover {

    background: #d1d5db;
}


/* ==============================
   MOBILE
================================ */

@media (max-width: 600px) {

    body {
        padding: 20px 15px;
    }

    .card {
        padding: 24px 20px;
    }

    .buttons {
        flex-direction: column;
    }

    button,
    .back-btn {
        width: 100%;
    }

}

</style>

</head>


<body>


<div class="container">


    <div class="card">


        <h1>
            Edit Note
        </h1>


        <p class="subtitle">
            Update note information or replace the PDF file.
        </p>


        <?php if ($success !== ""): ?>

            <div class="success">

                <?php
                echo htmlspecialchars($success);
                ?>

            </div>

        <?php endif; ?>


        <?php if ($error !== ""): ?>

            <div class="error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
            autocomplete="off"
        >


            <label for="title">
                Note Title *
            </label>

            <input
                type="text"
                id="title"
                name="title"
                maxlength="200"
                value="<?php
                    echo htmlspecialchars(
                        $note["title"]
                    );
                ?>"
                required
            >


            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                maxlength="5000"
                placeholder="Enter note description"
            ><?php
                echo htmlspecialchars(
                    $note["description"]
                );
            ?></textarea>


            <label for="category_id">
                Category *
            </label>

            <select
                id="category_id"
                name="category_id"
                required
            >

                <option value="">
                    Select Category
                </option>


                <?php if ($categories): ?>

                    <?php while (
                        $category =
                        $categories->fetch_assoc()
                    ): ?>

                        <option
                            value="<?php
                                echo (int)
                                    $category["id"];
                            ?>"
                            <?php
                            if (
                                (int)$category["id"]
                                ===
                                (int)$note["category_id"]
                            ) {
                                echo "selected";
                            }
                            ?>
                        >

                            <?php
                            echo htmlspecialchars(
                                $category["category_name"]
                            );
                            ?>

                        </option>

                    <?php endwhile; ?>

                <?php endif; ?>

            </select>


            <div class="current-file">

                <strong>
                    Current PDF:
                </strong>

                <?php
                echo htmlspecialchars(
                    $note["file_name"]
                );
                ?>

            </div>


            <label for="pdf_file">
                Replace PDF (Optional)
            </label>

            <input
                type="file"
                id="pdf_file"
                name="pdf_file"
                accept=".pdf,application/pdf"
            >


            <small class="file-info">

                Leave empty to keep the current PDF.
                PDF files only. Maximum size: 10 MB.

            </small>


            <div class="buttons">

                <button type="submit">
                    Update Note
                </button>


                <a
                    href="manage-notes.php"
                    class="back-btn"
                >
                    Back
                </a>

            </div>


        </form>

    </div>

</div>


</body>

</html>