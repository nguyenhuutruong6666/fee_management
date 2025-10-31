<?php
session_start();
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Truy vấn user theo tên đăng nhập
    $sql = "SELECT * FROM users WHERE userName='$username' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // ✅ So sánh trực tiếp mật khẩu thường
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
  <title>Đăng nhập - Quản lý Đoàn phí</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <style>
    .login-box {
      max-width: 400px;
      margin: 100px auto;
      padding: 25px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.15);
    }
    .login-box h2 {
      text-align: center;
      color: #0056b3;
      margin-bottom: 20px;
    }
    .login-box input {
      width: 100%;
      padding: 10px;
      margin: 8px 0;
      border-radius: 5px;
      border: 1px solid #ccc;
      font-size: 15px;
    }
    .login-box button {
      width: 100%;
      background: #007bff;
      color: white;
      padding: 10px;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
    }
    .login-box button:hover {
      background: #0056b3;
    }
    .error {
      color: red;
      text-align: center;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <h2>Đăng nhập hệ thống</h2>
    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
    <form method="POST">
      <input type="text" name="username" placeholder="Tên đăng nhập" required>
      <input type="password" name="password" placeholder="Mật khẩu" required>
      <button type="submit">Đăng nhập</button>
    </form>
  </div>
</body>
</html>
