<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = trim($_POST['old_password']);
    $new = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    // Lấy thông tin người dùng từ DB
    $sql = "SELECT * FROM users WHERE userId = {$user['userId']} LIMIT 1";
    $result = $conn->query($sql);
    $dbUser = $result->fetch_assoc();

    if (!$dbUser) {
        $message = "<p class='error'>Không tìm thấy tài khoản.</p>";
    } elseif ($old !== $dbUser['password']) {
        $message = "<p class='error'>Mật khẩu hiện tại không đúng.</p>";
    } elseif (strlen($new) < 6) {
        $message = "<p class='error'>Mật khẩu mới phải có ít nhất 6 ký tự.</p>";
    } elseif ($new === $old) {
        $message = "<p class='error'>Mật khẩu mới không được trùng với mật khẩu cũ.</p>";
    } elseif ($new !== $confirm) {
        $message = "<p class='error'>Mật khẩu xác nhận không khớp.</p>";
    } else {
        // Cập nhật mật khẩu mới
        $conn->query("UPDATE users SET password='$new' WHERE userId = {$user['userId']}");
        $message = "<p class='success'>Đổi mật khẩu thành công!</p>";
    }
}
?>

<div class="container">
  <h2>Đổi mật khẩu</h2>
  <div class="password-box">
    <?= $message ?>
    <form method="POST">
      <label>Mật khẩu hiện tại:</label>
      <input type="password" name="old_password" required>

      <label>Mật khẩu mới:</label>
      <input type="password" name="new_password" required>

      <label>Xác nhận mật khẩu mới:</label>
      <input type="password" name="confirm_password" required>

      <button type="submit">Lưu thay đổi</button>
    </form>
  </div>
</div>

<style>
.container {
  margin-left: 240px;
  max-width: calc(100% - 300px);
}
.password-box {
  background: #fff;
  padding: 25px;
  border-radius: 10px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  max-width: 450px;
  margin: 30px auto;
}
.password-box label {
  display: block;
  margin-top: 10px;
  font-weight: bold;
  color: #2d3436;
}
.password-box input {
  width: 95%;
  padding: 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
  margin-top: 5px;
}
.password-box button {
  width: 100%;
  margin-top: 15px;
  background: #00b894;
  color: white;
  border: none;
  padding: 10px;
  border-radius: 6px;
  font-size: 16px;
  cursor: pointer;
}
.password-box button:hover {
  background: #55efc4;
}
.success {
  background: #dfe6e9;
  color: #27ae60;
  padding: 8px;
  border-radius: 6px;
  text-align: center;
}
.error {
  background: #ffe0e0;
  color: #c0392b;
  padding: 8px;
  border-radius: 6px;
  text-align: center;
}
</style>

<?php include("../includes/footer.php"); ?>
