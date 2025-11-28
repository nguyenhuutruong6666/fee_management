<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

//KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['user'];
$user_id = intval($user['userId']);
$user_role = $user['role_name'] ?? '';
$user_unit = intval($user['unit'] ?? 0);
$isAdmin = intval($user['isAdmin'] ?? 0);

$allowedRoles = ['BCH Chi đoàn', 'BCH Khoa', 'BCH Trường'];
if ($isAdmin != 1 && !in_array($user_role, $allowedRoles)) {
  echo "<div class='container'><p class='error'>Bạn không có quyền truy cập chức năng này.</p></div>";
  include("../includes/footer.php");
  exit();
}

//LẤY THÔNG TIN TÊN ĐƠN VỊ
$unitName = '';
$unitQuery = $conn->query("SELECT unit_name FROM organization_units WHERE id = $user_unit");
if ($unitQuery && $unitQuery->num_rows > 0) {
  $unitName = $unitQuery->fetch_assoc()['unit_name'];
}

//TÍNH TỔNG SỐ TIỀN ĐOÀN VIÊN ĐÃ NỘP
$current_balance = 0;
$totalMembers = 0;
$paidMembers = 0;

if ($user_role === 'BCH Chi đoàn') {
  // Tổng tiền đoàn viên trong chi đoàn đó đã nộp
  $sql = "
    SELECT SUM(o.amount) AS total_collected, COUNT(DISTINCT o.user_id) AS paid_members
    FROM fee_obligation o
    JOIN users u ON o.user_id = u.userId
    WHERE u.unit = $user_unit AND o.status = 'Đã nộp'
  ";
  $res = $conn->query($sql);
  if ($res && $r = $res->fetch_assoc()) {
    $current_balance = floatval($r['total_collected']);
    $paidMembers = intval($r['paid_members']);
  }
  $totalMembers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE unit = $user_unit")->fetch_assoc()['total'] ?? 0;

} elseif ($user_role === 'BCH Khoa') {
  // Tổng tiền của tất cả chi đoàn thuộc khoa
  $sql = "
    SELECT SUM(o.amount) AS total_collected, COUNT(DISTINCT o.user_id) AS paid_members
    FROM fee_obligation o
    JOIN users u ON o.user_id = u.userId
    JOIN organization_units ou ON u.unit = ou.id
    WHERE ou.parent_id = $user_unit AND o.status = 'Đã nộp'
  ";
  $res = $conn->query($sql);
  if ($res && $r = $res->fetch_assoc()) {
    $current_balance = floatval($r['total_collected']);
    $paidMembers = intval($r['paid_members']);
  }
  $totalMembers = $conn->query("
    SELECT COUNT(*) AS total 
    FROM users u JOIN organization_units ou ON u.unit = ou.id 
    WHERE ou.parent_id = $user_unit
  ")->fetch_assoc()['total'] ?? 0;

} elseif ($user_role === 'BCH Trường') {
  // Tổng tiền tất cả đoàn viên toàn trường
  $sql = "SELECT SUM(o.amount) AS total_collected, COUNT(DISTINCT o.user_id) AS paid_members
          FROM fee_obligation o WHERE o.status = 'Đã nộp'";
  $res = $conn->query($sql);
  if ($res && $r = $res->fetch_assoc()) {
    $current_balance = floatval($r['total_collected']);
    $paidMembers = intval($r['paid_members']);
  }
  $totalMembers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'] ?? 0;
}

//LẤY DANH SÁCH ĐOÀN VIÊN ĐÃ NỘP (TOP 20)
$transactions = $conn->query("
  SELECT u.fullName, o.period_label, o.amount, o.updated_at
  FROM fee_obligation o
  JOIN users u ON o.user_id = u.userId
  WHERE o.status = 'Đã nộp' 
    AND (
      ('$user_role' = 'BCH Chi đoàn' AND u.unit = $user_unit)
      OR ('$user_role' = 'BCH Khoa' AND u.unit IN (SELECT id FROM organization_units WHERE parent_id = $user_unit))
      OR ('$user_role' = 'BCH Trường')
    )
  ORDER BY o.updated_at DESC
  LIMIT 20
");
?>

<div class="container">
  <h2>Quản lý Sổ quỹ đoàn phí</h2>
  <div class="action-buttons">
    <a href="approve_fee_period.php" class="btn btn-approve">Phê duyệt kỳ thu phí</a>
    <a href="allocate_transfer.php" class="btn btn-transfer">Phân bổ & Chuyển nộp</a>
  </div>

  <div class="summary-box">
    <h3>Đơn vị: <?= htmlspecialchars($unitName ?: 'Không xác định') ?></h3>
    <p><strong>Cấp quản lý:</strong> <?= htmlspecialchars($user_role) ?></p>
    <div class="balance-card">
      <h4>Tổng số dư hiện tại (đoàn viên & BCH đã nộp)</h4>
      <div class="balance-amount"><?= number_format($current_balance, 0, ',', '.') ?>đ</div>
      <p class="sub-info">Đã nộp: <?= $paidMembers ?>/<?= $totalMembers ?> đoàn viên</p>
    </div>
  </div>

  <hr>
  <h3>Danh sách đoàn viên đã nộp gần nhất</h3>

  <table class="data-table">
    <thead>
      <tr>
        <th>Họ tên</th>
        <th>Kỳ thu phí</th>
        <th>Số tiền</th>
        <th>Ngày nộp</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($transactions && $transactions->num_rows > 0): ?>
        <?php while ($t = $transactions->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($t['fullName']) ?></td>
            <td><?= htmlspecialchars($t['period_label']) ?></td>
            <td style="color:#27ae60"><?= number_format($t['amount'], 0, ',', '.') ?>đ</td>
            <td><?= date('d/m/Y H:i', strtotime($t['updated_at'])) ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="4" style="text-align:center;">Không có bản ghi nộp đoàn phí nào.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<style>
.container { padding: 25px; margin-left: 240px; max-width: calc(100% - 300px);}
h2 { text-align:center; margin-bottom:25px; color:#2d3436;}
.summary-box { background:#f8f9fa; border-radius:10px; padding:20px; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
.balance-card { text-align:center; background:#eafbea; padding:20px; border-radius:10px; border:2px solid #2ecc71; margin-top:15px;}
.balance-amount { font-size:26px; color:#27ae60; font-weight:bold; margin-top:10px;}
.sub-info { color:#636e72; font-size:14px;}
.data-table { width:100%; border-collapse:collapse; margin-top:20px;}
.data-table th, .data-table td { border:1px solid #ccc; padding:10px; text-align:center;}
.data-table th { background:#0984e3; color:white;}
.action-buttons { margin-bottom:20px; display:flex; gap:15px;}
.action-buttons .btn { padding:10px 18px; font-size:15px; border-radius:6px; color:white; text-decoration:none; font-weight:600; transition:0.2s;}
.btn-approve { background:#27ae60;}
.btn-approve:hover { background:#1e874b;}
.btn-transfer { background:#0984e3;}
.btn-transfer:hover { background:#0868b9;}
tr:nth-child(even){ background:#f9f9f9;}
.error{ color:#d63031; text-align:center; font-weight:bold;}
</style>

<?php include("../includes/footer.php"); ?>
