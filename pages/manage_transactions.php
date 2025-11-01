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
$role_name = $user['role_name'] ?? '';
$message = "";

// ✅ Kiểm tra quyền (BCH Trường, BCH Khoa, BCH Chi đoàn)
$allowed_roles = ['BCH Trường', 'BCH Khoa', 'BCH Chi đoàn'];
if (!in_array($role_name, $allowed_roles) && !$user['isAdmin']) {
  echo "<div class='container'><p style='color:red;'>🚫 Bạn không có quyền truy cập trang này.</p></div>";
  include("../includes/footer.php");
  exit();
}

// ✅ Cập nhật trạng thái giao dịch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'])) {
  $payment_id = intval($_POST['payment_id']);
  $new_status = $_POST['status'];
  $note = $_POST['note'] ?? '';

  // Lấy thông tin giao dịch
  $res = $conn->query("SELECT * FROM fee_payment WHERE id=$payment_id");
  $payment = $res->fetch_assoc();

  if (!$payment) {
    $message = "<p class='error'>❌ Không tìm thấy giao dịch.</p>";
  } else {
    // ✅ Cập nhật trạng thái
    $stmt = $conn->prepare("UPDATE fee_payment SET status=?, note=? WHERE id=?");
    $stmt->bind_param("ssi", $new_status, $note, $payment_id);
    $stmt->execute();

    // ✅ Nếu trạng thái = Success → Cập nhật nghĩa vụ + sinh biên lai + ghi sổ quỹ
    if ($new_status === 'Success') {
      $obligation_id = $payment['obligation_id'];
      $amount = $payment['amount'];

      $conn->query("UPDATE fee_obligation SET status='Đã nộp' WHERE id=$obligation_id");

      // Phát hành biên lai (e-Receipt)
      $conn->query("
        INSERT INTO fee_receipt (payment_id, receipt_code, issued_by, amount)
        VALUES ($payment_id, CONCAT('RC-', $payment_id, '-', YEAR(NOW())), {$user['userId']}, $amount)
      ");

      // Ghi nhận sổ quỹ
      $conn->query("
        INSERT INTO fee_cashbook (payment_id, transaction_type, amount, recorded_by, description)
        VALUES ($payment_id, 'Thu', $amount, {$user['userId']}, 'Duyệt giao dịch đoàn phí')
      ");
    }

    $message = "<p class='success'>✅ Đã cập nhật trạng thái giao dịch thành công!</p>";
  }
}

// ✅ Lấy danh sách giao dịch (Pending hoặc Need review)
$sql = "
  SELECT p.id, p.transaction_code, p.payment_method, p.amount, p.status, p.note, p.payment_date,
         u.fullName AS payer_name, o.period_label, o.status AS obligation_status
  FROM fee_payment p
  JOIN users u ON p.payer_id = u.userId
  JOIN fee_obligation o ON p.obligation_id = o.id
  WHERE p.status IN ('Pending', 'Need review')
  ORDER BY p.payment_date DESC
";
$transactions = $conn->query($sql);
?>

<div class="container">
  <h2>🧾 Quản lý giao dịch đoàn phí</h2>
  <?= $message ?>

  <table class="table">
    <thead>
      <tr>
        <th>Mã GD</th>
        <th>Người nộp</th>
        <th>Kỳ phí</th>
        <th>Số tiền</th>
        <th>Hình thức</th>
        <th>Ngày</th>
        <th>Trạng thái</th>
        <th>Ghi chú</th>
        <th>Thao tác</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($transactions->num_rows > 0): ?>
        <?php while ($t = $transactions->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($t['transaction_code']) ?></td>
            <td><?= htmlspecialchars($t['payer_name']) ?></td>
            <td><?= htmlspecialchars($t['period_label']) ?></td>
            <td><?= number_format($t['amount'], 0) ?>đ</td>
            <td><?= htmlspecialchars($t['payment_method']) ?></td>
            <td><?= date("d/m/Y H:i", strtotime($t['payment_date'])) ?></td>
            <td><span class="status <?= strtolower($t['status']) ?>"><?= $t['status'] ?></span></td>
            <td><?= htmlspecialchars($t['note']) ?></td>
            <td>
              <form method="POST" class="inline-form">
                <input type="hidden" name="payment_id" value="<?= $t['id'] ?>">
                <select name="status" class="status-select">
                  <option value="Pending" <?= $t['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="Success">Success</option>
                  <option value="Failed">Failed</option>
                  <option value="Need review">Need review</option>
                  <option value="Canceled">Canceled</option>
                </select>
                <input type="text" name="note" placeholder="Ghi chú..." value="<?= htmlspecialchars($t['note']) ?>">
                <button type="submit" class="btn-update">💾</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="9" style="text-align:center;">Không có giao dịch cần xử lý</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<style>
.container {
  margin-left: 240px;
  padding: 20px;
  max-width: calc(100% - 280px);
}
h2 {
  text-align: center;
  margin-bottom: 20px;
  color: #2d3436;
}
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
th, td {
  border: 1px solid #ddd;
  padding: 10px;
  text-align: center;
}
th { background: #0984e3; color: white; }
tr:nth-child(even) { background: #f9f9f9; }
.inline-form {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  justify-content: center;
}
.status-select, input[type=text] {
  padding: 5px;
  font-size: 13px;
  border: 1px solid #ccc;
  border-radius: 5px;
}
.btn-update {
  background: #00b894;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 5px;
  cursor: pointer;
}
.btn-update:hover { background: #019875; }
.status.success { color: #27ae60; font-weight: bold; }
.status.pending { color: #e67e22; font-weight: bold; }
.status.failed { color: #e74c3c; font-weight: bold; }
.status["need review"] { color: #f1c40f; }
.success { color: #27ae60; font-weight: bold; }
.error { color: #d63031; font-weight: bold; }
</style>

<?php include("../includes/footer.php"); ?>
