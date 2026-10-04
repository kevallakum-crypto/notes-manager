<?php
session_start();
require_once "config/database.php";

$isLoggedIn = isset($_SESSION["user_id"]);
$userRole = $_SESSION["user_role"] ?? "";

$notesCount = 0;
$categoriesCount = 0;
$usersCount = 0;
$latestNotes = [];
$categories = [];

$result = $conn->query("SELECT COUNT(*) AS total FROM notes");
if ($result) {
    $notesCount = (int)$result->fetch_assoc()["total"];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM categories");
if ($result) {
    $categoriesCount = (int)$result->fetch_assoc()["total"];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($result) {
    $usersCount = (int)$result->fetch_assoc()["total"];
}

$result = $conn->query("
    SELECT
        notes.id,
        notes.title,
        notes.description,
        categories.category_name,
        notes.created_at
    FROM notes
    INNER JOIN categories
        ON notes.category_id = categories.id
    ORDER BY notes.created_at DESC
    LIMIT 6
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $latestNotes[] = $row;
    }
}

$result = $conn->query("
    SELECT id, category_name
    FROM categories
    ORDER BY category_name ASC
    LIMIT 8
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

function shortDescription($text, $length = 100)
{
    $text = trim(strip_tags($text));

    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, $length) . "...";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>NoteHub - Study Notes Management System</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f6f8fc;
            color: #1f2937;
            line-height: 1.6;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: min(1180px, 92%);
            margin: auto;
        }

        /* ================= NAVBAR ================= */

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-inner {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            font-size: 25px;
            font-weight: 800;
            color: #2563eb;
            white-space: nowrap;
        }

        .logo span {
            color: #111827;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-links a {
            font-size: 15px;
            font-weight: 600;
            color: #4b5563;
            transition: 0.2s;
        }

        .nav-links a:hover {
            color: #2563eb;
        }

        .nav-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 18px;
            border-radius: 9px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: 0.2s;
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-outline {
            border: 1px solid #d1d5db;
            color: #374151;
            background: #ffffff;
        }

        .btn-outline:hover {
            border-color: #2563eb;
            color: #2563eb;
        }

        /* ================= HERO ================= */

        .hero {
            padding: 75px 0 65px;
            background:
                radial-gradient(circle at top right, #dbeafe 0, transparent 35%),
                linear-gradient(135deg, #eff6ff, #ffffff);
        }

        .hero-content {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            align-items: center;
            gap: 55px;
        }

        .hero-text h1 {
            font-size: clamp(38px, 5vw, 62px);
            line-height: 1.08;
            color: #111827;
            margin-bottom: 22px;
        }

        .hero-text h1 span {
            color: #2563eb;
        }

        .hero-text p {
            max-width: 650px;
            color: #6b7280;
            font-size: 18px;
            margin-bottom: 30px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .search-box {
            margin-top: 28px;
            max-width: 650px;
            display: flex;
            background: #ffffff;
            padding: 7px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.08);
        }

        .search-box input {
            flex: 1;
            min-width: 0;
            border: none;
            outline: none;
            padding: 13px 14px;
            font-size: 15px;
            color: #111827;
        }

        .search-box button {
            border: none;
            background: #2563eb;
            color: white;
            padding: 0 20px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
        }

        .hero-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
        }

        .hero-card-icon {
            width: 62px;
            height: 62px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            font-size: 30px;
            margin-bottom: 20px;
        }

        .hero-card h3 {
            font-size: 23px;
            margin-bottom: 10px;
            color: #111827;
        }

        .hero-card p {
            color: #6b7280;
            margin-bottom: 20px;
        }

        .feature-list {
            list-style: none;
        }

        .feature-list li {
            padding: 9px 0;
            color: #374151;
            font-size: 15px;
        }

        .feature-list li::before {
            content: "✓";
            color: #2563eb;
            font-weight: bold;
            margin-right: 10px;
        }

        /* ================= STATS ================= */

        .stats {
            padding: 30px 0;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .stat-card {
            text-align: center;
            padding: 18px;
        }

        .stat-card h2 {
            font-size: 32px;
            color: #2563eb;
        }

        .stat-card p {
            color: #6b7280;
            font-size: 14px;
        }

        /* ================= SECTIONS ================= */

        .section {
            padding: 70px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 38px;
        }

        .section-header h2 {
            font-size: 34px;
            color: #111827;
            margin-bottom: 8px;
        }

        .section-header p {
            color: #6b7280;
        }

        /* ================= CATEGORIES ================= */

        .category-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .category-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 22px;
            transition: 0.25s;
        }

        .category-card:hover {
            transform: translateY(-4px);
            border-color: #93c5fd;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07);
        }

        .category-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            border-radius: 10px;
            font-size: 21px;
            margin-bottom: 14px;
        }

        .category-card h3 {
            font-size: 17px;
            margin-bottom: 5px;
        }

        .category-card p {
            color: #6b7280;
            font-size: 13px;
        }

        /* ================= NOTES ================= */

        .notes-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .note-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 15px;
            padding: 23px;
            transition: 0.25s;
            display: flex;
            flex-direction: column;
        }

        .note-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        }

        .note-category {
            display: inline-block;
            width: fit-content;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 10px;
            border-radius: 20px;
            margin-bottom: 14px;
        }

        .note-card h3 {
            font-size: 19px;
            color: #111827;
            margin-bottom: 9px;
        }

        .note-card p {
            color: #6b7280;
            font-size: 14px;
            flex: 1;
        }

        .note-date {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 18px;
            margin-bottom: 16px;
        }

        .note-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 40px;
            background: #2563eb;
            color: #ffffff;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        .note-btn:hover {
            background: #1d4ed8;
        }

        .empty-state {
            text-align: center;
            padding: 45px 20px;
            background: #ffffff;
            border: 1px dashed #d1d5db;
            border-radius: 14px;
            color: #6b7280;
        }

        /* ================= CTA ================= */

        .cta {
            padding: 65px 0;
        }

        .cta-box {
            background: #2563eb;
            color: #ffffff;
            border-radius: 20px;
            padding: 50px;
            text-align: center;
        }

        .cta-box h2 {
            font-size: 35px;
            margin-bottom: 10px;
        }

        .cta-box p {
            max-width: 650px;
            margin: 0 auto 25px;
            opacity: 0.9;
        }

        .cta-box .btn {
            background: #ffffff;
            color: #2563eb;
        }

        /* ================= FOOTER ================= */

        footer {
            background: #111827;
            color: #d1d5db;
            padding: 30px 0;
        }

        .footer-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .footer-logo {
            color: #ffffff;
            font-size: 20px;
            font-weight: 800;
        }

        .footer-text {
            font-size: 13px;
            color: #9ca3af;
        }

        /* ================= TABLET ================= */

        @media (max-width: 900px) {

            .nav-links {
                display: none;
            }

            .hero {
                padding: 55px 0;
            }

            .hero-content {
                grid-template-columns: 1fr;
                gap: 35px;
            }

            .hero-text {
                text-align: center;
            }

            .hero-text p {
                margin-left: auto;
                margin-right: auto;
            }

            .hero-actions {
                justify-content: center;
            }

            .search-box {
                margin-left: auto;
                margin-right: auto;
            }

            .category-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .notes-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* ================= MOBILE ================= */

        @media (max-width: 600px) {

            .container {
                width: 92%;
            }

            .nav-inner {
                min-height: 62px;
            }

            .logo {
                font-size: 21px;
            }

            .nav-buttons {
                gap: 6px;
            }

            .nav-buttons .btn {
                min-height: 36px;
                padding: 0 11px;
                font-size: 12px;
            }

            .hero {
                padding: 45px 0;
            }

            .hero-text h1 {
                font-size: 38px;
            }

            .hero-text p {
                font-size: 15px;
            }

            .hero-actions {
                flex-direction: column;
            }

            .hero-actions .btn {
                width: 100%;
            }

            .search-box {
                flex-direction: column;
                gap: 7px;
                padding: 7px;
            }

            .search-box input {
                width: 100%;
            }

            .search-box button {
                min-height: 42px;
            }

            .hero-card {
                padding: 23px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .stat-card {
                padding: 13px;
                border-bottom: 1px solid #f0f0f0;
            }

            .section {
                padding: 50px 0;
            }

            .section-header h2 {
                font-size: 28px;
            }

            .category-grid {
                grid-template-columns: 1fr;
            }

            .notes-grid {
                grid-template-columns: 1fr;
            }

            .cta-box {
                padding: 35px 20px;
            }

            .cta-box h2 {
                font-size: 28px;
            }

            .footer-inner {
                flex-direction: column;
                text-align: center;
            }
        }

        /* ================= SMALL MOBILE ================= */

        @media (max-width: 380px) {

            .logo {
                font-size: 19px;
            }

            .nav-buttons .btn {
                padding: 0 8px;
                font-size: 11px;
            }

            .hero-text h1 {
                font-size: 33px;
            }

            .hero-card {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<!-- ================= NAVBAR ================= -->

<nav class="navbar">
    <div class="container nav-inner">

        <a href="index.php" class="logo">
            Note<span>Hub</span>
        </a>

        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="notes.php">Browse Notes</a>
            <a href="#categories">Categories</a>
            <a href="#latest">Latest Notes</a>
        </div>

        <div class="nav-buttons">

            <?php if ($isLoggedIn): ?>

                <?php if ($userRole === "admin"): ?>
                    <a href="admin/dashboard.php" class="btn btn-outline">
                        Dashboard
                    </a>
                <?php else: ?>
                    <a href="user/dashboard.php" class="btn btn-outline">
                        Dashboard
                    </a>
                <?php endif; ?>

                <a href="logout.php" class="btn btn-primary">
                    Logout
                </a>

            <?php else: ?>

                <a href="login.php" class="btn btn-outline">
                    Login
                </a>

                <a href="register.php" class="btn btn-primary">
                    Register
                </a>

            <?php endif; ?>

        </div>

    </div>
</nav>


<!-- ================= HERO ================= -->

<section class="hero">

    <div class="container hero-content">

        <div class="hero-text">

            <h1>
                Your Study Notes,
                <span>All in One Place.</span>
            </h1>

            <p>
                Discover, organize and access useful study notes
                anytime. Make your learning easier with NoteHub.
            </p>

            <div class="hero-actions">

                <a href="notes.php" class="btn btn-primary">
                    Browse Notes
                </a>

                <?php if (!$isLoggedIn): ?>
                    <a href="register.php" class="btn btn-outline">
                        Create Account
                    </a>
                <?php else: ?>
                    <?php if ($userRole === "admin"): ?>
                        <a href="admin/add-note.php" class="btn btn-outline">
                            Add Note
                        </a>
                    <?php else: ?>
                        <a href="user/saved-notes.php" class="btn btn-outline">
                            Saved Notes
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

            </div>

            <form action="notes.php" method="GET" class="search-box">

                <input
                    type="text"
                    name="search"
                    placeholder="Search notes, subjects or topics..."
                    maxlength="100"
                >

                <button type="submit">
                    Search
                </button>

            </form>

        </div>


        <div class="hero-card">

            <div class="hero-card-icon">
                📚
            </div>

            <h3>
                Smart Notes Management
            </h3>

            <p>
                A simple platform to find and manage academic
                notes efficiently.
            </p>

            <ul class="feature-list">
                <li>Easy note searching</li>
                <li>Organized categories</li>
                <li>Save notes for later</li>
                <li>Download PDF notes</li>
                <li>Responsive on all devices</li>
            </ul>

        </div>

    </div>

</section>


<!-- ================= STATS ================= -->

<section class="stats">

    <div class="container stats-grid">

        <div class="stat-card">
            <h2><?php echo $notesCount; ?>+</h2>
            <p>Available Notes</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $categoriesCount; ?>+</h2>
            <p>Categories</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $usersCount; ?>+</h2>
            <p>Registered Users</p>
        </div>

    </div>

</section>


<!-- ================= CATEGORIES ================= -->

<section class="section" id="categories">

    <div class="container">

        <div class="section-header">
            <h2>Explore Categories</h2>
            <p>Find notes based on your subject and interest.</p>
        </div>

        <?php if (!empty($categories)): ?>

            <div class="category-grid">

                <?php foreach ($categories as $category): ?>

                    <a
                        href="notes.php?category=<?php echo (int)$category["id"]; ?>"
                        class="category-card"
                    >

                        <div class="category-icon">
                            📘
                        </div>

                        <h3>
                            <?php echo htmlspecialchars($category["category_name"]); ?>
                        </h3>

                        <p>
                            Explore related study notes
                        </p>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state">
                No categories available yet.
            </div>

        <?php endif; ?>

    </div>

</section>


<!-- ================= LATEST NOTES ================= -->

<section class="section" id="latest">

    <div class="container">

        <div class="section-header">
            <h2>Latest Notes</h2>
            <p>Recently added study materials.</p>
        </div>

        <?php if (!empty($latestNotes)): ?>

            <div class="notes-grid">

                <?php foreach ($latestNotes as $note): ?>

                    <div class="note-card">

                        <span class="note-category">
                            <?php
                            echo htmlspecialchars(
                                $note["category_name"]
                            );
                            ?>
                        </span>

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $note["title"]
                            );
                            ?>
                        </h3>

                        <p>
                            <?php
                            echo htmlspecialchars(
                                shortDescription(
                                    $note["description"] ?? ""
                                )
                            );
                            ?>
                        </p>

                        <div class="note-date">
                            Added:
                            <?php
                            echo date(
                                "d M Y",
                                strtotime($note["created_at"])
                            );
                            ?>
                        </div>

                        <?php if ($isLoggedIn): ?>

                            <a
                                href="view-note.php?id=<?php echo (int)$note["id"]; ?>"
                                class="note-btn"
                            >
                                View Note
                            </a>

                        <?php else: ?>

                            <a
                                href="login.php"
                                class="note-btn"
                            >
                                Login to View
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state">
                <h3>No notes available</h3>
                <p>
                    Notes will appear here when an administrator
                    uploads them.
                </p>
            </div>

        <?php endif; ?>

        <div style="text-align:center; margin-top:30px;">
            <a href="notes.php" class="btn btn-primary">
                View All Notes
            </a>
        </div>

    </div>

</section>


<!-- ================= CTA ================= -->

<section class="cta">

    <div class="container">

        <div class="cta-box">

            <h2>
                Ready to Start Learning?
            </h2>

            <p>
                Browse available notes, save useful materials
                and keep your study resources organized.
            </p>

            <?php if ($isLoggedIn): ?>

                <a href="notes.php" class="btn">
                    Browse Notes
                </a>

            <?php else: ?>

                <a href="register.php" class="btn">
                    Create Free Account
                </a>

            <?php endif; ?>

        </div>

    </div>

</section>


<!-- ================= FOOTER ================= -->

<footer>

    <div class="container footer-inner">

        <div class="footer-logo">
            NoteHub
        </div>

        <div class="footer-text">
            Study Notes Management System
        </div>

        <div class="footer-text">
            © <?php echo date("Y"); ?> NoteHub. All rights reserved.
        </div>

    </div>

</footer>

</body>
</html>