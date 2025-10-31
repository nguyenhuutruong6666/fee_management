<?php
session_start();
include("../config/db.php");

$message = "";
$step = 1; // bước 1: nhập email, bước 2: nhập mật khẩu mới

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Bước 1: nhập email
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        $result = $conn->query("SELECT * FROM users WHERE email='$email' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $_SESSION['reset_email'] = $email;
            $step = 2;
        } else {
            $message = "<p class='error'>❌ Không tìm thấy tài khoản với email này.</p>";
        }
    }

    // Bước 2: đặt lại mật khẩu
    if (isset($_POST['new_password']) && isset($_SESSION['reset_email'])) {
        $newPass = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        $email = $_SESSION['reset_email'];

        if ($newPass !== $confirm) {
            $message = "<p class='error'>⚠️ Mật khẩu xác nhận không khớp!</p>";
            $step = 2;
        } elseif (strlen($newPass) < 6) {
            $message = "<p class='error'>⚠️ Mật khẩu phải có ít nhất 6 ký tự.</p>";
            $step = 2;
        } else {
            $conn->query("UPDATE users SET password='$newPass' WHERE email='$email'");
            unset($_SESSION['reset_email']);
            $message = "<p class='success'>✅ Đặt lại mật khẩu thành công! <a href='login.php'>Đăng nhập ngay</a></p>";
            $step = 3;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quên mật khẩu - Hệ thống Đoàn phí</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <style>
    body {
      background: linear-gradient(135deg, #74b9ff, #a29bfe);
      font-family: "Segoe UI", sans-serif;
    }
    .reset-box {
      width: 380px;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      padding: 30px;
      margin: 100px auto;
      text-align: center;
    }
    .reset-box h2 {
      color: #2d3436;
      margin-bottom: 15px;
    }
    input {
      width: 90%;
      padding: 10px;
      margin: 8px 0;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 15px;
    }
    button {
      width: 95%;
      background: #0984e3;
      color: white;
      border: none;
      padding: 10px;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
      margin-top: 10px;
    }
    button:hover {
      background: #74b9ff;
    }
    .success, .error {
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 10px;
    }
    .error { background: #ffe0e0; color: #c0392b; }
    .success { background: #dfe6e9; color: #27ae60; }
  </style>
</head>
<body>
  <div class="reset-box">
    <h2>🔐 Quên mật khẩu</h2>
    <?= $message ?>
    
    <?php if ($step === 1): ?>
      <form method="POST">
        <input type="email" name="email" placeholder="Nhập email đã đăng ký" required>
        <button type="submit">Gửi liên kết đặt lại</button>
      </form>
    <?php elseif ($step === 2): ?>
      <form method="POST">
        <input type="password" name="new_password" placeholder="Mật khẩu mới" required>
        <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu mới" required>
        <button type="submit">Đặt lại mật khẩu</button>
      </form>
    <?php endif; ?>
    <a href="login.php" class="back-btn">⬅ Quay lại đăng nhập</a>
  </div>
</body>
</html>
