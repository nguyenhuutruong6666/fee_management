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

// Nếu là Admin thì có thể xem tài khoản người khác qua GET id
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
        <img src="../public/img/avt.png" alt="avatar">
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
        <tr>
          <th>Tên đăng nhập:</th>
          <td><?= htmlspecialchars($user['userName']) ?></td>
        </tr>
        <tr>
          <th>Họ và tên:</th>
          <td><?= htmlspecialchars($user['fullName']) ?></td>
        </tr>
        <tr>
          <th>Email:</th>
          <td><?= htmlspecialchars($user['email']) ?></td>
        </tr>
        <tr>
          <th>Năm sinh:</th>
          <td>
            <?= isset($user['birthDate']) && $user['birthDate'] ? date("d/m/Y", strtotime($user['birthDate'])) : "Chưa cập nhật" ?>
          </td>
        </tr>
        <tr>
          <th>Giới tính:</th>
          <td>
            <?php
              if (isset($user['gender'])) {
                echo ($user['gender'] === 'M') ? 'Nam' :
                    (($user['gender'] === 'F') ? 'Nữ' : 'Khác');
              } else {
                echo "Chưa cập nhật";
              }
            ?>
          </td>
        </tr>
        <tr>
          <th>Ngày vào Đoàn:</th>
          <td>
            <?= isset($user['joinDate']) && $user['joinDate'] ? date("d/m/Y", strtotime($user['joinDate'])) : "Chưa cập nhật" ?>
          </td>
        </tr>
        <tr>
          <th>Vai trò:</th>
          <td><?= htmlspecialchars($user['role']) ?></td>
        </tr>
        <tr>
          <th>Đơn vị:</th>
          <td><?= htmlspecialchars($user['unit']) ?></td>
        </tr>
        <tr>
          <th>Ngày tạo:</th>
          <td><?= date("d/m/Y", strtotime($user['createdAt'])) ?></td>
        </tr>
      </table>
    </div>
    
    <div class="profile-footer">
      <a href="edit_user.php?id=<?= $user['userId'] ?>" class="btn-edit">✏️ Chỉnh sửa thông tin</a>
      <a href="change_password.php" class="btn-password">🔑 Đổi mật khẩu</a>
    </div>
  </div>
</div>

<style>
.profile-card {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  padding: 20px;
  max-width: 700px;
  margin: 30px auto;
}
.profile-header {
  display: flex;
  align-items: center;
  border-bottom: 1px solid #eee;
  padding-bottom: 10px;
}
.profile-header .avatar img {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  margin-right: 20px;
}
.profile-header .info h3 {
  margin: 0;
  color: #2d3436;
}
.profile-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
}
.profile-table th {
  text-align: left;
  width: 30%;
  color: #555;
  padding: 8px;
}
.profile-table td {
  color: #2d3436;
  padding: 8px;
}
.profile-footer {
  text-align: right;
  margin-top: 15px;
}
.btn-edit, .btn-password {
  display: inline-block;
  padding: 8px 14px;
  border-radius: 6px;
  text-decoration: none;
  margin-left: 8px;
  font-size: 15px;
}
.btn-edit { background: #0984e3; color: white; }
.btn-edit:hover { background: #74b9ff; }
.btn-password { background: #00b894; color: white; }
.btn-password:hover { background: #55efc4; }
</style>

<?php include("../includes/footer.php"); ?>
