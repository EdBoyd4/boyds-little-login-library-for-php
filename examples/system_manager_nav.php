<?php
// A sample navigation menu for a System Manager role.
// You can include this file in your secured pages (e.g., example_secure_page.php)
// using `include 'system_manager_nav.php';`
?>
<style>
    .sys-nav {
        background: #1976d2;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
        justify-content: space-between;
    }
    .sys-nav-links {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    .sys-nav a {
        color: #ffffff;
        text-decoration: none;
        font-family: sans-serif;
        font-weight: 500;
        padding: 8px 12px;
        border-radius: 4px;
        transition: background-color 0.2s ease, transform 0.1s ease;
    }
    .sys-nav a:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-1px);
    }
    .sys-nav .logout-btn {
        background: #d32f2f;
    }
    .sys-nav .logout-btn:hover {
        background: #b71c1c;
    }
    .sys-nav-title {
        color: white;
        font-family: sans-serif;
        font-weight: bold;
        font-size: 1.1em;
        margin-right: 20px;
    }
</style>

<nav class="sys-nav">
    <div class="sys-nav-links">
        <span class="sys-nav-title">System Manager</span>
        <a href="example_secure_page.php">Dashboard</a>
        <a href="add_user.php">Add User</a>
        <a href="remove_user.php">Remove User</a>
        <a href="change_password.php">Change Password</a>
    </div>
    <div>
        <a href="logout.php" class="logout-btn">Log Out</a>
    </div>
</nav>
