<?php

session_start();

require_once "config/database.php";

/* ==============================
   LOGIN PROTECTION
================================ */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];


/* ==============================
   SEARCH
================================ */

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";


/* ==============================
   CATEGORY
================================ */

$category_id =
    isset($_GET["category"]) && is_numeric($_GET["category"])
    ? intval($_GET["category"])
    : 0;


/* ==============================
   SORT
================================ */

$sort = isset($_GET["sort"])
    ? $_GET["sort"]
    : "latest";

switch ($sort) {

    case "oldest":
        $order_by = "notes.created_at ASC";
        break;

    case "az":
        $order_by = "notes.title ASC";
        break;

    case "za":
        $order_by = "notes.title DESC";
        break;

    default:
        $order_by = "notes.created_at DESC";
        break;
}


/* ==============================
   PAGINATION
================================ */

$notes_per_page = 6;

$page =
    isset($_GET["page"]) && is_numeric($_GET["page"])
    ? intval($_GET["page"])
    : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $notes_per_page;


/* ==============================
   FILTER
================================ */

$where = [];
$params = [];
$types = "";


/* Search */

if ($search !== "") {

    $where[] = "
        (
            notes.title LIKE ?
            OR notes.description LIKE ?
            OR categories.category_name LIKE ?
        )
    ";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sss";
}


/* Category */

if ($category_id > 0) {

    $where[] = "notes.category_id = ?";

    $params[] = $category_id;

    $types .= "i";
}


$where_sql = "";

if (!empty($where)) {
    $where_sql = "WHERE " . implode(" AND ", $where);
}


/* ==============================
   COUNT NOTES
================================ */

$count_sql = "
    SELECT COUNT(*) AS total
    FROM notes
    INNER JOIN categories
        ON notes.category_id = categories.id
    $where_sql
";

$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {

    $count_stmt->bind_param(
        $types,
        ...$params
    );
}

$count_stmt->execute();

$count_result = $count_stmt->get_result();

$total_notes =
    $count_result->fetch_assoc()["total"];

$total_pages =
    $total_notes > 0
    ? ceil($total_notes / $notes_per_page)
    : 1;


/* ==============================
   GET NOTES
================================ */

$sql = "
    SELECT
        notes.id,
        notes.title,
        notes.description,
        notes.file_name,
        notes.created_at,
        categories.category_name,

        CASE
            WHEN saved_notes.id IS NOT NULL
            THEN 1
            ELSE 0
        END AS is_saved

    FROM notes

    INNER JOIN categories
        ON notes.category_id = categories.id

    LEFT JOIN saved_notes
        ON notes.id = saved_notes.note_id
        AND saved_notes.user_id = ?

    $where_sql

    ORDER BY $order_by

    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

$final_types = "i" . $types . "ii";

$final_params = [];

$final_params[] = $user_id;

foreach ($params as $param) {
    $final_params[] = $param;
}

$final_params[] = $notes_per_page;
$final_params[] = $offset;

$stmt->bind_param(
    $final_types,
    ...$final_params
);

$stmt->execute();

$result = $stmt->get_result();


/* ==============================
   CATEGORIES
================================ */

$categories = $conn->query("
    SELECT
        id,
        category_name
    FROM categories
    ORDER BY category_name ASC
");


/* ==============================
   URL HELPER
================================ */

function build_url($page_number)
{

    $query = [];

    if ($GLOBALS["search"] !== "") {
        $query["search"] = $GLOBALS["search"];
    }

    if ($GLOBALS["category_id"] > 0) {
        $query["category"] = $GLOBALS["category_id"];
    }

    if ($GLOBALS["sort"] !== "latest") {
        $query["sort"] = $GLOBALS["sort"];
    }

    $query["page"] = $page_number;

    return "notes.php?" . http_build_query($query);
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

<title>Browse Notes - NoteHub</title>

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
    font-family:
        Inter,
        Arial,
        Helvetica,
        sans-serif;

    background: #f5f7fb;
    color: #172033;
    min-height: 100vh;
}


/* ==============================
   NAVBAR
================================ */

.navbar {
    height: 72px;
    background: rgba(255,255,255,0.96);

    border-bottom: 1px solid #e8ecf3;

    padding: 0 6%;

    display: flex;
    align-items: center;
    justify-content: space-between;

    position: sticky;
    top: 0;
    z-index: 1000;

    backdrop-filter: blur(12px);
}

.logo {
    text-decoration: none;
    font-size: 25px;
    font-weight: 800;
    color: #172033;
    letter-spacing: -0.5px;
}

.logo span {
    color: #2563eb;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 6px;
}

.nav-links a {
    text-decoration: none;
    color: #536176;

    font-size: 13px;
    font-weight: 600;

    padding: 10px 13px;

    border-radius: 9px;

    transition: 0.2s;
}

.nav-links a:hover {
    background: #eef4ff;
    color: #2563eb;
}

.logout {
    color: #dc2626 !important;
}

.logout:hover {
    background: #fff1f2 !important;
    color: #dc2626 !important;
}


/* ==============================
   HERO HEADER
================================ */

.page-header {
    background:
        radial-gradient(
            circle at top right,
            #dbeafe 0,
            transparent 35%
        ),
        linear-gradient(
            135deg,
            #f8fbff,
            #eef4ff
        );

    padding: 62px 6% 85px;

    border-bottom: 1px solid #e4eaf3;
}

.header-content {
    max-width: 1250px;
    margin: auto;
}

.page-header h1 {
    font-size: 42px;
    line-height: 1.15;

    letter-spacing: -1px;

    margin-bottom: 12px;
}

.page-header p {
    color: #64748b;
    font-size: 15px;
}


/* ==============================
   FILTER BOX
================================ */

.filter-section {
    width: 88%;
    max-width: 1250px;

    margin: -38px auto 30px;

    background: white;

    padding: 20px;

    border: 1px solid #e7ebf2;

    border-radius: 16px;

    box-shadow:
        0 15px 40px rgba(15,23,42,0.08);

    position: relative;
    z-index: 10;
}

.search-form {
    display: grid;

    grid-template-columns:
        minmax(200px, 1fr)
        210px
        160px
        110px;

    gap: 10px;
}

.input,
.select {
    width: 100%;

    height: 46px;

    padding: 0 14px;

    border: 1px solid #dfe5ee;

    border-radius: 9px;

    outline: none;

    background: #fff;

    color: #273449;

    font-size: 13px;

    transition: 0.2s;
}

.input::placeholder {
    color: #94a3b8;
}

.input:focus,
.select:focus {
    border-color: #2563eb;

    box-shadow:
        0 0 0 3px rgba(37,99,235,0.10);
}

.search-btn {
    height: 46px;

    border: none;

    border-radius: 9px;

    background: #2563eb;

    color: white;

    font-size: 13px;

    font-weight: 700;

    cursor: pointer;

    transition: 0.2s;
}

.search-btn:hover {
    background: #1d4ed8;

    transform: translateY(-1px);
}


/* ==============================
   CLEAR FILTER
================================ */

.clear-area {
    margin-top: 12px;
}

.clear-btn {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    text-decoration: none;

    padding: 8px 13px;

    background: #f1f5f9;

    color: #475569;

    border-radius: 8px;

    font-size: 12px;

    font-weight: 600;

    transition: 0.2s;
}

.clear-btn:hover {
    background: #e2e8f0;
}


/* ==============================
   RESULT BAR
================================ */

.result-info {
    width: 88%;
    max-width: 1250px;

    margin: 0 auto 22px;

    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 15px;
}

.result-info p {
    color: #64748b;
    font-size: 13px;
}

.result-info strong {
    color: #172033;
}

.active-filter {
    background: #eef4ff;

    color: #2563eb !important;

    padding: 7px 12px;

    border-radius: 20px;
}


/* ==============================
   NOTES CONTAINER
================================ */

.notes-container {
    width: 88%;
    max-width: 1250px;

    margin: auto;

    min-height: 350px;
}

.notes-grid {
    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 22px;
}


/* ==============================
   NOTE CARD
================================ */

.note-card {
    background: white;

    border: 1px solid #e6eaf0;

    border-radius: 16px;

    padding: 21px;

    position: relative;

    display: flex;
    flex-direction: column;

    min-height: 290px;

    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease,
        border-color 0.25s ease;
}

.note-card:hover {
    transform: translateY(-6px);

    border-color: #d4def0;

    box-shadow:
        0 18px 40px rgba(15,23,42,0.10);
}


/* ==============================
   CARD TOP
================================ */

.note-top {
    display: flex;

    align-items: center;
    justify-content: space-between;

    margin-bottom: 17px;
}

.category {
    display: inline-flex;

    align-items: center;

    max-width: 80%;

    background: #eff6ff;

    color: #1d4ed8;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 700;

    white-space: nowrap;

    overflow: hidden;
    text-overflow: ellipsis;
}

.save-btn {
    flex-shrink: 0;

    width: 36px;
    height: 36px;

    border: 1px solid #e5eaf2;

    background: #f8fafc;

    color: #64748b;

    border-radius: 50%;

    cursor: pointer;

    font-size: 18px;

    display: flex;

    align-items: center;
    justify-content: center;

    transition: 0.2s;
}

.save-btn:hover {
    background: #eef4ff;

    color: #2563eb;

    border-color: #cbdcff;

    transform: scale(1.06);
}

.save-btn.saved {
    color: #2563eb;

    background: #eff6ff;

    border-color: #bfdbfe;
}


/* ==============================
   CARD CONTENT
================================ */

.note-card h2 {
    font-size: 18px;

    line-height: 1.4;

    color: #172033;

    margin-bottom: 10px;

    display: -webkit-box;

    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;

    overflow: hidden;
}

.description {
    color: #68758a;

    font-size: 13px;

    line-height: 1.65;

    min-height: 63px;

    margin-bottom: 13px;

    display: -webkit-box;

    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;

    overflow: hidden;
}

.note-date {
    color: #94a3b8;

    font-size: 11px;

    margin-top: auto;

    padding-top: 10px;

    margin-bottom: 17px;
}


/* ==============================
   CARD ACTIONS
================================ */

.actions {
    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 9px;
}

.action {
    text-decoration: none;

    text-align: center;

    padding: 10px 8px;

    border-radius: 9px;

    font-size: 12px;

    font-weight: 700;

    transition: 0.2s;
}

.view {
    background: #2563eb;
    color: white;
}

.view:hover {
    background: #1d4ed8;

    transform: translateY(-1px);
}

.download {
    background: #eef4ff;
    color: #2563eb;
}

.download:hover {
    background: #dbeafe;
}


/* ==============================
   EMPTY STATE
================================ */

.empty-state {
    background: white;

    border: 1px solid #e6eaf0;

    border-radius: 16px;

    padding: 80px 20px;

    text-align: center;

    box-shadow:
        0 8px 25px rgba(15,23,42,0.04);
}

.empty-icon {
    width: 68px;
    height: 68px;

    margin: 0 auto 18px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #eff6ff;

    border-radius: 50%;

    font-size: 28px;
}

.empty-state h2 {
    font-size: 21px;

    margin-bottom: 8px;
}

.empty-state p {
    color: #64748b;

    font-size: 13px;
}


/* ==============================
   PAGINATION
================================ */

.pagination {
    width: 88%;
    max-width: 1250px;

    margin: 38px auto 65px;

    display: flex;

    align-items: center;
    justify-content: center;

    gap: 7px;

    flex-wrap: wrap;
}

.pagination a {
    min-width: 38px;
    height: 38px;

    padding: 0 10px;

    display: flex;

    align-items: center;
    justify-content: center;

    text-decoration: none;

    background: white;

    color: #475569;

    border: 1px solid #e1e7ef;

    border-radius: 8px;

    font-size: 13px;

    font-weight: 600;

    transition: 0.2s;
}

.pagination a:hover {
    color: #2563eb;

    border-color: #93b4f8;

    background: #f8fbff;
}

.pagination .active {
    background: #2563eb;

    color: white;

    border-color: #2563eb;
}


/* ==============================
   FOOTER
================================ */

footer {
    background: #111827;

    margin-top: 20px;

    padding: 30px 6%;

    text-align: center;
}

footer p {
    color: #94a3b8;

    font-size: 12px;
}


/* ==============================
   TABLET
================================ */

@media (max-width: 1050px) {

    .notes-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .search-form {
        grid-template-columns:
            1fr 1fr;
    }

}


/* ==============================
   MOBILE
================================ */

@media (max-width: 680px) {

    .navbar {
        height: 64px;

        padding: 0 5%;
    }

    .logo {
        font-size: 21px;
    }

    .nav-links {
        gap: 3px;
    }

    .nav-links a {
        display: none;
    }

    .nav-links a:last-child {
        display: block;
    }

    .page-header {
        padding: 43px 5% 70px;
    }

    .page-header h1 {
        font-size: 30px;
    }

    .page-header p {
        font-size: 13px;
    }

    .filter-section {
        width: 92%;

        margin-top: -32px;

        padding: 15px;
    }

    .search-form {
        grid-template-columns: 1fr;
    }

    .result-info {
        width: 92%;

        flex-direction: column;

        align-items: flex-start;
    }

    .notes-container {
        width: 92%;
    }

    .notes-grid {
        grid-template-columns: 1fr;

        gap: 16px;
    }

    .note-card {
        padding: 18px;
    }

    .pagination {
        width: 92%;
    }

}


/* ==============================
   SMALL MOBILE
================================ */

@media (max-width: 400px) {

    .page-header h1 {
        font-size: 27px;
    }

    .note-card h2 {
        font-size: 17px;
    }

    .actions {
        grid-template-columns: 1fr;
    }

}

</style>

</head>


<body>


<!-- ==============================
     NAVBAR
================================ -->

<nav class="navbar">

    <a
        href="index.php"
        class="logo"
    >
        Note<span>Hub</span>
    </a>

    <div class="nav-links">

        <a href="index.php">
            Home
        </a>

        <a href="user/dashboard.php">
            Dashboard
        </a>

        <a href="user/saved-notes.php">
            Saved Notes
        </a>

        <a
            href="logout.php"
            class="logout"
        >
            Logout
        </a>

    </div>

</nav>


<!-- ==============================
     HEADER
================================ -->

<section class="page-header">

    <div class="header-content">

        <h1>
            Browse Notes
        </h1>

        <p>
            Search, filter and explore your study materials.
        </p>

    </div>

</section>


<!-- ==============================
     FILTER SECTION
================================ -->

<div class="filter-section">

    <form
        action="notes.php"
        method="GET"
        class="search-form"
    >

        <input
            type="text"
            name="search"
            class="input"
            placeholder="Search notes, topics or categories..."
            value="<?php echo htmlspecialchars($search); ?>"
        >


        <select
            name="category"
            class="select"
        >

            <option value="">
                All Categories
            </option>

            <?php while ($category = $categories->fetch_assoc()): ?>

                <option
                    value="<?php echo $category["id"]; ?>"
                    <?php
                    echo
                    ($category_id == $category["id"])
                    ? "selected"
                    : "";
                    ?>
                >

                    <?php
                    echo htmlspecialchars(
                        $category["category_name"]
                    );
                    ?>

                </option>

            <?php endwhile; ?>

        </select>


        <select
            name="sort"
            class="select"
        >

            <option
                value="latest"
                <?php echo $sort === "latest" ? "selected" : ""; ?>
            >
                Latest
            </option>

            <option
                value="oldest"
                <?php echo $sort === "oldest" ? "selected" : ""; ?>
            >
                Oldest
            </option>

            <option
                value="az"
                <?php echo $sort === "az" ? "selected" : ""; ?>
            >
                A - Z
            </option>

            <option
                value="za"
                <?php echo $sort === "za" ? "selected" : ""; ?>
            >
                Z - A
            </option>

        </select>


        <button
            type="submit"
            class="search-btn"
        >
            Search
        </button>

    </form>


    <?php if ($search !== "" || $category_id > 0): ?>

        <div class="clear-area">

            <a
                href="notes.php"
                class="clear-btn"
            >
                Clear Filters
            </a>

        </div>

    <?php endif; ?>

</div>


<!-- ==============================
     RESULT INFO
================================ -->

<div class="result-info">

    <p>

        Showing

        <strong>
            <?php echo $total_notes; ?>
        </strong>

        note(s)

    </p>


    <?php if ($search !== ""): ?>

        <p class="active-filter">

            Search:
            <strong>
                <?php echo htmlspecialchars($search); ?>
            </strong>

        </p>

    <?php elseif ($category_id > 0): ?>

        <p class="active-filter">
            Category filter active
        </p>

    <?php endif; ?>

</div>


<!-- ==============================
     NOTES
================================ -->

<div class="notes-container">

    <?php if ($result->num_rows > 0): ?>

        <div class="notes-grid">

            <?php while ($note = $result->fetch_assoc()): ?>

                <div class="note-card">

                    <div class="note-top">

                        <span class="category">

                            <?php
                            echo htmlspecialchars(
                                $note["category_name"]
                            );
                            ?>

                        </span>


                        <button
                            type="button"
                            class="save-btn <?php echo $note["is_saved"] ? "saved" : ""; ?>"
                            data-note-id="<?php echo $note["id"]; ?>"
                            title="<?php echo $note["is_saved"] ? "Remove from saved" : "Save note"; ?>"
                            onclick="toggleSave(this)"
                        >

                            <?php
                            echo $note["is_saved"]
                                ? "★"
                                : "☆";
                            ?>

                        </button>

                    </div>


                    <h2>

                        <?php
                        echo htmlspecialchars(
                            $note["title"]
                        );
                        ?>

                    </h2>


                    <div class="description">

                        <?php

                        $description =
                            $note["description"];

                        if (empty($description)) {

                            echo "No description available.";

                        } else {

                            echo htmlspecialchars(
                                strlen($description) > 120
                                    ? substr(
                                        $description,
                                        0,
                                        120
                                    ) . "..."
                                    : $description
                            );

                        }

                        ?>

                    </div>


                    <div class="note-date">

                        Uploaded:

                        <?php
                        echo date(
                            "d M Y",
                            strtotime(
                                $note["created_at"]
                            )
                        );
                        ?>

                    </div>


                    <div class="actions">

                        <a
                            href="view-note.php?id=<?php echo $note["id"]; ?>"
                            class="action view"
                        >
                            View
                        </a>

                        <a
                            href="download.php?id=<?php echo $note["id"]; ?>"
                            class="action download"
                        >
                            Download
                        </a>

                    </div>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="empty-state">

            <div class="empty-icon">
                🔍
            </div>

            <h2>
                No Notes Found
            </h2>

            <p>
                Try another search term or category.
            </p>

        </div>

    <?php endif; ?>

</div>


<!-- ==============================
     PAGINATION
================================ -->

<?php if ($total_pages > 1): ?>

    <div class="pagination">

        <?php if ($page > 1): ?>

            <a href="<?php echo build_url($page - 1); ?>">
                ←
            </a>

        <?php endif; ?>


        <?php for ($i = 1; $i <= $total_pages; $i++): ?>

            <a
                href="<?php echo build_url($i); ?>"
                class="<?php echo $i == $page ? "active" : ""; ?>"
            >
                <?php echo $i; ?>
            </a>

        <?php endfor; ?>


        <?php if ($page < $total_pages): ?>

            <a href="<?php echo build_url($page + 1); ?>">
                →
            </a>

        <?php endif; ?>

    </div>

<?php endif; ?>


<!-- ==============================
     FOOTER
================================ -->

<footer>

    <p>
        © <?php echo date("Y"); ?> NoteHub. All rights reserved.
    </p>

</footer>


<script>

/* ==============================
   SAVE / UNSAVE NOTE
================================ */

async function toggleSave(button) {

    const noteId =
        button.getAttribute("data-note-id");

    if (!noteId) {
        return;
    }

    button.disabled = true;

    try {

        const formData = new FormData();

        formData.append(
            "note_id",
            noteId
        );

        const response = await fetch(
            "save-note.php",
            {
                method: "POST",
                body: formData
            }
        );

        const data =
            await response.json();

        if (data.success) {

            if (data.saved) {

                button.innerHTML = "★";

                button.classList.add("saved");

                button.title =
                    "Remove from saved";

            } else {

                button.innerHTML = "☆";

                button.classList.remove("saved");

                button.title =
                    "Save note";
            }

        } else {

            alert(
                data.message ||
                "Unable to update saved note."
            );
        }

    } catch (error) {

        alert(
            "Something went wrong. Please try again."
        );
    }

    button.disabled = false;
}

</script>

</body>

</html>