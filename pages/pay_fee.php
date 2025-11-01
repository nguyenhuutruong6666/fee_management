<?php
include("../includes/header.php");
include("../includes/navbar.php");

if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['user'];
$message = "";

// Giả lập danh sách kỳ nộp phí (sau này lấy từ bảng fee_periods)
$periods = [
  ['id' => 1, 'name' => 'Học kỳ I - Năm 2025', 'amount' => 50000],
  ['id' => 2, 'name' => 'Học kỳ II - Năm 2025', 'amount' => 50000]
];

// Nếu người dùng gửi form nộp phí
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $method = $_POST['method'];
  $periodId = intval($_POST['period']);
  $amount = intval($_POST['amount']);

  // Giả lập xử lý thanh toán
  if ($method === "cash") {
    $status = "Pending";
    $message = "<p class='success'>💵 Đã ghi nhận giao dịch tiền mặt (chờ xác nhận của BCH Chi đoàn).</p>";
  } elseif ($method === "vietqr") {
    $status = "Pending";
    $message = "<p class='success'>🏦 Vui lòng chuyển khoản qua VietQR với mã tham chiếu: <b>DV{$user['userId']}K{$periodId}</b></p>";
  } elseif ($method === "vnpay" || $method === "momo") {
    $status = "Redirect";
    $message = "<p class='success'>💳 Hệ thống đang chuyển hướng đến cổng thanh toán <b>".strtoupper($method)."</b>...</p>";
    // Sau này thêm redirect sang cổng thanh toán thật
  }

  // Ghi log (mô phỏng, sau này ghi DB)
  file_put_contents("../logs/payment_log.txt",
    "[".date("Y-m-d H:i:s")."] {$user['fullName']} - {$method} - {$periodId} - {$amount} - {$status}\n",
    FILE_APPEND
  );
}
?>

<div class="container">
  <h2>💰 Nộp đoàn phí</h2>

  <?php if ($message): ?>
    <div class="alert"><?= $message ?></div>
  <?php endif; ?>

  <form method="POST" class="form-pay">
    <div class="form-group">
      <label>Kỳ nộp đoàn phí:</label>
      <select name="period" required>
        <option value="">-- Chọn kỳ học --</option>
        <?php foreach ($periods as $p): ?>
          <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= number_format($p['amount']) ?>đ)</option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Số tiền nộp:</label>
      <input type="number" name="amount" value="50000" min="1000" required>
    </div>

    <div class="form-group">
      <label>Hình thức thanh toán:</label>
      <div class="methods">
        <label><input type="radio" name="method" value="cash" required> 💵 Tiền mặt</label>
        <label><input type="radio" name="method" value="vietqr"> 🏦 Chuyển khoản VietQR</label>
        <label><input type="radio" name="method" value="vnpay"> 💳 VNPay</label>
        <label><input type="radio" name="method" value="momo"> 📱 MoMo</label>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-submit">🧾 Nộp phí</button>
      <a href="dashboard.php" class="btn-back">⬅️ Quay lại</a>
    </div>
  </form>
</div>

<style>
body {
  font-family: "Segoe UI", sans-serif;
  background: #f7f9fc;
}
.container {
  margin-left: 240px;
  max-width: calc(100% - 260px);
  padding: 40px 30px;
}
h2 {
  color: #2d3436;
  margin-bottom: 20px;
}
.form-group {
  margin-bottom: 20px;
}
label {
  font-weight: 600;
}
select, input[type="number"] {
  width: 100%;
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #ccc;
  margin-top: 5px;
}
.methods {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-top: 10px;
}
.methods label {
  background: #ecf0f1;
  border-radius: 8px;
  padding: 10px 15px;
  cursor: pointer;
  transition: all 0.3s;
}
.methods label:hover {
  background: #dfe6e9;
}
.form-actions {
  margin-top: 25px;
  display: flex;
  gap: 10px;
}
.btn-submit {
  background: linear-gradient(135deg, #00b894, #00cec9);
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: 0.3s;
}
.btn-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(0, 206, 201, 0.3);
}
.btn-back {
  background: #b2bec3;
  color: white;
  text-decoration: none;
  padding: 12px 20px;
  border-radius: 8px;
}
.alert {
  background: #dff9fb;
  border-left: 5px solid #00cec9;
  padding: 12px 15px;
  margin-bottom: 15px;
  border-radius: 8px;
  color: #2d3436;
  font-weight: 500;
}
</style>

<?php include("../includes/footer.php"); ?>
