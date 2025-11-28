<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");
require_once("../libs/fpdf/fpdf.php");
require_once("../libs/PHP_XLSXWriter/xlsxwriter.class.php");

// KIỂM TRA QUYỀN
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['user'];
$userId = intval($user['userId']);
$userRole = $user['role_name'] ?? '';
$userUnit = intval($user['unit'] ?? 0);
$isAdmin = intval($user['isAdmin'] ?? 0);

$allowedRoles = ['BCH Trường', 'BCH Khoa', 'BCH Chi đoàn'];
if ($isAdmin != 1 && !in_array($userRole, $allowedRoles)) {
  echo "<div class='container'><p class='error'>Bạn không có quyền truy cập chức năng này.</p></div>";
  include("../includes/footer.php");
  exit();
}

// =========================================
// TỰ ĐỘNG LỌC THEO VAI TRÒ
// =========================================
$roleCondition = "";

if ($isAdmin != 1) {
    if ($userRole == 'BCH Trường') {
        $roleCondition = "";
    } elseif ($userRole == 'BCH Khoa') {
        $roleCondition = "
            AND (
                (f.unit_type='BCH Khoa' AND f.unit_id=$userUnit)
                OR 
                (f.unit_type='BCH Chi đoàn' AND u.parent_id=$userUnit)
            )
        ";
    } elseif ($userRole == 'BCH Chi đoàn') {
        $roleCondition = " AND f.unit_type='BCH Chi đoàn' AND f.unit_id=$userUnit";
    }
}

// =========================================
// LẤY TOÀN BỘ DỮ LIỆU ĐÃ DUYỆT VÀ KHÓA
// =========================================
$query = "
    SELECT f.*, 
           u.unit_name AS unit_name,
           u.parent_id,
           p.unit_name AS parent_name
    FROM fee_summary f
    LEFT JOIN organization_units u ON f.unit_id = u.id
    LEFT JOIN organization_units p ON u.parent_id = p.id
    WHERE f.locked = 1
      AND f.approval_status LIKE '%Approved%'
      $roleCondition
    ORDER BY f.unit_type, u.parent_id, f.unit_id ASC
";

$reportData = $conn->query($query);

?>
<!-- ========================================= -->
<!-- GIAO DIỆN HIỂN THỊ -->
<!-- ========================================= -->

<div class="container">
  <h2>Báo cáo tổng hợp hoạt động đoàn phí</h2>

  <?php if (!$reportData || $reportData->num_rows == 0): ?>
    <p class="error">Không có dữ liệu để hiển thị.</p>
  <?php else: ?>

  <table class="data-table">
    <thead>
      <tr>
        <th>Kỳ</th>
        <th>Cấp</th>
        <th>Tên đơn vị</th>
        <th>Thuộc đơn vị</th>
        <th>Tổng ĐV</th>
        <th>Đã nộp</th>
        <th>Chưa nộp</th>
        <th>Miễn</th>
        <th>Tổng thu (đ)</th>
        <th>Cập nhật</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($r = $reportData->fetch_assoc()): ?>
      <tr>
        <td><?= $r['period_label'] ?></td>
        <td><?= $r['unit_type'] ?></td>
        <td><?= $r['unit_name'] ?></td>
        <td><?= $r['parent_name'] ?: '-' ?></td>
        <td><?= $r['total_members'] ?></td>
        <td><?= $r['paid_members'] ?></td>
        <td><?= $r['unpaid_members'] ?></td>
        <td><?= $r['exempt_members'] ?></td>
        <td><?= number_format($r['total_collected']) ?>đ</td>
        <td><?= date("d/m/Y", strtotime($r['updated_at'])) ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Nút xuất file -->
  <form method="POST" style="margin-top:20px;">
    <input type="hidden" name="action" value="export">
    <button name="format" value="pdf" class="btn-export">Xuất PDF</button>
    <button name="format" value="excel" class="btn-export">Xuất Excel</button>
  </form>

  <?php endif; ?>
</div>

<style>
.container { padding:25px; margin-left:240px; max-width:calc(100% - 310px);}
h2 { text-align:center; font-size:25px; margin-bottom:25px; font-weight:bold; }
.data-table { width:100%; border-collapse:collapse; margin-top:20px;}
.data-table th { background:#dfe6e9; padding:10px; border:1px solid #ccc; }
.data-table td { padding:8px; border:1px solid #ccc; text-align:center; }
.btn-export { 
  background:#00b894; 
  padding:10px 15px; 
  color:white; 
  border:none; 
  border-radius:6px; 
  cursor:pointer;
  margin-right:10px;
}
.btn-export:hover { background:#019267; }
.error { color:#d63031; font-weight:bold; text-align:center; margin-top:20px; }
</style>

<?php include("../includes/footer.php"); ?>
