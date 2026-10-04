<?php

session_start();

require_once "config/database.php";

$message = "";
$message_type = "";


/* ==============================
   ALREADY LOGGED IN
================================ */

if (isset($_SESSION["user_id"])) {

    if (isset($_SESSION["user_role"]) && $_SESSION["user_role"] === "admin") {
        header("Location: admin/dashboard.php");
        exit;
    }

    header("Location: user/dashboard.php");
    exit;
}


/* ==============================
   REGISTRATION MESSAGE
================================ */

if (isset($_GET["registered"])) {

    $message = "Registration successful. Please login.";
    $message_type = "success";
}


/* ==============================
   LOGIN
================================ */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = isset($_POST["email"])
        ? trim($_POST["email"])
        : "";

    $password = isset($_POST["password"])
        ? $_POST["password"]
        : "";


    /* ==============================
       BASIC VALIDATION
    ================================ */

    if ($email === "" || $password === "") {

        $message = "Please enter email and password.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } else {


        /* ==============================
           FIND USER
        ================================ */

        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );


        if ($stmt) {

            $stmt->bind_param("s", $email);

            $stmt->execute();

            $result = $stmt->get_result();


            /* ==============================
               VERIFY USER
            ================================ */

            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();


                if (
                    password_verify(
                        $password,
                        $user["password"]
                    )
                ) {


                    /* ==============================
                       REGENERATE SESSION ID
                       SECURITY AGAINST SESSION FIXATION
                    ================================ */

                    session_regenerate_id(true);


                    /* ==============================
                       CREATE SESSION
                    ================================ */

                    $_SESSION["user_id"] =
                        (int) $user["id"];

                    $_SESSION["user_name"] =
                        $user["name"];

                    $_SESSION["user_email"] =
                        $user["email"];

                    $_SESSION["user_role"] =
                        $user["role"];


                    /* ==============================
                       REDIRECT BY ROLE
                    ================================ */

                    if ($user["role"] === "admin") {

                        header(
                            "Location: admin/dashboard.php"
                        );

                    } else {

                        header(
                            "Location: user/dashboard.php"
                        );
                    }

                    exit;

                } else {

                    $message =
                        "Invalid email or password.";

                    $message_type = "error";
                }

            } else {

                $message =
                    "Invalid email or password.";

                $message_type = "error";
            }


            $stmt->close();

        } else {

            $message =
                "Unable to process login. Please try again.";

            $message_type = "error";
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

<title>Login - NoteHub</title>


<style>

/* ==============================
   RESET
================================ */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {

    min-height: 100vh;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    display: flex;

    align-items: center;
    justify-content: center;

    padding: 20px;

    background:
        radial-gradient(
            circle at top left,
            #dbeafe,
            transparent 35%
        ),
        linear-gradient(
            135deg,
            #f8fbff,
            #eef4ff
        );
}


/* ==============================
   LOGIN CARD
================================ */

.login-box {

    width: 100%;
    max-width: 420px;

    background: white;

    padding: 38px;

    border-radius: 20px;

    border: 1px solid #e5eaf2;

    box-shadow:
        0 20px 50px
        rgba(15,23,42,0.10);
}


/* ==============================
   LOGO
================================ */

.logo {

    text-align: center;

    font-size: 27px;

    font-weight: 800;

    color: #172033;

    margin-bottom: 22px;
}

.logo span {
    color: #2563eb;
}


/* ==============================
   TITLE
================================ */

h1 {

    text-align: center;

    color: #172033;

    font-size: 27px;

    margin-bottom: 8px;
}

.subtitle {

    text-align: center;

    color: #64748b;

    font-size: 13px;

    margin-bottom: 26px;
}


/* ==============================
   MESSAGE
================================ */

.message {

    padding: 11px 13px;

    border-radius: 9px;

    margin-bottom: 18px;

    text-align: center;

    font-size: 13px;

    line-height: 1.5;
}

.success {

    background: #ecfdf3;

    color: #15803d;

    border: 1px solid #bbf7d0;
}

.error {

    background: #fff1f2;

    color: #dc2626;

    border: 1px solid #fecdd3;
}


/* ==============================
   FORM
================================ */

.form-group {

    margin-bottom: 18px;
}

label {

    display: block;

    color: #334155;

    font-size: 13px;

    font-weight: 700;

    margin-bottom: 7px;
}

input {

    width: 100%;

    height: 46px;

    padding: 0 13px;

    border: 1px solid #dce3ed;

    border-radius: 9px;

    outline: none;

    background: white;

    color: #172033;

    font-size: 13px;

    transition: 0.2s;
}

input:focus {

    border-color: #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(37,99,235,0.10);
}


/* ==============================
   LOGIN BUTTON
================================ */

button {

    width: 100%;

    height: 46px;

    border: none;

    border-radius: 9px;

    background: #2563eb;

    color: white;

    font-size: 14px;

    font-weight: 700;

    cursor: pointer;

    transition: 0.2s;
}

button:hover {

    background: #1d4ed8;

    transform: translateY(-1px);
}

button:active {

    transform: translateY(0);
}


/* ==============================
   REGISTER
================================ */

.register-link {

    text-align: center;

    color: #64748b;

    font-size: 13px;

    margin-top: 22px;
}

.register-link a {

    color: #2563eb;

    text-decoration: none;

    font-weight: 700;
}

.register-link a:hover {

    text-decoration: underline;
}


/* ==============================
   MOBILE
================================ */

@media (max-width: 480px) {

    body {
        padding: 15px;
    }

    .login-box {
        padding: 28px 22px;
        border-radius: 16px;
    }

    h1 {
        font-size: 24px;
    }

}

</style>

</head>


<body>


<div class="login-box">


    <div class="logo">
        Note<span>Hub</span>
    </div>


    <h1>
        Welcome Back
    </h1>


    <p class="subtitle">
        Login to your Notes Management account
    </p>


    <?php if ($message !== ""): ?>

        <div class="message <?php echo $message_type; ?>">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="login.php"
        autocomplete="on"
    >


        <div class="form-group">

            <label for="email">
                Email Address
            </label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                autocomplete="email"
                maxlength="150"
                required
            >

        </div>


        <div class="form-group">

            <label for="password">
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                autocomplete="current-password"
                maxlength="255"
                required
            >

        </div>


        <button type="submit">
            Login
        </button>


    </form>


    <div class="register-link">

        Don't have an account?

        <a href="register.php">
            Create Account
        </a>

    </div>


</div>


</body>

</html>