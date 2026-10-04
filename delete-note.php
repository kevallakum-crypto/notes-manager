<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

if (!isset($_GET["id"])) {
    header("Location: manage-notes.php");
    exit;
}

$note_id = intval($_GET["id"]);

$stmt = $conn->prepare(
    "SELECT file_name FROM notes WHERE id = ?"
);

$stmt->bind_param("i", $note_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 1) {

    $note = $result->fetch_assoc();

    $file_path =
        "../uploads/notes/" . $note["file_name"];

    if (file_exists($file_path)) {
        unlink($file_path);
    }

    $delete = $conn->prepare(
        "DELETE FROM notes WHERE id = ?"
    );

    $delete->bind_param("i", $note_id);

    $delete->execute();
}

header("Location: manage-notes.php?deleted=1");

exit;

?>