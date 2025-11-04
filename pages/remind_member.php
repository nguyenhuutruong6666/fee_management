<?php
session_start();
include("../config/db.php");
require '../vendor/autoload.php'; // dùng nếu cài qua Composer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Kiểm tra quyền
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['user'];
$role_name = $user['role_name'] ?? '';
$allowed_roles = ['BCH Trường', 'BCH Khoa', 'BCH Chi đoàn'];
if (!in_array($role_name, $allowed_roles) && !$user['isAdmin']) {
  echo "<div style='color:red; text-align:center;'>🚫 Bạn không có quyền gửi nhắc nợ.</div>";
  exit();
}

// Kiểm tra dữ liệu POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $payer_name = $_POST['payer_name'] ?? '';
  $payer_unit = $_POST['payer_unit'] ?? '';
  $amount = floatval($_POST['amount'] ?? 0);
  $period_label = $_POST['period_label'] ?? '';

  // Lấy email đoàn viên
  $stmt = $conn->prepare("SELECT email FROM users WHERE fullName=? AND unit=? LIMIT 1");
  $stmt->bind_param("ss", $payer_name, $payer_unit);
  $stmt->execute();
  $result = $stmt->get_result();
  $userEmail = ($result->num_rows > 0) ? $result->fetch_assoc()['email'] : null;

  if (!$userEmail) {
    echo "<div style='color:red; text-align:center;'>⚠️ Không tìm thấy email của đoàn viên <b>$payer_name</b>.</div>";
    exit();
  }

  // --- Cấu hình PHPMailer ---
  $mail = new PHPMailer(true);
  try {
    // SMTP Settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'YOUR_GMAIL@gmail.com';  // 📧 Thay bằng Gmail của bạn
    $mail->Password = 'YOUR_APP_PASSWORD';     // 🔑 Thay bằng App Password (16 ký tự)
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    // Sender & Recipient
    $mail->setFrom('YOUR_GMAIL@gmail.com', 'Ban Chấp Hành Đoàn');
    $mail->addAddress($userEmail, $payer_name);

    // Nội dung email
    $mail->isHTML(true);
    $mail->Subject = "Nhắc nộp đoàn phí kỳ $period_label";
    $mail->Body = "
      <p>Chào <strong>$payer_name</strong>,</p>
      <p>Hệ thống ghi nhận rằng bạn vẫn chưa hoàn thành nghĩa vụ đoàn phí cho kỳ <b>$period_label</b>.</p>
      <p><b>Số tiền cần nộp:</b> " . number_format($amount, 0, ',', '.') . "đ</p>
      <p>Vui lòng hoàn thành sớm để đảm bảo nghĩa vụ đoàn viên.</p>
      <p>Trân trọng,<br>Ban Chấp Hành Đoàn</p>
    ";

    // Gửi email
    $mail->send();

    echo "
      <div style='text-align:center; color:#27ae60;'>
        ✅ Đã gửi nhắc nợ thành công tới <b>$payer_name</b> ($userEmail)
        <br><a href='manage_transactions.php' style='color:#0984e3;'>⬅️ Quay lại</a>
      </div>
    ";
  } catch (Exception $e) {
    echo "
      <div style='text-align:center; color:red;'>
        ❌ Gửi email thất bại: {$mail->ErrorInfo}
        <br><a href='manage_transactions.php' style='color:#0984e3;'>⬅️ Quay lại</a>
      </div>
    ";
  }
} else {
  echo "<div style='text-align:center;'>⚠️ Không có dữ liệu được gửi.</div>";
}
?>
