<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";

/* Add Category */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $category_name = trim($_POST["category_name"]);

    if ($category_name === "") {

        $error = "Please enter a category name.";

    } else {

        $check = $conn->prepare("
            SELECT id
            FROM categories
            WHERE category_name = ?
        ");

        $check->bind_param("s", $category_name);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $error = "This category already exists.";

        } else {

            $insert = $conn->prepare("
                INSERT INTO categories (category_name)
                VALUES (?)
            ");

            $insert->bind_param("s", $category_name);

            if ($insert->execute()) {

                $message = "Category added successfully.";

            } else {

                $error = "Unable to add category.";
            }
        }
    }
}

/* Delete Category */
if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {

    $category_id = intval($_GET["delete"]);

    $check_notes = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM notes
        WHERE category_id = ?
    ");

    $check_notes->bind_param("i", $category_id);
    $check_notes->execute();

    $note_result = $check_notes->get_result();
    $note_data = $note_result->fetch_assoc();

    if ($note_data["total"] > 0) {

        $error = "This category cannot be deleted because notes are using it.";

    } else {

        $delete = $conn->prepare("
            DELETE FROM categories
            WHERE id = ?
        ");

        $delete->bind_param("i", $category_id);

        if ($delete->execute()) {

            $message = "Category deleted successfully.";

        } else {

            $error = "Unable to delete category.";
        }
    }
}

/* Get Categories */
$categories = $conn->query("
    SELECT
        c.id,
        c.category_name,
        c.created_at,
        COUNT(n.id) AS note_count
    FROM categories c
    LEFT JOIN notes n
        ON c.id = n.category_id
    GROUP BY c.id
    ORDER BY c.category_name ASC
");

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Categories</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f7fb;
            padding: 40px 20px;
        }

        .container {
            max-width: 950px;
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

        .add-form {
            display: flex;
            gap: 12px;
        }

        .add-form input {
            flex: 1;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 9px;
            font-size: 15px;
        }

        .add-form button {
            border: none;
            padding: 13px 22px;
            background: #4f46e5;
            color: white;
            border-radius: 9px;
            cursor: pointer;
            font-weight: bold;
        }

        .add-form button:hover {
            background: #3730a3;
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

        table {
            width: 100%;
            border-collapse: collapse;
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

        .count {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 13px;
            font-weight: bold;
        }

        .delete {
            text-decoration: none;
            background: #fee2e2;
            color: #b91c1c;
            padding: 8px 12px;
            border-radius: 7px;
            font-weight: bold;
        }

        .delete:hover {
            background: #fecaca;
        }

        @media (max-width: 650px) {

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .add-form {
                flex-direction: column;
            }

            table {
                font-size: 14px;
            }

            th,
            td {
                padding: 10px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <div class="header">

        <h1>Manage Categories</h1>

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

    <!-- Add Category -->

    <div class="card">

        <h2 style="margin-bottom: 18px;">
            Add New Category
        </h2>

        <form
            method="POST"
            class="add-form"
        >

            <input
                type="text"
                name="category_name"
                placeholder="Enter category name"
                required
            >

            <button type="submit">
                + Add Category
            </button>

        </form>

    </div>

    <!-- Category List -->

    <div class="card">

        <h2 style="margin-bottom: 18px;">
            All Categories
        </h2>

        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Category</th>

                    <th>Notes</th>

                    <th>Created</th>

                    <th>Action</th>

                </tr>

            </thead>

            <tbody>

                <?php if ($categories->num_rows > 0): ?>

                    <?php while ($category = $categories->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo $category["id"]; ?>
                            </td>

                            <td>
                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $category["category_name"]
                                    );
                                    ?>
                                </strong>
                            </td>

                            <td>

                                <span class="count">

                                    <?php
                                    echo $category["note_count"];
                                    ?>

                                    Notes

                                </span>

                            </td>

                            <td>
                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime($category["created_at"])
                                );
                                ?>
                            </td>

                            <td>

                                <?php if ($category["note_count"] == 0): ?>

                                    <a
                                        href="?delete=<?php echo $category["id"]; ?>"
                                        class="delete"
                                        onclick="return confirm('Are you sure you want to delete this category?');"
                                    >
                                        Delete
                                    </a>

                                <?php else: ?>

                                    <span style="color:#999;">
                                        In Use
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="5">
                            No categories found.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>