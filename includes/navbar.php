<nav class="sidebar">
  <ul>
    <li><a href="dashboard.php">🏠 Trang chủ</a></li>

    <?php if (isset($_SESSION['user']) && $_SESSION['user']['isAdmin']): ?>
      <li><a href="users.php">👥 Quản lý người dùng</a></li>
      <li><a href="units.php">🏫 Cấu hình tổ chức</a></li>
    <?php endif; ?>

    <?php
    // ✅ Hiển thị menu “Quản lý đoàn viên” cho các cấp: Admin, BCH Trường, BCH Khoa, BCH Chi đoàn
    if (isset($_SESSION['user'])) {
        $role = $_SESSION['user']['role_name'] ?? '';
        if (
            $_SESSION['user']['isAdmin'] == 1 ||
            in_array($role, ['BCH Trường', 'BCH Khoa', 'BCH Chi đoàn'])
        ) {
            echo '<li><a href="members.php">📋 Quản lý đoàn viên</a></li>';
        }
    }
    ?>

    <li><a href="profile.php">👤 Thông tin tài khoản</a></li>
  </ul>
</nav>
