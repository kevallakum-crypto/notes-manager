<?php

session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid note ID.");
}

$user_id = $_SESSION["user_id"];
$note_id = intval($_GET["id"]);


/* Get Note */

$stmt = $conn->prepare("
    SELECT id, title, file_name
    FROM notes
    WHERE id = ?
");

$stmt->bind_param("i", $note_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Note not found.");
}

$note = $result->fetch_assoc();


/* File Path */

$file_path = __DIR__ . "/uploads/notes/" . $note["file_name"];

if (!file_exists($file_path)) {
    die("PDF file not found.");
}


/* Save Download History */

$download = $conn->prepare("
    INSERT INTO downloads
    (user_id, note_id)
    VALUES (?, ?)
");

$download->bind_param(
    "ii",
    $user_id,
    $note_id
);

$download->execute();


/* Download PDF */

$download_name = basename($note["title"]) . ".pdf";

header("Content-Type: application/pdf");

header(
    "Content-Disposition: attachment; filename=\"" .
    $download_name .
    "\""
);

header("Content-Length: " . filesize($file_path));

header("Cache-Control: no-cache, must-revalidate");

readfile($file_path);

exit;

?>