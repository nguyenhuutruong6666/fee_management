<nav class="sidebar">
    <ul>
        <li><a href="dashboard.php">Trang chủ</a></li>
        <?php if (isset($_SESSION['user']) && $_SESSION['user']['isAdmin']): ?>
            <li><a href="users.php">Người dùng</a></li>
        <?php endif; ?>
        <li><a href="fee_policy.php">Chính sách đoàn phí</a></li>
        <li><a href="obligations.php">Nghĩa vụ</a></li>
        <li><a href="payments.php">Giao dịch</a></li>
        <li><a href="activities.php">Hoạt động</a></li>
        <li><a href="reports.php">Báo cáo</a></li>
        <li><a href="profile.php">Thông tin tài khoản</a></li>
    </ul>
</nav>
