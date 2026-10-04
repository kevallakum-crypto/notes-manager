<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

$message = "";
$message_type = "";


/* ==============================
   ADD NOTE
================================ */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = isset($_POST["title"])
        ? trim($_POST["title"])
        : "";

    $description = isset($_POST["description"])
        ? trim($_POST["description"])
        : "";

    $category_id = isset($_POST["category_id"])
        ? filter_var($_POST["category_id"], FILTER_VALIDATE_INT)
        : false;


    /* ==============================
       TEXT VALIDATION
    ================================ */

    if ($title === "") {

        $message = "Please enter a note title.";
        $message_type = "error";

    } elseif (strlen($title) > 200) {

        $message = "Note title cannot exceed 200 characters.";
        $message_type = "error";

    } elseif (strlen($description) > 5000) {

        $message = "Description cannot exceed 5000 characters.";
        $message_type = "error";

    } elseif ($category_id === false || $category_id <= 0) {

        $message = "Please select a valid category.";
        $message_type = "error";

    } elseif (
        !isset($_FILES["note_file"]) ||
        !is_array($_FILES["note_file"])
    ) {

        $message = "Please select a PDF file.";
        $message_type = "error";

    } else {

        $file = $_FILES["note_file"];


        /* ==============================
           UPLOAD ERROR CHECK
        ================================ */

        if ($file["error"] !== UPLOAD_ERR_OK) {

            $message = "File upload failed. Please try again.";
            $message_type = "error";

        } elseif ($file["size"] <= 0) {

            $message = "The selected file is empty.";
            $message_type = "error";

        } elseif ($file["size"] > 10 * 1024 * 1024) {

            $message = "File size must not exceed 10 MB.";
            $message_type = "error";

        } else {

            $original_name = $file["name"];
            $tmp_name = $file["tmp_name"];

            $extension = strtolower(
                pathinfo(
                    $original_name,
                    PATHINFO_EXTENSION
                )
            );


            /* ==============================
               EXTENSION CHECK
            ================================ */

            if ($extension !== "pdf") {

                $message = "Only PDF files are allowed.";
                $message_type = "error";

            } elseif (!is_uploaded_file($tmp_name)) {

                $message = "Invalid file upload.";
                $message_type = "error";

            } else {


                /* ==============================
                   MIME TYPE CHECK
                ================================ */

                $finfo = finfo_open(FILEINFO_MIME_TYPE);

                $mime_type = $finfo
                    ? finfo_file($finfo, $tmp_name)
                    : "";

                if ($finfo) {
                    finfo_close($finfo);
                }


                if ($mime_type !== "application/pdf") {

                    $message =
                        "Invalid PDF file. Please upload a valid PDF.";

                    $message_type = "error";

                } else {


                    /* ==============================
                       PDF SIGNATURE CHECK
                    ================================ */

                    $file_handle = fopen($tmp_name, "rb");

                    $file_header = "";

                    if ($file_handle) {

                        $file_header =
                            fread($file_handle, 4);

                        fclose($file_handle);
                    }


                    if ($file_header !== "%PDF") {

                        $message =
                            "The uploaded file is not a valid PDF.";

                        $message_type = "error";

                    } else {


                        /* ==============================
                           CATEGORY CHECK
                        ================================ */

                        $category_check = $conn->prepare(
                            "SELECT id
                             FROM categories
                             WHERE id = ?
                             LIMIT 1"
                        );

                        if (!$category_check) {

                            $message =
                                "Unable to validate category.";

                            $message_type = "error";

                        } else {

                            $category_check->bind_param(
                                "i",
                                $category_id
                            );

                            $category_check->execute();

                            $category_result =
                                $category_check->get_result();

                            if (
                                $category_result->num_rows === 0
                            ) {

                                $message =
                                    "Selected category does not exist.";

                                $message_type = "error";

                            } else {


                                /* ==============================
                                   UPLOAD DIRECTORY
                                ================================ */

                                $upload_folder =
                                    "../uploads/notes/";


                                if (!is_dir($upload_folder)) {

                                    if (
                                        !mkdir(
                                            $upload_folder,
                                            0755,
                                            true
                                        )
                                    ) {

                                        $message =
                                            "Unable to create upload folder.";

                                        $message_type = "error";
                                    }
                                }


                                if (
                                    $message_type !== "error" ||
                                    $message === ""
                                ) {


                                    /* ==============================
                                       SAFE FILE NAME
                                    ================================ */

                                    $new_file_name =
                                        bin2hex(random_bytes(16))
                                        . ".pdf";

                                    $destination =
                                        $upload_folder
                                        . $new_file_name;


                                    /* ==============================
                                       MOVE FILE
                                    ================================ */

                                    if (
                                        move_uploaded_file(
                                            $tmp_name,
                                            $destination
                                        )
                                    ) {


                                        /* ==============================
                                           DATABASE INSERT
                                        ================================ */

                                        $stmt = $conn->prepare(
                                            "INSERT INTO notes
                                            (
                                                title,
                                                description,
                                                category_id,
                                                file_name,
                                                uploaded_by
                                            )
                                            VALUES (?, ?, ?, ?, ?)"
                                        );


                                        if ($stmt) {

                                            $uploaded_by =
                                                (int) $_SESSION["user_id"];

                                            $stmt->bind_param(
                                                "ssisi",
                                                $title,
                                                $description,
                                                $category_id,
                                                $new_file_name,
                                                $uploaded_by
                                            );


                                            if ($stmt->execute()) {

                                                $message =
                                                    "Note uploaded successfully.";

                                                $message_type =
                                                    "success";

                                            } else {

                                                /* Delete file if DB insert fails */
                                                if (
                                                    file_exists(
                                                        $destination
                                                    )
                                                ) {
                                                    unlink($destination);
                                                }

                                                $message =
                                                    "Unable to save note. Please try again.";

                                                $message_type =
                                                    "error";
                                            }

                                            $stmt->close();

                                        } else {

                                            if (
                                                file_exists(
                                                    $destination
                                                )
                                            ) {
                                                unlink($destination);
                                            }

                                            $message =
                                                "Unable to save note.";

                                            $message_type =
                                                "error";
                                        }

                                    } else {

                                        $message =
                                            "File upload failed.";

                                        $message_type =
                                            "error";
                                    }
                                }
                            }

                            $category_check->close();
                        }
                    }
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

<title>Add Note - Admin</title>


<style>

/* ==============================
   RESET
================================ */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}


/* ==============================
   BODY
================================ */

body {
    background: #f4f6fb;
    min-height: 100vh;
}


/* ==============================
   NAVBAR
================================ */

.navbar {

    height: 70px;

    background: #20243a;

    color: white;

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 0 40px;
}

.logo {

    font-size: 22px;

    font-weight: bold;
}

.nav-links {

    display: flex;

    gap: 15px;
}

.nav-links a {

    color: white;

    text-decoration: none;

    padding: 9px 15px;

    border-radius: 7px;

    transition: 0.2s;
}

.nav-links a:hover {
    background: #667eea;
}


/* ==============================
   CONTAINER
================================ */

.container {

    max-width: 800px;

    margin: 40px auto;

    padding: 0 20px;
}


/* ==============================
   BACK LINK
================================ */

.back {

    display: inline-block;

    margin-bottom: 20px;

    color: #667eea;

    text-decoration: none;

    font-weight: bold;
}

.back:hover {
    text-decoration: underline;
}


/* ==============================
   FORM BOX
================================ */

.form-box {

    background: white;

    padding: 35px;

    border-radius: 18px;

    box-shadow:
        0 5px 25px
        rgba(0,0,0,0.08);
}

h1 {

    margin-bottom: 8px;

    color: #222;
}

.subtitle {

    color: #777;

    margin-bottom: 30px;
}


/* ==============================
   MESSAGE
================================ */

.message {

    padding: 12px;

    border-radius: 8px;

    margin-bottom: 20px;

    line-height: 1.5;
}

.success {

    background: #e7f7ed;

    color: #18753c;
}

.error {

    background: #ffe7e7;

    color: #c00000;
}


/* ==============================
   FORM
================================ */

.form-group {

    margin-bottom: 20px;
}

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

    border: 1px solid #ddd;

    border-radius: 8px;

    outline: none;

    font-size: 15px;

    background: white;
}

textarea {

    height: 120px;

    resize: vertical;
}

input:focus,
textarea:focus,
select:focus {

    border-color: #667eea;

    box-shadow:
        0 0 0 3px
        rgba(102,126,234,0.10);
}

input[type="file"] {

    padding: 10px;

    cursor: pointer;
}


/* ==============================
   FILE INFO
================================ */

.file-info {

    color: #777;

    font-size: 13px;

    margin-top: 7px;

    line-height: 1.5;
}


/* ==============================
   BUTTON
================================ */

button {

    width: 100%;

    padding: 14px;

    border: none;

    border-radius: 8px;

    background: #667eea;

    color: white;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;

    transition: 0.2s;
}

button:hover {

    background: #5568d9;

    transform: translateY(-1px);
}

button:active {
    transform: translateY(0);
}


/* ==============================
   MOBILE
================================ */

@media (max-width: 600px) {

    .navbar {

        height: auto;

        padding: 15px 20px;

        flex-direction: column;

        gap: 12px;
    }

    .nav-links {

        width: 100%;

        justify-content: center;
    }

    .container {

        margin: 25px auto;

        padding: 0 15px;
    }

    .form-box {

        padding: 25px 20px;

        border-radius: 14px;
    }

    h1 {
        font-size: 25px;
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

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </div>

</nav>


<div class="container">


    <a
        href="dashboard.php"
        class="back"
    >
        ← Back to Dashboard
    </a>


    <div class="form-box">


        <h1>
            Add New Note
        </h1>


        <p class="subtitle">
            Upload a study note for students.
        </p>


        <?php if ($message !== ""): ?>

            <div
                class="message <?php echo htmlspecialchars($message_type); ?>"
            >
                <?php
                echo htmlspecialchars($message);
                ?>
            </div>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
            autocomplete="off"
        >


            <div class="form-group">

                <label for="title">
                    Note Title *
                </label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    placeholder="Enter note title"
                    maxlength="200"
                    value="<?php
                        echo isset($_POST["title"])
                            ? htmlspecialchars($_POST["title"])
                            : "";
                    ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    maxlength="5000"
                    placeholder="Enter note description"
                ><?php
                    echo isset($_POST["description"])
                        ? htmlspecialchars($_POST["description"])
                        : "";
                ?></textarea>

            </div>


            <div class="form-group">

                <label for="category_id">
                    Category *
                </label>

                <select
                    name="category_id"
                    id="category_id"
                    required
                >

                    <option value="">
                        Select Category
                    </option>


                    <?php

                    $categories = $conn->query(
                        "SELECT id, category_name
                         FROM categories
                         ORDER BY category_name ASC"
                    );

                    if ($categories):

                        while (
                            $category =
                            $categories->fetch_assoc()
                        ):

                    ?>

                        <option
                            value="<?php
                                echo (int) $category["id"];
                            ?>"
                            <?php
                            if (
                                isset($_POST["category_id"]) &&
                                (int)$_POST["category_id"]
                                === (int)$category["id"]
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

                    <?php

                        endwhile;

                    endif;

                    ?>

                </select>

            </div>


            <div class="form-group">

                <label for="note_file">
                    Select PDF File *
                </label>

                <input
                    type="file"
                    id="note_file"
                    name="note_file"
                    accept=".pdf,application/pdf"
                    required
                >


                <div class="file-info">

                    PDF files only.
                    Maximum size: 10 MB.

                </div>

            </div>


            <button type="submit">
                Upload Note
            </button>


        </form>

    </div>

</div>


</body>

</html>