<?php

/* ==============================
   DATABASE CONFIGURATION
================================ */

$host = "localhost";
$username = "root";
$password = "";
$database = "notes_management";


/* ==============================
   CREATE CONNECTION
================================ */

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);


/* ==============================
   CONNECTION CHECK
================================ */

if ($conn->connect_error) {

    die(
        "Database connection failed. Please try again later."
    );
}


/* ==============================
   CHARACTER SET
================================ */

if (!$conn->set_charset("utf8mb4")) {

    die(
        "Database configuration error."
    );
}

?>