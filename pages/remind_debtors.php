<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once("../includes/PHPMailer/src/Exception.php");
require_once("../includes/PHPMailer/src/PHPMailer.php");
require_once("../includes/PHPMailer/src/SMTP.php");

// Chỉ BCH hoặc Admin mới được vào
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
$user_role = $user['role_name'] ?? 'Đoàn viên';
if (!in_array($user_role, ['BCH Trường', 'BCH Khoa', 'BCH Chi đoàn']) && ($user['isAdmin'] ?? 0) != 1) {
    die("<p style='color:red;text-align:center;'>Bạn không có quyền truy cập trang này.</p>");
}

$message = "";

// Hàm gửi mail bằng PHPMailer
function sendMail($toEmail, $toName, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'nguyenhuutruongchatgpt@gmail.com'; // 🔸 thay bằng email thật
        $mail->Password = 'jbee qhxa hitr nqyv';    // 🔸 thay bằng app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('your_email@gmail.com', 'Hệ thống Đoàn phí');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Gửi mail lỗi: " . $mail->ErrorInfo);
        return false;
    }
}

// Gửi email nhắc nợ
if (isset($_POST['send_reminder'])) {
    $email = $_POST['email'];
    $fullname = $_POST['fullname'];
    $mssv = $_POST['mssv'];
    $period = $_POST['period'];
    $amount = $_POST['amount'];
    $ref = $_POST['ref'];
    $due = $_POST['due'];

    $subject = "Nhắc nộp đoàn phí – $period";
    $body = "
      <div style='font-family:Arial,sans-serif;line-height:1.6;color:#333;'>
        <h3>Thông báo nhắc nộp đoàn phí</h3>
        <p>Kính gửi đồng chí <strong>$fullname</strong> (MSSV: $mssv),</p>
        <p>Hiện tại bạn <b>đã quá hạn nộp đoàn phí</b> cho kỳ <b>$period</b>.</p>
        <p>
          <b>Số tiền cần nộp:</b> " . number_format($amount, 0, ',', '.') . "đ<br>
          <b>Mã tham chiếu:</b> $ref<br>
          <b>Hạn nộp:</b> $due
        </p>
        <p>Vui lòng thực hiện nộp đoàn phí sớm nhất để tránh bị nhắc lại.</p>
        <hr>
        <p style='font-size:13px;color:#555;'>Trân trọng,<br><b>Ban Chấp hành Đoàn Trường</b></p>
      </div>
    ";

    if (sendMail($email, $fullname, $subject, $body)) {
        $message = "<p class='success'>Đã gửi nhắc nợ cho $fullname ($email)</p>";
    } else {
        $message = "<p class='error'>Gửi email thất bại cho $fullname ($email)</p>";
    }
}

// Lấy danh sách đoàn viên quá hạn chưa nộp
$sql = "
  SELECT u.fullName, u.email, u.identifyCard, o.period_label, o.amount, o.reference_code, o.due_date, p.policy_name
  FROM fee_obligation o
  JOIN users u ON o.user_id = u.userId
  JOIN fee_policy p ON o.policy_id = p.id
  WHERE o.status = 'Chưa nộp' AND o.due_date < CURDATE()
  ORDER BY o.due_date ASC
";
$result = $conn->query($sql);
?>

<div class="container">
  <h2>Danh sách đoàn viên quá hạn nộp đoàn phí</h2>
  <?= $message ?>

  <table class="table">
    <thead>
      <tr>
        <th>Họ tên</th>
        <th>MSSV</th>
        <th>Email</th>
        <th>Kỳ</th>
        <th>Số tiền</th>
        <th>Hạn nộp</th>
        <th>Mã tham chiếu</th>
        <th>Thao tác</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['fullName']) ?></td>
            <td><?= htmlspecialchars($row['identifyCard']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['period_label']) ?></td>
            <td><?= number_format($row['amount'], 0, ',', '.') ?>đ</td>
            <td><?= date("d/m/Y", strtotime($row['due_date'])) ?></td>
            <td><?= htmlspecialchars($row['reference_code']) ?></td>
            <td>
              <form method="POST">
                <input type="hidden" name="email" value="<?= htmlspecialchars($row['email']) ?>">
                <input type="hidden" name="fullname" value="<?= htmlspecialchars($row['fullName']) ?>">
                <input type="hidden" name="mssv" value="<?= htmlspecialchars($row['identifyCard']) ?>">
                <input type="hidden" name="period" value="<?= htmlspecialchars($row['period_label']) ?>">
                <input type="hidden" name="amount" value="<?= $row['amount'] ?>">
                <input type="hidden" name="ref" value="<?= htmlspecialchars($row['reference_code']) ?>">
                <input type="hidden" name="due" value="<?= date("d/m/Y", strtotime($row['due_date'])) ?>">
                <button type="submit" name="send_reminder" class="btn-send">📩 Nhắc nợ</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="8" style="text-align:center;">Không có đoàn viên nào quá hạn.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<style>
.container { padding:25px; margin-left:240px; max-width:calc(100% - 310px); }
h2 { text-align:center; color:#2d3436; margin-bottom:20px; }
.table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 4px 8px rgba(0,0,0,0.1); }
.table th, .table td { padding:10px; text-align:center; border-bottom:1px solid #ddd; }
.table th { background:#0984e3; color:white; }
.btn-send { background:#00b894; color:white; padding:6px 10px; border:none; border-radius:6px; cursor:pointer; }
.btn-send:hover { background:#019875; }
.success { color:#27ae60; font-weight:bold; text-align:center; }
.error { color:#d63031; font-weight:bold; text-align:center; }
</style>

<?php include("../includes/footer.php"); ?>
