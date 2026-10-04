<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";

/* Change User Role */

if (
    isset($_GET["role"]) &&
    isset($_GET["id"]) &&
    is_numeric($_GET["id"])
) {

    $user_id = intval($_GET["id"]);
    $new_role = $_GET["role"];

    if ($new_role === "admin" || $new_role === "user") {

        if ($user_id == $_SESSION["user_id"]) {

            $error = "You cannot change your own role.";

        } else {

            $stmt = $conn->prepare("
                UPDATE users
                SET role = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "si",
                $new_role,
                $user_id
            );

            if ($stmt->execute()) {

                $message = "User role updated successfully.";

            } else {

                $error = "Unable to update user role.";
            }
        }
    }
}


/* Delete User */

if (
    isset($_GET["delete"]) &&
    is_numeric($_GET["delete"])
) {

    $user_id = intval($_GET["delete"]);

    if ($user_id == $_SESSION["user_id"]) {

        $error = "You cannot delete your own account.";

    } else {

        $stmt = $conn->prepare("
            DELETE FROM users
            WHERE id = ?
        ");

        $stmt->bind_param(
            "i",
            $user_id
        );

        if ($stmt->execute()) {

            $message = "User deleted successfully.";

        } else {

            $error = "Unable to delete user.";
        }
    }
}


/* Search */

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if ($search !== "") {

    $search_value = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            email,
            role,
            created_at
        FROM users
        WHERE name LIKE ?
        OR email LIKE ?
        ORDER BY id DESC
    ");

    $stmt->bind_param(
        "ss",
        $search_value,
        $search_value
    );

    $stmt->execute();

    $users = $stmt->get_result();

} else {

    $users = $conn->query("
        SELECT
            id,
            name,
            email,
            role,
            created_at
        FROM users
        ORDER BY id DESC
    ");
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

    <title>Manage Users</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f7fb;
            padding: 35px 20px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        h1 {
            color: #222;
        }

        .back {
            text-decoration: none;
            background: #e5e7eb;
            color: #222;
            padding: 11px 18px;
            border-radius: 9px;
            font-weight: bold;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.07);
            margin-bottom: 25px;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-form input {
            flex: 1;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 9px;
            font-size: 15px;
        }

        .search-form button {
            padding: 13px 22px;
            border: none;
            border-radius: 9px;
            background: #4f46e5;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .clear {
            text-decoration: none;
            background: #e5e7eb;
            color: #333;
            padding: 13px 18px;
            border-radius: 9px;
            font-weight: bold;
        }

        .message {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 9px;
            margin-bottom: 18px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 9px;
            margin-bottom: 18px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th,
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #f8fafc;
            color: #444;
        }

        .role {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .admin-role {
            background: #ede9fe;
            color: #6d28d9;
        }

        .user-role {
            background: #e0f2fe;
            color: #0369a1;
        }

        .action {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .make-admin,
        .make-user,
        .delete {
            text-decoration: none;
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: bold;
        }

        .make-admin {
            background: #ede9fe;
            color: #6d28d9;
        }

        .make-user {
            background: #e0f2fe;
            color: #0369a1;
        }

        .delete {
            background: #fee2e2;
            color: #b91c1c;
        }

        .make-admin:hover {
            background: #ddd6fe;
        }

        .make-user:hover {
            background: #bae6fd;
        }

        .delete:hover {
            background: #fecaca;
        }

        .you {
            color: #777;
            font-size: 12px;
        }

        @media (max-width: 650px) {

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .search-form {
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <div class="header">

        <h1>User Management</h1>

        <a
            href="dashboard.php"
            class="back"
        >
            ← Dashboard
        </a>

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


    <!-- Search -->

    <div class="card">

        <form
            method="GET"
            class="search-form"
        >

            <input
                type="text"
                name="search"
                placeholder="Search by name or email..."
                value="<?php echo htmlspecialchars($search); ?>"
            >

            <button type="submit">
                Search
            </button>

            <?php if ($search !== ""): ?>

                <a
                    href="users.php"
                    class="clear"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>

    </div>


    <!-- Users -->

    <div class="card">

        <h2 style="margin-bottom: 20px;">
            All Users
        </h2>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Name</th>

                        <th>Email</th>

                        <th>Role</th>

                        <th>Joined</th>

                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if ($users->num_rows > 0): ?>

                        <?php while ($user = $users->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?php echo $user["id"]; ?>
                                </td>

                                <td>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $user["name"]
                                        );
                                        ?>
                                    </strong>

                                    <?php if (
                                        $user["id"] == $_SESSION["user_id"]
                                    ): ?>

                                        <div class="you">
                                            This is you
                                        </div>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $user["email"]
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php if ($user["role"] === "admin"): ?>

                                        <span class="role admin-role">
                                            Admin
                                        </span>

                                    <?php else: ?>

                                        <span class="role user-role">
                                            User
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime($user["created_at"])
                                    );
                                    ?>

                                </td>

                                <td>

                                    <?php if (
                                        $user["id"] != $_SESSION["user_id"]
                                    ): ?>

                                        <div class="action">

                                            <?php if (
                                                $user["role"] === "user"
                                            ): ?>

                                                <a
                                                    href="?id=<?php echo $user["id"]; ?>&role=admin"
                                                    class="make-admin"
                                                    onclick="return confirm('Make this user an admin?');"
                                                >
                                                    Make Admin
                                                </a>

                                            <?php else: ?>

                                                <a
                                                    href="?id=<?php echo $user["id"]; ?>&role=user"
                                                    class="make-user"
                                                    onclick="return confirm('Change this admin to a normal user?');"
                                                >
                                                    Make User
                                                </a>

                                            <?php endif; ?>


                                            <a
                                                href="?delete=<?php echo $user["id"]; ?>"
                                                class="delete"
                                                onclick="return confirm('Are you sure you want to delete this user?');"
                                            >
                                                Delete
                                            </a>

                                        </div>

                                    <?php else: ?>

                                        <span class="you">
                                            Account Owner
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="6">
                                No users found.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>

</html>