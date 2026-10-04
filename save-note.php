<?php

session_start();

require_once "config/database.php";

header("Content-Type: application/json");


/* ==============================
   LOGIN CHECK
================================ */

if (!isset($_SESSION["user_id"])) {

    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);

    exit;
}


/* ==============================
   NOTE ID CHECK
================================ */

if (
    !isset($_POST["note_id"]) ||
    !is_numeric($_POST["note_id"])
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid note."
    ]);

    exit;
}


$user_id = intval($_SESSION["user_id"]);

$note_id = intval($_POST["note_id"]);


/* ==============================
   CHECK NOTE EXISTS
================================ */

$check_note = $conn->prepare("
    SELECT id
    FROM notes
    WHERE id = ?
");

$check_note->bind_param(
    "i",
    $note_id
);

$check_note->execute();

$note_result = $check_note->get_result();


if ($note_result->num_rows !== 1) {

    echo json_encode([
        "success" => false,
        "message" => "Note not found."
    ]);

    exit;
}


/* ==============================
   CHECK SAVED
================================ */

$check = $conn->prepare("
    SELECT id
    FROM saved_notes
    WHERE user_id = ?
    AND note_id = ?
");

$check->bind_param(
    "ii",
    $user_id,
    $note_id
);

$check->execute();

$result = $check->get_result();


/* ==============================
   UNSAVE
================================ */

if ($result->num_rows > 0) {

    $delete = $conn->prepare("
        DELETE FROM saved_notes
        WHERE user_id = ?
        AND note_id = ?
    ");

    $delete->bind_param(
        "ii",
        $user_id,
        $note_id
    );

    $delete->execute();


    echo json_encode([
        "success" => true,
        "saved" => false,
        "message" => "Note removed from saved notes."
    ]);

    exit;
}


/* ==============================
   SAVE
================================ */

$insert = $conn->prepare("
    INSERT INTO saved_notes
    (user_id, note_id)
    VALUES (?, ?)
");

$insert->bind_param(
    "ii",
    $user_id,
    $note_id
);

$insert->execute();


echo json_encode([
    "success" => true,
    "saved" => true,
    "message" => "Note saved successfully."
]);

exit;

?>