<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

//Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['user'];
$user_id = $user['userId'];
$user_role = $user['role_name'] ?? 'Đoàn viên'; //Lấy trực tiếp từ session
$message = "";


//XỬ LÝ NỘP TIỀN
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['obligation_id'])) {
  $obligation_id = intval($_POST['obligation_id']);
  $method = $_POST['method'] ?? '';
  $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
  $reference = $_POST['reference'] ?? '';
  $collector_id = ($user['isAdmin'] ?? 0) ? $user_id : NULL;

  //Kiểm tra xem đã có mã giao dịch chưa (tránh sinh trùng)
  $check = $conn->prepare("
    SELECT transaction_code, status 
    FROM fee_payment 
    WHERE obligation_id=? AND payer_id=? AND payment_method=? 
    LIMIT 1
  ");
  $check->bind_param("iis", $obligation_id, $user_id, $method);
  $check->execute();
  $existing = $check->get_result()->fetch_assoc();

  if ($existing) {
    $transaction_code = $existing['transaction_code'];
  } else {
    $transaction_code = "TXN-" . strtoupper(uniqid());
    $stmt = $conn->prepare("
      INSERT INTO fee_payment 
      (obligation_id, payer_id, collector_id, payment_method, amount, transaction_code, status, created_at)
      VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())
    ");
    $stmt->bind_param("iiisds", $obligation_id, $user_id, $collector_id, $method, $amount, $transaction_code);
    $stmt->execute();
  }

  //NỘP TIỀN MẶT
  if ($method === 'Cash') {
    // BCH hoặc Admin -> xác nhận ngay
    if (in_array($user_role, ['BCH Trường', 'BCH Khoa', 'BCH Chi đoàn']) || ($user['isAdmin'] ?? 0) == 1) {
      $conn->query("UPDATE fee_payment SET status='Success' WHERE transaction_code='$transaction_code'");
      $conn->query("UPDATE fee_obligation SET status='Đã nộp' WHERE id=$obligation_id");

      // Sinh biên lai điện tử và ghi vào sổ quỹ
      $conn->query("
        INSERT INTO fee_receipt (payment_id, receipt_code, issued_by, amount)
        SELECT id, CONCAT('RC-', id, '-', YEAR(NOW())), $user_id, amount 
        FROM fee_payment WHERE transaction_code='$transaction_code'
      ");
      $conn->query("
        INSERT INTO fee_cashbook (payment_id, transaction_type, amount, recorded_by, description)
        SELECT id, 'Thu', amount, $user_id, 'BCH xác nhận thu tiền mặt' 
        FROM fee_payment WHERE transaction_code='$transaction_code'
      ");
      $message = "<p class='success'>✅ Nộp tiền mặt thành công! (BCH xác nhận tự động)</p>";
    } else {
      // Đoàn viên thường -> chờ BCH xác nhận
      $conn->query("UPDATE fee_payment SET status='Pending' WHERE transaction_code='$transaction_code'");
      $message = "<p class='success'>🕓 Đã ghi nhận nộp tiền mặt. Đang chờ BCH Chi đoàn xác nhận.</p>";
    }
  }

  //NỘP QUA VIETQR
  if ($method === 'VietQR') {
    $_SESSION['qr_transaction'] = [
      'obligation_id' => $obligation_id,
      'amount' => $amount,
      'reference' => $reference,
      'transaction_code' => $transaction_code
    ];
    header("Location: pay_fee.php?show_qr=1");
    exit();
  }
}

//XÁC NHẬN CHUYỂN KHOẢN
if (isset($_POST['confirm_transfer'])) {
  $obligation_id = intval($_POST['obligation_id']);
  $transaction_code = $_POST['transaction_code'];

  $conn->query("UPDATE fee_payment SET status='Success' WHERE transaction_code='$transaction_code'");
  $conn->query("UPDATE fee_obligation SET status='Đã nộp' WHERE id=$obligation_id");

  // Sinh biên lai và ghi vào sổ quỹ
  $conn->query("
    INSERT INTO fee_receipt (payment_id, receipt_code, issued_by, amount)
    SELECT id, CONCAT('RC-', id, '-', YEAR(NOW())), $user_id, amount 
    FROM fee_payment WHERE transaction_code='$transaction_code'
  ");
  $conn->query("
    INSERT INTO fee_cashbook (payment_id, transaction_type, amount, recorded_by, description)
    SELECT id, 'Thu', amount, $user_id, 'Nộp đoàn phí qua VietQR' 
    FROM fee_payment WHERE transaction_code='$transaction_code'
  ");
  $message = "<p class='success'>✅ Xác nhận chuyển khoản thành công!</p>";
}


//LẤY DANH SÁCH NGHĨA VỤ CHƯA NỘP
$sql = "
  SELECT o.id, o.period_label, o.amount, o.status, o.due_date, o.reference_code, p.policy_name
  FROM fee_obligation o
  JOIN fee_policy p ON o.policy_id = p.id
  WHERE o.user_id = $user_id AND o.status = 'Chưa nộp'
  ORDER BY o.due_date ASC
";
$obligations = $conn->query($sql);
?>

<div class="container">
  <h2>💳 Nghĩa vụ đoàn phí của bạn</h2>
  <?= $message ?>

  <!-- Nút cho BCH / Admin -->
  <?php if (in_array($user_role, ['BCH Chi đoàn']) || ($user['isAdmin'] ?? 0) == 1): ?>
    <div style="text-align:right; margin-bottom:15px;">
      <a href="confirm_cash_payment.php" class="btn-manage">🔑 Trang xác nhận tiền mặt (BCH)</a>
    </div>
  <?php endif; ?>

  <?php 
  //HIỂN THỊ QR
  if (isset($_GET['show_qr']) && isset($_SESSION['qr_transaction'])): 
    $txn = $_SESSION['qr_transaction'];
    $bank = "970436"; // Vietcombank
    $accountNo = "0385672224";
    $accountName = "Nguyen Huu Truong";
    $amount = $txn['amount'];
    $ref = $txn['reference'];
    $obligation_id = $txn['obligation_id'];
    $qrText = "https://img.vietqr.io/image/$bank-$accountNo-compact2.png?amount=$amount&addInfo=$ref&accountName=$accountName";
  ?>
    <div class="qr-box">
      <h3>🏦 Quét mã VietQR để nộp đoàn phí</h3>
      <p><strong>Số tiền:</strong> <?= number_format($amount, 0, ',', '.') ?>đ</p>
      <img src="<?= htmlspecialchars($qrText) ?>" alt="VietQR" class="qr-image">
      <p><strong>Nội dung chuyển khoản:</strong> <?= htmlspecialchars($ref) ?></p>

      <form method="POST" style="margin-top:15px;">
        <input type="hidden" name="obligation_id" value="<?= $obligation_id ?>">
        <input type="hidden" name="transaction_code" value="<?= $txn['transaction_code'] ?>">
        <button type="submit" name="confirm_transfer" class="btn-confirm">✅ Tôi đã chuyển khoản</button>
      </form>
      <a href="pay_fee.php" class="btn-back">⬅️ Quay lại</a>
    </div>
  <?php unset($_SESSION['qr_transaction']); endif; ?>

  <?php if ($obligations->num_rows > 0): ?>
    <div class="obligation-list">
      <?php while ($o = $obligations->fetch_assoc()): ?>
        <div class="obligation-card">
          <h3><?= htmlspecialchars($o['policy_name']) ?></h3>
          <p>📅 Chu kỳ: <strong><?= $o['period_label'] ?></strong></p>
          <p>🕒 Hạn nộp: <?= date("d/m/Y", strtotime($o['due_date'])) ?></p>
          <p>💵 Số tiền: <strong><?= number_format($o['amount'], 0, ',', '.') ?>đ</strong></p>
          <p>🔖 Mã tham chiếu: <strong><?= htmlspecialchars($o['reference_code']) ?></strong></p>

          <form method="POST" class="payment-form">
            <input type="hidden" name="obligation_id" value="<?= $o['id'] ?>">
            <input type="hidden" name="amount" value="<?= $o['amount'] ?>">
            <input type="hidden" name="reference" value="<?= htmlspecialchars($o['reference_code']) ?>">
            <div class="payment-buttons">
              <button type="submit" name="method" value="Cash" class="btn-cash">💵 Nộp tiền mặt</button>
              <button type="submit" name="method" value="VietQR" class="btn-qr">🏦 Chuyển khoản VietQR</button>
            </div>
          </form>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <p>✅ Bạn không còn nghĩa vụ nào cần nộp.</p>
  <?php endif; ?>
</div>

<style>
.container { padding:25px; margin-left:240px; max-width:calc(100% - 310px); }
h2 { text-align:center; color:#2d3436; margin-bottom:25px; }
.btn-manage { background:#0984e3; color:white; padding:8px 14px; text-decoration:none; border-radius:6px; font-weight:600; }
.obligation-list { display:grid; grid-template-columns:repeat(auto-fill,minmax(330px,1fr)); gap:20px; }
.obligation-card { background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:20px; transition:transform .2s ease; }
.obligation-card:hover { transform:translateY(-4px); }
.payment-buttons { display:flex; justify-content:space-between; margin-top:15px; }
.btn-cash, .btn-qr, .btn-confirm { width:48%; border:none; color:white; padding:10px; border-radius:6px; cursor:pointer; font-weight:600; }
.btn-cash { background:linear-gradient(135deg,#00b894,#00cec9); }
.btn-qr { background:linear-gradient(135deg,#6c5ce7,#a29bfe); }
.btn-confirm { background:linear-gradient(135deg,#27ae60,#2ecc71); width:100%; }
.qr-box { background:#fff; border-radius:12px; box-shadow:0 4px 16px rgba(0,0,0,0.1); padding:25px; text-align:center; margin:30px auto; max-width:450px; }
.qr-image { width:250px; margin:15px 0; }
.btn-back { display:inline-block; padding:8px 15px; background:#b2bec3; color:white; border-radius:8px; text-decoration:none; }
.success { color:#27ae60; font-weight:bold; text-align:center; }
.error { color:#d63031; font-weight:bold; text-align:center; }
</style>

<?php include("../includes/footer.php"); ?>
