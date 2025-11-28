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
$user_id = $user['userId'];
$user_unit = $user['unit'] ?? '';
$isAdmin = $user['isAdmin'] ?? 0;
$message = "";

// Kiểm tra quyền truy cập
$allowed_roles = ['BCH Trường', 'BCH Khoa', 'BCH Chi đoàn'];
if (!in_array($role_name, $allowed_roles) && !$isAdmin) {
  echo "<div class='container'><p style='color:red;'>Bạn không có quyền truy cập trang này.</p></div>";
  include("../includes/footer.php");
  exit();
}

//CẬP NHẬT TRẠNG THÁI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id']) && isset($_POST['status']) && $isAdmin) {
  $payment_id = intval($_POST['payment_id']);
  $new_status = $_POST['status'];

  $res = $conn->query("SELECT * FROM fee_payment WHERE id=$payment_id");
  $payment = $res->fetch_assoc();

  if ($payment) {
    $stmt = $conn->prepare("UPDATE fee_payment SET status=? WHERE id=?");
    $stmt->bind_param("si", $new_status, $payment_id);
    $stmt->execute();

    if ($new_status === 'Success') {
      $obligation_id = $payment['obligation_id'];
      $amount = $payment['amount'];

      $conn->query("UPDATE fee_obligation SET status='Đã nộp' WHERE id=$obligation_id");

      // Sinh biên lai
      $conn->query("
        INSERT INTO fee_receipt (payment_id, receipt_code, issued_by, amount)
        VALUES ($payment_id, CONCAT('RC-', $payment_id, '-', YEAR(NOW())), $user_id, $amount)
      ");

      // Ghi vào sổ quỹ
      $conn->query("
        INSERT INTO fee_cashbook (payment_id, transaction_type, amount, recorded_by, description)
        VALUES ($payment_id, 'Thu', $amount, $user_id, 'Duyệt giao dịch đoàn phí')
      ");
    }

    $message = "<p class='success'>Cập nhật trạng thái thành công!</p>";
  } else {
    $message = "<p class='error'>Không tìm thấy giao dịch.</p>";
  }
}

//XỬ LÝ TÌM KIẾM
$search_name = trim($_GET['search'] ?? "");

//TRUY VẤN DANH SÁCH
$sql = "
  SELECT 
    p.id, p.transaction_code, p.payment_method, p.amount, p.status, p.payment_date,
    u.fullName AS payer_name, o.period_label,
    ou.unit_name AS payer_unit
  FROM fee_payment p
  JOIN users u ON p.payer_id = u.userId
  JOIN fee_obligation o ON p.obligation_id = o.id
  LEFT JOIN organization_units ou ON u.unit = ou.id
  WHERE 1
";

// Tìm kiếm theo tên người nộp
if ($search_name !== "") {
  $sql .= " AND u.fullName LIKE '%" . $conn->real_escape_string($search_name) . "%'";
}

// Phân quyền hiển thị
if (!$isAdmin && $role_name !== 'BCH Trường') {
  if ($role_name === 'BCH Khoa') {
    $sql .= " AND (ou.parent_id = '" . $conn->real_escape_string($user_unit) . "' OR ou.id = '" . $conn->real_escape_string($user_unit) . "')";
  }
  if ($role_name === 'BCH Chi đoàn') {
    $sql .= " AND ou.id = '" . $conn->real_escape_string($user_unit) . "'";
  }
}

$sql .= " ORDER BY p.payment_date DESC";
$transactions = $conn->query($sql);
?>

<div class="container">
  <h2>Quản lý giao dịch đoàn phí</h2>
  <?= $message ?>

  <!-- Form tìm kiếm -->
  <form method="GET" class="search-form">
    <input type="text" name="search" placeholder="Tìm kiếm..." value="<?= htmlspecialchars($search_name) ?>">
    <button type="submit" class="btn-search">Tìm kiếm</button>
    <?php if ($search_name): ?>
      <a href="manage_transactions.php" class="btn-reset">Đặt lại</a>
    <?php endif; ?>
  </form>

  <table class="table">
    <thead>
      <tr>
        <th>Mã GD</th>
        <th>Người nộp</th>
        <th>Đơn vị</th>
        <th>Kỳ phí</th>
        <th>Số tiền</th>
        <th>Hình thức</th>
        <th>Ngày</th>
        <th>Trạng thái</th>
        <?php if ($isAdmin): ?><th>Thao tác</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if ($transactions && $transactions->num_rows > 0): ?>
        <?php while ($t = $transactions->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($t['transaction_code']) ?></td>
            <td><?= htmlspecialchars($t['payer_name']) ?></td>
            <td><?= htmlspecialchars($t['payer_unit'] ?? 'Chưa cập nhật') ?></td>
            <td><?= htmlspecialchars($t['period_label']) ?></td>
            <td><?= number_format($t['amount'], 0, ',', '.') ?>đ</td>
            <td><?= htmlspecialchars($t['payment_method']) ?></td>
            <td><?= date("d/m/Y H:i", strtotime($t['payment_date'])) ?></td>
            <td><span class="status <?= strtolower($t['status']) ?>"><?= htmlspecialchars($t['status']) ?></span></td>

            <?php if ($isAdmin): ?>
            <td>
              <form method="POST" class="inline-form">
                <input type="hidden" name="payment_id" value="<?= $t['id'] ?>">
                <select name="status" class="status-select">
                  <option value="Pending" <?= $t['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="Success" <?= $t['status'] == 'Success' ? 'selected' : '' ?>>Success</option>
                  <option value="Failed" <?= $t['status'] == 'Failed' ? 'selected' : '' ?>>Failed</option>
                  <option value="Need review" <?= $t['status'] == 'Need review' ? 'selected' : '' ?>>Need review</option>
                  <option value="Canceled" <?= $t['status'] == 'Canceled' ? 'selected' : '' ?>>Canceled</option>
                </select>
                <button type="submit" class="btn-update">Lưu</button>
              </form>
            </td>
            <?php endif; ?>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="<?= $isAdmin ? 9 : 8 ?>" style="text-align:center;">Không có giao dịch phù hợp.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<style>
.container {
  margin-left: 240px;
  padding: 20px;
  max-width: calc(100% - 300px);
}
h2 {
  text-align: center;
  margin-bottom: 20px;
  color: #2d3436;
}
.search-form {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-bottom: 15px;
}
.search-form input {
  width: 250px;
  padding: 6px 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
}
.btn-search, .btn-reset {
  background: #0984e3;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  text-decoration: none;
}
.btn-reset { background: #636e72; }
.btn-search:hover { background: #0772c3; }
.btn-reset:hover { background: #555; }

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
  justify-content: center;
  gap: 6px;
}
.status-select {
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
.status.needreview { color: #f1c40f; font-weight: bold; }

.success { color: #27ae60; font-weight: bold; text-align: center; }
.error { color: #d63031; font-weight: bold; text-align: center; }
</style>

<?php include("../includes/footer.php"); ?>
