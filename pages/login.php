<?php
session_start();
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // ✅ Cho phép đăng nhập bằng username hoặc email
    $sql = "SELECT * FROM users WHERE userName='$usernameOrEmail' OR email='$usernameOrEmail' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // ✅ So sánh mật khẩu thường (có thể đổi sang password_verify sau)
        if ($password === $user['password']) {
            $_SESSION['user'] = $user;
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "❌ Sai mật khẩu!";
        }
    } else {
        $error = "⚠️ Tài khoản không tồn tại!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng nhập - Hệ thống Quản lý Đoàn phí</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <style>
    body {
      background: linear-gradient(135deg, #74b9ff, #a29bfe);
      font-family: "Segoe UI", sans-serif;
    }
    .login-container {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .login-box {
      width: 360px;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      padding: 30px;
      text-align: center;
    }
    .login-box h2 {
      color: #2d3436;
      margin-bottom: 20px;
      font-weight: 600;
    }
    .login-box input {
      width: 90%;
      padding: 10px;
      margin: 10px 0;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 15px;
    }
    .login-box button {
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
    .login-box button:hover {
      background: #74b9ff;
    }
    .forgot {
      display: block;
      margin-top: 12px;
      font-size: 14px;
    }
    .forgot a {
      color: #0984e3;
      text-decoration: none;
    }
    .forgot a:hover {
      text-decoration: underline;
    }
    .error {
      color: #d63031;
      font-size: 15px;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-box">
      <h2>HỆ THỐNG QUẢN LÝ ĐOÀN PHÍ</h2>
      <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
      <form method="POST">
        <input type="text" name="username" placeholder="Tên đăng nhập hoặc Email" required>
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <button type="submit">Đăng nhập</button>
      </form>
      <div class="forgot">
        <a href="forgot_password.php">🔑 Quên mật khẩu?</a>
      </div>
    </div>
  </div>
</body>
</html>
