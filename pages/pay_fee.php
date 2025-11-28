<?php 
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['user'];
$user_id = $user['userId'];
$user_role = $user['role_name'] ?? 'Đoàn viên';
$message = "";

// =========================
// XỬ LÝ NỘP TIỀN
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['obligation_id'])) {

  $obligation_id = intval($_POST['obligation_id']);
  $method = $_POST['method'] ?? '';
  $amount = floatval($_POST['amount'] ?? 0);
  $reference = $_POST['reference'] ?? '';
  $collector_id = ($user['isAdmin'] ?? 0) ? $user_id : NULL;

  // Kiểm tra giao dịch đã tồn tại chưa
  $check = $conn->prepare("
    SELECT transaction_code FROM fee_payment 
    WHERE obligation_id=? AND payer_id=? AND payment_method=? LIMIT 1
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

  // =========================
  // NỘP TIỀN MẶT
  // =========================
  if ($method === 'Cash') {

    if (in_array($user_role, ['BCH Trường', 'BCH Khoa', 'BCH Chi đoàn']) || ($user['isAdmin'] ?? 0) == 1) {
      
      $conn->query("UPDATE fee_payment SET status='Success' WHERE transaction_code='$transaction_code'");
      $conn->query("UPDATE fee_obligation SET status='Đã nộp' WHERE id=$obligation_id");

      // Tạo biên lai
      $conn->query("
        INSERT INTO fee_receipt (payment_id, receipt_code, issued_by, amount)
        SELECT id, CONCAT('RC-', id, '-', YEAR(NOW())), $user_id, amount 
        FROM fee_payment WHERE transaction_code='$transaction_code'
      ");

      // Ghi sổ quỹ
      $conn->query("
        INSERT INTO fee_cashbook (payment_id, transaction_type, amount, recorded_by, description)
        SELECT id, 'Thu', amount, $user_id, 'BCH xác nhận thu tiền mặt' 
        FROM fee_payment WHERE transaction_code='$transaction_code'
      ");

      $message = "<p class='success'>Nộp tiền mặt thành công! (BCH xác nhận tự động)</p>";

    } else {
      $conn->query("UPDATE fee_payment SET status='Pending' WHERE transaction_code='$transaction_code'");
      $message = "<p class='success'>Đã ghi nhận nộp tiền mặt. Đang chờ BCH Chi đoàn xác nhận.</p>";
    }
  }

  // THANH TOÁN VNPay
  if ($method === 'VNPAY') {

    // ---- CẤU HÌNH VNPAY ----
    $vnp_TmnCode    = "NXWJB51W";
    $vnp_HashSecret = "GLOD1KF7WG0VYZPDQUFZ5SL3S0FL9OA1";
    $vnp_Url        = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";

    // ✔ URL user sẽ được redirect sau khi thanh toán
    $vnp_ReturnUrl  = "https://unsquinting-glennis-unallegorized.ngrok-free.dev/fee_management/pages/payment_return.php";

    // ✔ URL VNPay gọi server-to-server để xác nhận thanh toán
    $vnp_IpnUrl     = "https://unsquinting-glennis-unallegorized.ngrok-free.dev/fee_management/pages/vnpay_ipn.php";

    // ======= GIÁ TRỊ GỬI SANG VNPAY =======
    $vnp_Amount     = $amount * 100;
    $vnp_TxnRef     = $transaction_code;
    $vnp_OrderInfo  = "Thanh toán nghĩa vụ đoàn phí - Mã GD: $transaction_code";

    $vnp_Data = [
        "vnp_Version"  => "2.1.0",
        "vnp_Command"  => "pay",
        "vnp_TmnCode"  => $vnp_TmnCode,

        "vnp_Amount"   => $vnp_Amount,
        "vnp_CurrCode" => "VND",
        "vnp_TxnRef"   => $vnp_TxnRef,
        "vnp_OrderInfo"=> $vnp_OrderInfo,
        "vnp_OrderType"=> "other",

        "vnp_Locale"   => "vn",
        "vnp_IpAddr"   => $_SERVER['REMOTE_ADDR'],
        "vnp_CreateDate" => date('YmdHis'),

        // ✔ 2 URL bắt buộc
        "vnp_ReturnUrl" => $vnp_ReturnUrl,
        "vnp_IpnUrl"    => $vnp_IpnUrl
    ];

    // Sắp xếp & tạo hash
    ksort($vnp_Data);
    $query = "";
    $hashdata = "";

    foreach ($vnp_Data as $key => $value) {
        $query    .= urlencode($key)."=".urlencode($value)."&";
        $hashdata .= $key."=".$value."&";
    }

    $query    = rtrim($query, "&");
    $hashdata = rtrim($hashdata, "&");

    $vnp_SecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
    $paymentUrl = $vnp_Url . "?" . $query . "&vnp_SecureHash=" . $vnp_SecureHash;

    header("Location: $paymentUrl");
    exit();
  }
}

// =========================
// LẤY DANH SÁCH NGHĨA VỤ
// =========================
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
  <h2>Nghĩa vụ đoàn phí của bạn</h2>
  <?= $message ?>

  <?php if (in_array($user_role, ['BCH Chi đoàn']) || ($user['isAdmin'] ?? 0) == 1): ?>
    <div style="text-align:right; margin-bottom:15px;">
      <a href="confirm_cash_payment.php" class="btn-manage">Trang xác nhận tiền mặt (BCH)</a>
      <a href="remind_debtors.php" class="btn-remind">Nhắc nợ đoàn viên</a>
    </div>
  <?php endif; ?>

  <?php if ($obligations->num_rows > 0): ?>
    <div class="obligation-list">

      <?php while ($o = $obligations->fetch_assoc()): 
        $isOverdue = strtotime($o['due_date']) < strtotime(date('Y-m-d'));
      ?>
        <div class="obligation-card">

          <h3><?= htmlspecialchars($o['policy_name']) ?></h3>
          <p>Chu kỳ: <strong><?= $o['period_label'] ?></strong></p>

          <p>
            Hạn nộp: <?= date("d/m/Y", strtotime($o['due_date'])) ?>
            <?php if ($isOverdue): ?>
              <span class="overdue">⚠️ Bạn đã quá hạn nộp</span>
            <?php endif; ?>
          </p>

          <p>Số tiền: <strong><?= number_format($o['amount'], 0, ',', '.') ?>đ</strong></p>

          <p>Mã tham chiếu: <strong><?= htmlspecialchars($o['reference_code']) ?></strong></p>

          <form method="POST" class="payment-form">
            <input type="hidden" name="obligation_id" value="<?= $o['id'] ?>">
            <input type="hidden" name="amount" value="<?= $o['amount'] ?>">
            <input type="hidden" name="reference" value="<?= htmlspecialchars($o['reference_code']) ?>">

            <div class="payment-buttons">
              <button type="submit" name="method" value="Cash" class="btn-cash">Nộp tiền mặt</button>
              <button type="submit" name="method" value="VNPAY" class="btn-qr">Thanh toán VNPay</button>
            </div>
          </form>

        </div>
      <?php endwhile; ?>

    </div>
  <?php else: ?>
    <p>Bạn không còn nghĩa vụ nào cần nộp.</p>
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
.btn-cash, .btn-qr { width:48%; border:none; color:white; padding:10px; border-radius:6px; cursor:pointer; font-weight:600; }
.btn-cash { background:linear-gradient(135deg,#00b894,#00cec9); }
.btn-qr { background:linear-gradient(135deg,#6c5ce7,#a29bfe); }
.overdue { color:#d63031; font-weight:bold; margin-left:5px; }
.btn-remind { background:#e67e22; color:white; padding:8px 14px; text-decoration:none; border-radius:6px; font-weight:600; margin-left:10px; }
.btn-remind:hover { background:#d35400; }
</style>

<?php include("../includes/footer.php"); ?>
