<nav class="sidebar">
    <ul>
        <li><a href="dashboard.php">Trang chủ</a></li>
        <?php if (isset($_SESSION['user']) && $_SESSION['user']['isAdmin']): ?>
            <li><a href="users.php">Quản lý người dùng</a></li>
        <?php endif; ?>
        <li><a href="profile.php">Thông tin tài khoản</a></li>
    </ul>
</nav>
