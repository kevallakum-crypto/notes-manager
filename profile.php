<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$message = "";
$error = "";


/* Get User */

$stmt = $conn->prepare("
    SELECT id, name, email, role, created_at
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$user = $result->fetch_assoc();


/* Update Profile */

if (isset($_POST["update_profile"])) {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);

    if ($name === "" || $email === "") {

        $error = "Please fill all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        /* Check duplicate email */

        $check = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND id != ?
        ");

        $check->bind_param(
            "si",
            $email,
            $user_id
        );

        $check->execute();

        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {

            $error = "This email is already registered.";

        } else {

            $update = $conn->prepare("
                UPDATE users
                SET name = ?, email = ?
                WHERE id = ?
            ");

            $update->bind_param(
                "ssi",
                $name,
                $email,
                $user_id
            );

            if ($update->execute()) {

                $message = "Profile updated successfully.";

                $user["name"] = $name;
                $user["email"] = $email;

                $_SESSION["user_name"] = $name;
                $_SESSION["user_email"] = $email;

            } else {

                $error = "Unable to update profile.";
            }
        }
    }
}


/* Change Password */

if (isset($_POST["change_password"])) {

    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];


    if (
        $current_password === "" ||
        $new_password === "" ||
        $confirm_password === ""
    ) {

        $error = "Please fill all password fields.";

    } elseif ($new_password !== $confirm_password) {

        $error = "New passwords do not match.";

    } elseif (strlen($new_password) < 6) {

        $error = "New password must be at least 6 characters.";

    } else {

        /* Get Current Password */

        $password_stmt = $conn->prepare("
            SELECT password
            FROM users
            WHERE id = ?
        ");

        $password_stmt->bind_param(
            "i",
            $user_id
        );

        $password_stmt->execute();

        $password_result =
            $password_stmt->get_result();

        $password_data =
            $password_result->fetch_assoc();


        if (
            !password_verify(
                $current_password,
                $password_data["password"]
            )
        ) {

            $error = "Current password is incorrect.";

        } else {

            $hashed_password =
                password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );

            $update_password = $conn->prepare("
                UPDATE users
                SET password = ?
                WHERE id = ?
            ");

            $update_password->bind_param(
                "si",
                $hashed_password,
                $user_id
            );

            if ($update_password->execute()) {

                $message =
                    "Password changed successfully.";

            } else {

                $error =
                    "Unable to change password.";
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

    <title>My Profile</title>

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
            gap: 10px;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            padding: 10px 14px;
            border-radius: 8px;
            font-weight: bold;
        }

        .dashboard {
            background: #4f46e5;
            color: white !important;
        }

        .container {
            max-width: 950px;
            margin: auto;
            padding: 45px 20px;
        }

        .heading {
            margin-bottom: 25px;
        }

        .heading h1 {
            font-size: 32px;
            margin-bottom: 7px;
        }

        .heading p {
            color: #777;
        }

        .message {
            background: #dcfce7;
            color: #166534;
            padding: 13px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 13px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .card {
            background: white;
            padding: 28px;
            border-radius: 17px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
        }

        .profile-top {
            text-align: center;
            margin-bottom: 25px;
        }

        .avatar {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            background: #4f46e5;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
            margin: 0 auto 15px;
        }

        .profile-top h2 {
            margin-bottom: 5px;
        }

        .profile-top p {
            color: #777;
        }

        .info {
            margin-top: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .info-label {
            color: #777;
        }

        .role {
            color: #4f46e5;
            font-weight: bold;
            text-transform: capitalize;
        }

        h2.section-title {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #4f46e5;
        }

        button {
            width: 100%;
            border: none;
            padding: 13px;
            background: #4f46e5;
            color: white;
            border-radius: 9px;
            font-weight: bold;
            cursor: pointer;
            font-size: 15px;
        }

        button:hover {
            background: #3730a3;
        }

        .password-card {
            margin-top: 22px;
        }

        .hint {
            color: #888;
            font-size: 13px;
            margin-top: -10px;
            margin-bottom: 18px;
        }

        @media (max-width: 750px) {

            .grid {
                grid-template-columns: 1fr;
            }

            .nav-links a:not(.dashboard) {
                display: none;
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
            My Profile
        </h1>

        <p>
            Manage your account information and password.
        </p>

    </div>


    <?php if ($message !== ""): ?>

        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <div class="grid">


        <!-- Profile Information -->

        <div class="card">

            <div class="profile-top">

                <div class="avatar">

                    <?php
                    echo strtoupper(
                        substr($user["name"], 0, 1)
                    );
                    ?>

                </div>

                <h2>

                    <?php
                    echo htmlspecialchars(
                        $user["name"]
                    );
                    ?>

                </h2>

                <p>

                    <?php
                    echo htmlspecialchars(
                        $user["email"]
                    );
                    ?>

                </p>

            </div>


            <div class="info">

                <div class="info-row">

                    <span class="info-label">
                        Account ID
                    </span>

                    <strong>
                        #<?php echo $user["id"]; ?>
                    </strong>

                </div>

                <div class="info-row">

                    <span class="info-label">
                        Role
                    </span>

                    <span class="role">
                        <?php echo htmlspecialchars($user["role"]); ?>
                    </span>

                </div>

                <div class="info-row">

                    <span class="info-label">
                        Joined
                    </span>

                    <strong>

                        <?php
                        echo date(
                            "d M Y",
                            strtotime($user["created_at"])
                        );
                        ?>

                    </strong>

                </div>

            </div>

        </div>


        <!-- Edit Profile -->

        <div class="card">

            <h2 class="section-title">
                Edit Profile
            </h2>

            <form method="POST">

                <label>
                    Full Name
                </label>

                <input
                    type="text"
                    name="name"
                    value="<?php
                    echo htmlspecialchars($user["name"]);
                    ?>"
                    required
                >

                <label>
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    value="<?php
                    echo htmlspecialchars($user["email"]);
                    ?>"
                    required
                >

                <button
                    type="submit"
                    name="update_profile"
                >
                    Save Changes
                </button>

            </form>

        </div>

    </div>


    <!-- Password -->

    <div class="card password-card">

        <h2 class="section-title">
            Change Password
        </h2>

        <form method="POST">

            <label>
                Current Password
            </label>

            <input
                type="password"
                name="current_password"
                placeholder="Enter current password"
                required
            >

            <label>
                New Password
            </label>

            <input
                type="password"
                name="new_password"
                placeholder="Enter new password"
                required
            >

            <p class="hint">
                Password must contain at least 6 characters.
            </p>

            <label>
                Confirm New Password
            </label>

            <input
                type="password"
                name="confirm_password"
                placeholder="Confirm new password"
                required
            >

            <button
                type="submit"
                name="change_password"
            >
                Change Password
            </button>

        </form>

    </div>

</div>

</body>

</html>