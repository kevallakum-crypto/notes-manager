<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

$message = "";

if (isset($_GET["deleted"])) {
    $message = "Note deleted successfully.";
}

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if (!empty($search)) {

    $search_value = "%" . $search . "%";

    $stmt = $conn->prepare(
        "SELECT 
            notes.id,
            notes.title,
            notes.description,
            notes.file_name,
            notes.created_at,
            categories.category_name
        FROM notes
        INNER JOIN categories
            ON notes.category_id = categories.id
        WHERE notes.title LIKE ?
           OR categories.category_name LIKE ?
        ORDER BY notes.id DESC"
    );

    $stmt->bind_param(
        "ss",
        $search_value,
        $search_value
    );

    $stmt->execute();

    $notes = $stmt->get_result();

} else {

    $notes = $conn->query(
        "SELECT
            notes.id,
            notes.title,
            notes.description,
            notes.file_name,
            notes.created_at,
            categories.category_name
        FROM notes
        INNER JOIN categories
            ON notes.category_id = categories.id
        ORDER BY notes.id DESC"
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Manage Notes</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #f4f6fb;
}

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
    gap: 12px;
}

.nav-links a {
    color: white;
    text-decoration: none;

    padding: 9px 14px;
    border-radius: 7px;
}

.nav-links a:hover {
    background: #667eea;
}

.container {
    max-width: 1200px;
    margin: 35px auto;
    padding: 0 20px;
}

.top-section {
    display: flex;
    justify-content: space-between;
    align-items: center;

    margin-bottom: 25px;
}

.top-section h1 {
    color: #222;
}

.add-btn {
    background: #667eea;
    color: white;

    padding: 11px 18px;
    border-radius: 8px;

    text-decoration: none;
}

.search-box {
    background: white;
    padding: 20px;

    border-radius: 12px;
    margin-bottom: 25px;

    box-shadow:
        0 4px 15px rgba(0,0,0,0.06);
}

.search-form {
    display: flex;
    gap: 10px;
}

.search-form input {
    flex: 1;

    padding: 12px;

    border: 1px solid #ddd;
    border-radius: 8px;

    outline: none;
}

.search-form button {
    padding: 12px 22px;

    border: none;
    border-radius: 8px;

    background: #667eea;
    color: white;

    cursor: pointer;
}

.clear-btn {
    padding: 12px 18px;

    background: #eee;
    color: #333;

    text-decoration: none;

    border-radius: 8px;
}

.message {
    background: #e7f7ed;
    color: #18753c;

    padding: 12px;
    border-radius: 8px;

    margin-bottom: 20px;
}

.notes-table {
    width: 100%;
    border-collapse: collapse;
}

.table-container {
    background: white;

    border-radius: 14px;

    overflow-x: auto;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.07);
}

.notes-table th {
    background: #20243a;
    color: white;

    padding: 15px;

    text-align: left;
}

.notes-table td {
    padding: 15px;

    border-bottom: 1px solid #eee;
}

.notes-table tr:hover {
    background: #f8f9ff;
}

.title {
    font-weight: bold;
    color: #333;
}

.category {
    display: inline-block;

    background: #eeeaff;
    color: #667eea;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 13px;
}

.actions {
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
}

.actions a {
    text-decoration: none;

    padding: 7px 10px;

    border-radius: 6px;

    font-size: 13px;
}

.view {
    background: #e7f1ff;
    color: #1261a0;
}

.edit {
    background: #fff3cd;
    color: #856404;
}

.delete {
    background: #ffe5e5;
    color: #c00000;
}

.no-notes {
    text-align: center;
    padding: 40px;

    color: #777;
}

@media(max-width:700px) {

    .navbar {
        padding: 0 15px;
    }

    .top-section {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .search-form {
        flex-direction: column;
    }

    .notes-table th,
    .notes-table td {
        white-space: nowrap;
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

    <div class="top-section">

        <h1>
            Manage Notes
        </h1>

        <a href="add-note.php" class="add-btn">
            + Add New Note
        </a>

    </div>

    <?php if (!empty($message)): ?>

        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <div class="search-box">

        <form method="GET" class="search-form">

            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Search by note title or category..."
            >

            <button type="submit">
                Search
            </button>

            <?php if (!empty($search)): ?>

                <a href="manage-notes.php" class="clear-btn">
                    Clear
                </a>

            <?php endif; ?>

        </form>

    </div>

    <div class="table-container">

        <table class="notes-table">

            <thead>

                <tr>

                    <th>
                        ID
                    </th>

                    <th>
                        Note Title
                    </th>

                    <th>
                        Category
                    </th>

                    <th>
                        Date
                    </th>

                    <th>
                        Actions
                    </th>

                </tr>

            </thead>

            <tbody>

            <?php if ($notes && $notes->num_rows > 0): ?>

                <?php while ($note = $notes->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo $note["id"]; ?>
                        </td>

                        <td>

                            <div class="title">

                                <?php
                                echo htmlspecialchars(
                                    $note["title"]
                                );
                                ?>

                            </div>

                        </td>

                        <td>

                            <span class="category">

                                <?php
                                echo htmlspecialchars(
                                    $note["category_name"]
                                );
                                ?>

                            </span>

                        </td>

                        <td>

                            <?php
                            echo date(
                                "d M Y",
                                strtotime($note["created_at"])
                            );
                            ?>

                        </td>

                        <td>

                            <div class="actions">

                                <a
                                    href="../uploads/notes/<?php echo urlencode($note["file_name"]); ?>"
                                    target="_blank"
                                    class="view"
                                >
                                    View
                                </a>

                                <a
                                    href="edit-note.php?id=<?php echo $note["id"]; ?>"
                                    class="edit"
                                >
                                    Edit
                                </a>

                                <a
                                    href="delete-note.php?id=<?php echo $note["id"]; ?>"
                                    class="delete"
                                    onclick="return confirm('Are you sure you want to delete this note?');"
                                >
                                    Delete
                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="5" class="no-notes">
                        No notes found.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>