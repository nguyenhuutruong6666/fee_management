<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

//Kiểm tra quyền truy cập
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['user'];
$user_id = $user['userId'];
$user_role = $user['role_name'] ?? '';
$user_unit = $user['unit'] ?? null;
$message = "";

//Chỉ BCH Chi đoàn được vào
if (!in_array($user_role, ['BCH Chi đoàn']) && ($user['isAdmin'] ?? 0) != 1) {
  echo "<div class='container'><p style='color:red;'>🚫 Bạn không có quyền truy cập trang này.</p></div>";
  include("../includes/footer.php");
  exit();
}

//XÁC NHẬN ĐOÀN VIÊN ĐÃ NỘP TIỀN MẶT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['payment_id'])) {
  $payment_id = intval($_POST['payment_id']);

  // Lấy thông tin thanh toán
  $res = $conn->query("
    SELECT p.id, p.amount, p.obligation_id, p.transaction_code
    FROM fee_payment p
    WHERE p.id = $payment_id AND p.status = 'Pending'
  ");
  $payment = $res->fetch_assoc();

  if ($payment) {
    $amount = $payment['amount'];
    $obligation_id = $payment['obligation_id'];
    $txn = $payment['transaction_code'];

    // Cập nhật trạng thái
    $conn->query("UPDATE fee_payment SET status='Success' WHERE id=$payment_id");
    $conn->query("UPDATE fee_obligation SET status='Đã nộp' WHERE id=$obligation_id");

    // Sinh biên lai điện tử
    $conn->query("
      INSERT INTO fee_receipt (payment_id, receipt_code, issued_by, amount)
      SELECT id, CONCAT('RC-', id, '-', YEAR(NOW())), $user_id, amount 
      FROM fee_payment WHERE id=$payment_id
    ");

    // Ghi vào sổ quỹ
    $conn->query("
      INSERT INTO fee_cashbook (payment_id, transaction_type, amount, recorded_by, description)
      SELECT id, 'Thu', amount, $user_id, 'BCH xác nhận đoàn viên nộp tiền mặt'
      FROM fee_payment WHERE id=$payment_id
    ");

    $message = "<p class='success'>✅ Đã xác nhận đoàn viên nộp tiền mặt thành công (Mã giao dịch: <strong>$txn</strong>).</p>";
  } else {
    $message = "<p class='error'>⚠️ Giao dịch không hợp lệ hoặc đã được xác nhận trước đó!</p>";
  }
}

//LẤY DANH SÁCH ĐOÀN VIÊN CẦN XÁC NHẬN
$sql = "
  SELECT 
    p.id AS payment_id,
    u.fullName,
    u.userName,
    u.identifyCard,
    u.unit,
    f.period_label,
    f.amount,
    f.reference_code,
    f.policy_id,
    p.transaction_code,
    p.created_at,
    pol.policy_name
  FROM fee_payment p
  JOIN fee_obligation f ON p.obligation_id = f.id
  JOIN users u ON f.user_id = u.userId
  JOIN fee_policy pol ON f.policy_id = pol.id
  WHERE p.payment_method='Cash' AND p.status='Pending'
";

//Nếu BCH thì chỉ xem đoàn viên cùng “unit”
if ($user_unit && ($user['isAdmin'] ?? 0) != 1) {
  $sql .= " AND u.unit = '" . $conn->real_escape_string($user_unit) . "'";
}

$sql .= " ORDER BY p.created_at DESC";
$list = $conn->query($sql);
?>

<div class="container">
  <h2>🧾 Xác nhận đoàn viên đã nộp tiền mặt</h2>
  <?= $message ?>

  <?php if ($list && $list->num_rows > 0): ?>
  <table class="table">
    <thead>
      <tr>
        <th>#</th>
        <th>Họ và tên</th>
        <th>Mã CCCD</th>
        <th>Đơn vị</th>
        <th>Chu kỳ</th>
        <th>Chính sách</th>
        <th>Số tiền</th>
        <th>Mã tham chiếu</th>
        <th>Mã giao dịch</th>
        <th>Ngày ghi nhận</th>
        <th>Xác nhận</th>
      </tr>
    </thead>
    <tbody>
      <?php $i=1; while ($r = $list->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($r['fullName']) ?></td>
        <td><?= htmlspecialchars($r['identifyCard']) ?></td>
        <td><?= htmlspecialchars($r['unit'] ?? '-') ?></td>
        <td><?= htmlspecialchars($r['period_label']) ?></td>
        <td><?= htmlspecialchars($r['policy_name']) ?></td>
        <td><strong><?= number_format($r['amount'], 0, ',', '.') ?>đ</strong></td>
        <td><?= htmlspecialchars($r['reference_code']) ?></td>
        <td><?= htmlspecialchars($r['transaction_code']) ?></td>
        <td><?= date("d/m/Y H:i", strtotime($r['created_at'])) ?></td>
        <td>
          <form method="POST" onsubmit="return confirm('Bạn có chắc muốn xác nhận đoàn viên này đã nộp tiền mặt?');">
            <input type="hidden" name="payment_id" value="<?= $r['payment_id'] ?>">
            <button type="submit" class="btn-confirm">✅ Xác nhận</button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p>✅ Hiện không có đoàn viên nào đang chờ xác nhận.</p>
  <?php endif; ?>
</div>

<style>
.container { padding: 25px; margin-left: 240px; max-width: calc(100% - 260px); }
h2 { text-align: center; color: #2d3436; margin-bottom: 20px; }
.table { width: 100%; border-collapse: collapse; margin-top: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
th { background: #0984e3; color: white; }
tr:nth-child(even) { background: #f9f9f9; }
.btn-confirm {
  background: linear-gradient(135deg, #27ae60, #2ecc71);
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}
.btn-confirm:hover { background: linear-gradient(135deg, #1f944e, #27ae60); }
.success { color: #27ae60; font-weight: bold; text-align:center; }
.error { color: #d63031; font-weight: bold; text-align:center; }
</style>

<?php include("../includes/footer.php"); ?>
