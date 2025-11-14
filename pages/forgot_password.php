<?php
session_start();
include("../config/db.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Nếu không dùng Composer
require_once("../includes/PHPMailer/src/Exception.php");
require_once("../includes/PHPMailer/src/PHPMailer.php");
require_once("../includes/PHPMailer/src/SMTP.php");

$message = "";
$step = 1; // 1: nhập email | 2: nhập mã xác minh | 3: nhập mật khẩu mới

//Hàm gửi email xác minh
function sendVerificationCode($toEmail, $code) {
    $mail = new PHPMailer(true);
    try {
        //Cấu hình Gmail SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'nguyenhuutruongchatgpt@gmail.com'; // Gmail của bạn
        $mail->Password = 'jbee qhxa hitr nqyv';      // App Password 16 ký tự
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        //Thông tin người gửi / người nhận
        $mail->setFrom('nguyenhuutruongchatgpt@gmail.com', 'He thong Quan ly Doan phi');
        $mail->addAddress($toEmail);

        //Nội dung email
        $mail->isHTML(true);
        $mail->Subject = 'Mã xác minh đặt lại mật khẩu';
        $mail->Body = "
            <div style='font-family:Segoe UI, sans-serif;'>
                <h2>Xin chào!</h2>
                <p>Bạn vừa yêu cầu đặt lại mật khẩu cho tài khoản của mình.</p>
                <p>Mã xác minh của bạn là:</p>
                <h1 style='color:#0984e3;letter-spacing:4px;'>$code</h1>
                <p><b>Lưu ý:</b> Mã chỉ có hiệu lực trong 5 phút.</p>
                <p>Trân trọng,<br>Hệ thống Quản lý Đoàn phí</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        $result = $conn->query("SELECT * FROM users WHERE email='$email' LIMIT 1");

        if ($result && $result->num_rows > 0) {
            $code = rand(1000, 9999);
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_code'] = $code;
            $_SESSION['code_expire'] = time() + 300; // 5 phút

            if (sendVerificationCode($email, $code)) {
                $message = "<p class='success'>Mã xác minh đã được gửi đến email của bạn!</p>";
                $step = 2;
            } else {
                $message = "<p class='error'>Không thể gửi email. Vui lòng thử lại sau.</p>";
            }
        } else {
            $message = "<p class='error'>Email không tồn tại trong hệ thống.</p>";
        }
    }
    elseif (isset($_POST['verify_code'])) {
        $code = trim($_POST['verify_code']);
        if (isset($_SESSION['reset_code']) && time() < $_SESSION['code_expire']) {
            if ($code == $_SESSION['reset_code']) {
                $message = "<p class='success'>Mã chính xác! Vui lòng nhập mật khẩu mới.</p>";
                $step = 3;
            } else {
                $message = "<p class='error'>Mã xác minh không chính xác!</p>";
                $step = 2;
            }
        } else {
            $message = "<p class='error'>Mã đã hết hạn. Vui lòng gửi lại yêu cầu.</p>";
            $step = 1;
        }
    }
    elseif (isset($_POST['new_password'])) {
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        $email = $_SESSION['reset_email'] ?? '';

        if ($new !== $confirm) {
            $message = "<p class='error'>Mật khẩu xác nhận không khớp!</p>";
            $step = 3;
        } elseif (strlen($new) < 6) {
            $message = "<p class='error'>Mật khẩu phải có ít nhất 6 ký tự.</p>";
            $step = 3;
        } else {
            $conn->query("UPDATE users SET password='$new' WHERE email='$email'");
            unset($_SESSION['reset_email'], $_SESSION['reset_code']);
            $message = "<p class='success'>Đặt lại mật khẩu thành công! <a href='login.php'>Đăng nhập</a></p>";
            $step = 4;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Quên mật khẩu</title>
<style>
body {
  font-family: "Segoe UI", sans-serif;
  background: linear-gradient(135deg, #74b9ff, #a29bfe);
}
.box {
  width: 400px;
  background: #fff;
  margin: 100px auto;
  padding: 25px;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  text-align: center;
}
input {
  width: 90%;
  padding: 10px;
  margin: 8px 0;
  border-radius: 6px;
  border: 1px solid #ccc;
}
button {
  width: 95%;
  background: #0984e3;
  color: white;
  border: none;
  padding: 10px;
  border-radius: 6px;
  cursor: pointer;
}
button:hover { background: #74b9ff; }
.success { color: #27ae60; }
.error { color: #c0392b; }
a { color: #0984e3; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="box">
  <h2>🔐 Quên mật khẩu</h2>
  <?= $message ?>

  <?php if ($step === 1): ?>
    <form method="POST">
      <input type="email" name="email" placeholder="Nhập email đăng ký" required>
      <button type="submit">Gửi mã xác minh</button>
    </form>

  <?php elseif ($step === 2): ?>
    <form method="POST">
      <input type="text" name="verify_code" maxlength="4" placeholder="Nhập mã 4 số" required>
      <button type="submit">Xác minh</button>
    </form>

  <?php elseif ($step === 3): ?>
    <form method="POST">
      <input type="password" name="new_password" placeholder="Mật khẩu mới" required>
      <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
      <button type="submit">Đặt lại mật khẩu</button>
    </form>
  <?php endif; ?>

  <p style="margin-top:15px;"><a href="login.php">⬅ Quay lại đăng nhập</a></p>
</div>
</body>
</html>
