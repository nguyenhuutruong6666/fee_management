<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// Nếu chưa đăng nhập → quay lại trang login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['user'];

// Nếu là Admin thì có thể xem toàn bộ user (hoặc lọc theo GET id)
if ($currentUser['isAdmin'] && isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    $query = "SELECT * FROM users WHERE userId = $userId";
} else {
    // Người dùng thường chỉ xem chính mình
    $userId = intval($currentUser['userId']);
    $query = "SELECT * FROM users WHERE userId = $userId";
}

$result = $conn->query($query);
$user = $result->fetch_assoc();

if (!$user) {
    echo "<div class='container'><p>❌ Không tìm thấy thông tin tài khoản.</p></div>";
    include("../includes/footer.php");
    exit();
}
?>

<div class="container">
  <h2>👤 Thông tin tài khoản</h2>

  <div class="profile-card">
    <div class="profile-header">
      <div class="avatar">
        <img src="../public/img/user.png" alt="avatar">
      </div>
      <div class="info">
        <h3><?= htmlspecialchars($user['fullName']) ?></h3>
        <p><b>Vai trò:</b> <?= htmlspecialchars($user['role']) ?></p>
        <p><b>Đơn vị:</b> <?= htmlspecialchars($user['unit']) ?></p>
        <p><b>Trạng thái:</b> <?= ($user['isAdmin'] ? '🛡️ Quản trị viên' : '✅ Hoạt động') ?></p>
      </div>
    </div>

    <div class="profile-body">
      <table class="table profile-table">
        <tr><th>Tên đăng nhập:</th><td><?= htmlspecialchars($user['userName']) ?></td></tr>
        <tr><th>Họ và tên:</th><td><?= htmlspecialchars($user['fullName']) ?></td></tr>
        <tr><th>Email:</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
        <tr><th>Vai trò:</th><td><?= htmlspecialchars($user['role']) ?></td></tr>
        <tr><th>Đơn vị:</th><td><?= htmlspecialchars($user['unit']) ?></td></tr>
        <tr><th>Ngày tạo:</th><td><?= date("d/m/Y", strtotime($user['createdAt'])) ?></td></tr>
      </table>
    </div>

    <div class="profile-footer">
      <a href="edit_user.php?id=<?= $user['userId'] ?>" class="btn-edit">✏️ Chỉnh sửa thông tin</a>
    </div>
  </div>
</div>

<?php include("../includes/footer.php"); ?>
