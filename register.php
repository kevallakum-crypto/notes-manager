<?php

session_start();

require_once "config/database.php";

$message = "";
$message_type = "";


/* ==============================
   ALREADY LOGGED IN
================================ */

if (isset($_SESSION["user_id"])) {

    if (
        isset($_SESSION["user_role"]) &&
        $_SESSION["user_role"] === "admin"
    ) {
        header("Location: admin/dashboard.php");
        exit;
    }

    header("Location: user/dashboard.php");
    exit;
}


/* ==============================
   REGISTRATION
================================ */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = isset($_POST["name"])
        ? trim($_POST["name"])
        : "";

    $email = isset($_POST["email"])
        ? trim($_POST["email"])
        : "";

    $password = isset($_POST["password"])
        ? $_POST["password"]
        : "";


    /* ==============================
       VALIDATION
    ================================ */

    if (
        $name === "" ||
        $email === "" ||
        $password === ""
    ) {

        $message = "Please fill all fields.";
        $message_type = "error";

    } elseif (strlen($name) < 2) {

        $message =
            "Name must contain at least 2 characters.";

        $message_type = "error";

    } elseif (strlen($name) > 100) {

        $message =
            "Name cannot exceed 100 characters.";

        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message =
            "Please enter a valid email address.";

        $message_type = "error";

    } elseif (strlen($email) > 150) {

        $message =
            "Email address is too long.";

        $message_type = "error";

    } elseif (strlen($password) < 6) {

        $message =
            "Password must be at least 6 characters.";

        $message_type = "error";

    } elseif (strlen($password) > 255) {

        $message =
            "Password is too long.";

        $message_type = "error";

    } else {


        /* ==============================
           CHECK DUPLICATE EMAIL
        ================================ */

        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );


        if ($check) {

            $check->bind_param(
                "s",
                $email
            );

            $check->execute();

            $result =
                $check->get_result();


            if ($result->num_rows > 0) {

                $message =
                    "Email already registered.";

                $message_type = "error";

                $check->close();

            } else {

                $check->close();


                /* ==============================
                   HASH PASSWORD
                ================================ */

                $hashed_password =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                /* ==============================
                   CREATE USER
                ================================ */

                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (name, email, password, role)
                    VALUES (?, ?, ?, 'user')"
                );


                if ($stmt) {

                    $stmt->bind_param(
                        "sss",
                        $name,
                        $email,
                        $hashed_password
                    );


                    if ($stmt->execute()) {

                        $stmt->close();

                        header(
                            "Location: login.php?registered=1"
                        );

                        exit;

                    } else {

                        $message =
                            "Registration failed. Please try again.";

                        $message_type = "error";

                        $stmt->close();
                    }

                } else {

                    $message =
                        "Unable to create account. Please try again.";

                    $message_type = "error";
                }
            }

        } else {

            $message =
                "Unable to process registration.";

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

<title>Create Account - NoteHub</title>


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
            circle at top right,
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
   REGISTER CARD
================================ */

.register-box {

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

.error {

    background: #fff1f2;

    color: #dc2626;

    border: 1px solid #fecdd3;
}


/* ==============================
   FORM
================================ */

.form-group {
    margin-bottom: 17px;
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
   PASSWORD HINT
================================ */

.password-hint {

    color: #94a3b8;

    font-size: 11px;

    margin-top: -10px;

    margin-bottom: 17px;
}


/* ==============================
   BUTTON
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
   LOGIN LINK
================================ */

.login-link {

    text-align: center;

    color: #64748b;

    font-size: 13px;

    margin-top: 22px;
}

.login-link a {

    color: #2563eb;

    text-decoration: none;

    font-weight: 700;
}

.login-link a:hover {
    text-decoration: underline;
}


/* ==============================
   MOBILE
================================ */

@media (max-width: 480px) {

    body {
        padding: 15px;
    }

    .register-box {
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


<div class="register-box">


    <div class="logo">
        Note<span>Hub</span>
    </div>


    <h1>
        Create Account
    </h1>


    <p class="subtitle">
        Create your Notes Management account
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
        action="register.php"
        autocomplete="on"
    >


        <div class="form-group">

            <label for="name">
                Full Name
            </label>

            <input
                type="text"
                id="name"
                name="name"
                placeholder="Enter your full name"
                maxlength="100"
                autocomplete="name"
                required
            >

        </div>


        <div class="form-group">

            <label for="email">
                Email Address
            </label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                maxlength="150"
                autocomplete="email"
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
                placeholder="Create a password"
                minlength="6"
                maxlength="255"
                autocomplete="new-password"
                required
            >

        </div>


        <div class="password-hint">
            Password must be at least 6 characters.
        </div>


        <button type="submit">
            Create Account
        </button>


    </form>


    <div class="login-link">

        Already have an account?

        <a href="login.php">
            Login
        </a>

    </div>


</div>


</body>

</html>