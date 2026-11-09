<?php
// includes/nav.php
// Shared navigation bar included at the top of every host page

// Detect the current file name (e.g. 'dashboard.php') so we can highlight the active menu link
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    .vimos-topbar {
        background: #0066cc;
        color: #fff;
        padding: 14px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .vimos-topbar .brand {
        margin: 0;
        font-size: 1.2rem;
        font-weight: bold;
        color: #fff;
        text-decoration: none;
    }

    .vimos-topbar nav {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .vimos-topbar nav a {
        color: #fff;
        text-decoration: none;
        font-size: 0.9rem;
        padding: 6px 12px;
        border-radius: 4px;
        border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .vimos-topbar nav a:hover,
    .vimos-topbar nav a.active {
        background: #fff;
        color: #0066cc;
        font-weight: bold;
    }
</style>

<header class="vimos-topbar">
    <a href="dashboard.php" class="brand">🏢 VimoS Host Portal</a>
    <nav>
        <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="visit_requests.php" class="<?= $current_page === 'visit_requests.php' ? 'active' : '' ?>">Visit
            requests</a>
        <a href="visitors.php"
            class="<?= in_array($current_page, ['visitors.php', 'visitor_edit.php'], true) ? 'active' : '' ?>">Visitor
            directory</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>