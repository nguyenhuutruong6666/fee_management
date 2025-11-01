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
$user_id = $user['userId'];
$message = "";

// ✅ Xử lý form thanh toán
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $obligation_id = intval($_POST['obligation_id']);
  $method = $_POST['payment_method'];
  $amount = floatval($_POST['amount']);
  $collector_id = isset($_POST['collector_id']) ? intval($_POST['collector_id']) : null;
  $transaction_code = "TXN-" . uniqid();

  // Lưu giao dịch vào fee_payment
  $stmt = $conn->prepare("
    INSERT INTO fee_payment (obligation_id, payer_id, collector_id, payment_method, amount, transaction_code, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())
  ");
  $stmt->bind_param("iiisds", $obligation_id, $user_id, $collector_id, $method, $amount, $transaction_code);

  if ($stmt->execute()) {
    $message = "<p class='success'>✅ Đã ghi nhận giao dịch (Mã: $transaction_code). Đang chờ xác nhận...</p>";

    // Nếu là nộp tiền mặt và người thu là BCH, cập nhật ngay
    if ($method === 'Cash' && $user['isAdmin'] == 1) {
      $conn->query("UPDATE fee_payment SET status='Success' WHERE transaction_code='$transaction_code'");
      $conn->query("UPDATE fee_obligation SET status='Đã nộp' WHERE id=$obligation_id");

      // Sinh biên lai điện tử
      $conn->query("
        INSERT INTO fee_receipt (payment_id, receipt_code, issued_by, amount)
        SELECT id, CONCAT('RC-', id, '-', YEAR(NOW())), $user_id, amount FROM fee_payment WHERE transaction_code='$transaction_code'
      ");

      // Ghi vào sổ quỹ
      $conn->query("
        INSERT INTO fee_cashbook (payment_id, transaction_type, amount, recorded_by, description)
        SELECT id, 'Thu', amount, $user_id, 'Nộp đoàn phí tiền mặt' FROM fee_payment WHERE transaction_code='$transaction_code'
      ");
      $message = "<p class='success'>💰 Thanh toán tiền mặt thành công! Nghĩa vụ đã được cập nhật.</p>";
    }
  } else {
    $message = "<p class='error'>❌ Lỗi khi ghi nhận giao dịch. Vui lòng thử lại.</p>";
  }
}

// ✅ Lấy nghĩa vụ chưa nộp của người dùng
$obligations = $conn->query("
  SELECT o.id, o.period_label, o.amount, o.status, p.policy_name
  FROM fee_obligation o
  JOIN fee_policy p ON o.policy_id = p.id
  WHERE o.user_id = $user_id AND o.status = 'Chưa nộp'
");
?>

<div class="container">
  <h2>💳 Nộp đoàn phí</h2>
  <?= $message ?>

  <?php if ($obligations->num_rows > 0): ?>
    <form method="POST" class="form-pay">
      <div class="form-group">
        <label>Chọn kỳ cần nộp:</label>
        <select name="obligation_id" required>
          <option value="">-- Chọn kỳ --</option>
          <?php while ($o = $obligations->fetch_assoc()): ?>
            <option value="<?= $o['id'] ?>">
              <?= htmlspecialchars($o['policy_name']) ?> - <?= $o['period_label'] ?> (<?= number_format($o['amount'],0) ?>đ)
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Hình thức thanh toán:</label>
        <select name="payment_method" required>
          <option value="">-- Chọn phương thức --</option>
          <option value="Cash">💵 Tiền mặt (BCH thu hộ)</option>
          <option value="VietQR">🏦 Chuyển khoản VietQR</option>
          <option value="VNPay">🌐 VNPay</option>
          <option value="MoMo">📱 MoMo</option>
        </select>
      </div>

      <div class="form-group">
        <label>Số tiền cần nộp:</label>
        <input type="number" name="amount" min="0" step="100" placeholder="VD: 3000" required>
      </div>

      <?php if ($user['isAdmin'] == 1): ?>
      <div class="form-group">
        <label>Người thu hộ (Collector ID):</label>
        <input type="number" name="collector_id" placeholder="Nhập ID BCH nếu có">
      </div>
      <?php endif; ?>

      <div class="form-actions">
        <button type="submit" class="btn-submit">💾 Ghi nhận thanh toán</button>
      </div>
    </form>
  <?php else: ?>
    <p>✅ Bạn đã hoàn thành tất cả nghĩa vụ đoàn phí hoặc chưa có kỳ nào cần nộp.</p>
  <?php endif; ?>
</div>

<style>
.container {
  padding: 25px;
  margin-left: 240px;
  max-width: calc(100% - 260px);
}
h2 { text-align: center; margin-bottom: 20px; color: #2d3436; }
.form-pay {
  background: #fff;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.form-group { margin-bottom: 18px; }
label { font-weight: 600; display: block; margin-bottom: 6px; color: #333; }
input, select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 6px;
}
.btn-submit {
  background: linear-gradient(135deg, #0984e3, #74b9ff);
  color: white;
  border: none;
  padding: 10px 22px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}
.btn-submit:hover { background: linear-gradient(135deg, #0873d6, #6aa8ff); }
.success { color: #27ae60; font-weight: bold; }
.error { color: #d63031; font-weight: bold; }
</style>

<?php include("../includes/footer.php"); ?>
