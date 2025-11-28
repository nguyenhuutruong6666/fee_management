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

$currentUser = $_SESSION['user'];
$role = $currentUser['role_name'] ?? '';
$userId = intval($currentUser['userId']);
$userUnit = intval($currentUser['unit'] ?? 0);
$isAdmin = intval($currentUser['isAdmin'] ?? 0);

$allowedRoles = ['BCH Trường', 'BCH Khoa', 'BCH Chi đoàn'];
if ($isAdmin != 1 && !in_array($role, $allowedRoles)) {
  echo "<div class='container'><p style='color:red;'>Bạn không có quyền truy cập trang này.</p></div>";
  include("../includes/footer.php");
  exit();
}

$message = "";

//DUYỆT / KHÓA KỲ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['period_label'])) {
  $period_label = trim($_POST['period_label']);
  $note = trim($_POST['note']);
  $unit_type = $role;
  $unit_id = $userUnit;

  // Cấp trên
  $to_unit_type = ($unit_type === 'BCH Chi đoàn') ? 'BCH Khoa' :
                  (($unit_type === 'BCH Khoa') ? 'BCH Trường' : null);

  // Trường hợp BCH Trường thì coi là “Khóa dữ liệu”
  $newStatus = ($role === 'BCH Trường') ? 'Đã khóa dữ liệu' : 'Đã duyệt';

  $conn->begin_transaction();
  try {
    // Ghi hoặc cập nhật bản ghi duyệt
    $stmt = $conn->prepare("
      INSERT INTO fee_approval (period_label, unit_type, unit_id, approved_by, approved_at, status, note)
      VALUES (?, ?, ?, ?, NOW(), ?, ?)
      ON DUPLICATE KEY UPDATE
        status=VALUES(status),
        approved_by=VALUES(approved_by),
        approved_at=NOW(),
        note=VALUES(note)
    ");
    $stmt->bind_param("ssiiss", $period_label, $unit_type, $unit_id, $userId, $newStatus, $note);
    $stmt->execute();

    // Ghi log chuyển cấp
    if ($to_unit_type) {
      $log = $conn->prepare("
        INSERT INTO fee_approval_log (period_label, from_unit_type, from_unit_id, to_unit_type, sent_by, sent_at, status, note)
        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
      ");
      $logStatus = ($role === 'BCH Trường') ? 'Đã khóa dữ liệu' : 'Đã gửi';
      $log->bind_param("sssiiss", $period_label, $unit_type, $unit_id, $to_unit_type, $userId, $logStatus, $note);
      $log->execute();
    }

    // Nếu cấp hiện tại duyệt thì khóa dữ liệu cấp dưới tương ứng
    if ($role === 'BCH Khoa') {
      $conn->query("UPDATE fee_approval SET status='Đã khóa dữ liệu'
                    WHERE period_label='$period_label' AND unit_type='BCH Chi đoàn'");
    }
    if ($role === 'BCH Trường') {
      $conn->query("UPDATE fee_approval SET status='Đã khóa dữ liệu'
                    WHERE period_label='$period_label' AND unit_type='BCH Khoa'");
    }

    $conn->commit();
    $message = "<p class='success'>Đã duyệt / khóa dữ liệu kỳ <b>$period_label</b> thành công!</p>";
  } catch (Exception $e) {
    $conn->rollback();
    $message = "<p class='error'>Lỗi khi duyệt dữ liệu: " . $e->getMessage() . "</p>";
  }
}

//LỌC KỲ HIỂN THỊ
$unitCondition = "1=0";
$canApprove = false;

if ($role === 'BCH Chi đoàn') {
  $unitCondition = "ou.id = $userUnit";
  $canApprove = true;

} elseif ($role === 'BCH Khoa') {
  // Khoa chỉ xem các kỳ đã duyệt bởi chi đoàn
  $approvedPeriods = $conn->query("
    SELECT DISTINCT period_label
    FROM fee_approval fa
    JOIN organization_units ou2 ON fa.unit_id = ou2.id
    WHERE fa.unit_type='BCH Chi đoàn' AND ou2.parent_id=$userUnit
  ");
  $periodsList = [];
  while ($r = $approvedPeriods->fetch_assoc()) $periodsList[] = "'" . $r['period_label'] . "'";
  $unitCondition = $periodsList ? "(ou.id=$userUnit OR ou.parent_id=$userUnit) AND o.period_label IN (" . implode(",", $periodsList) . ")" : "1=0";
  $canApprove = !empty($periodsList);

} elseif ($role === 'BCH Trường') {
  // Trường chỉ xem các kỳ đã duyệt bởi khoa
  $approvedPeriods = $conn->query("
    SELECT DISTINCT period_label
    FROM fee_approval
    WHERE unit_type='BCH Khoa'
  ");
  $periodsList = [];
  while ($r = $approvedPeriods->fetch_assoc()) $periodsList[] = "'" . $r['period_label'] . "'";
  $unitCondition = $periodsList ? "o.period_label IN (" . implode(",", $periodsList) . ")" : "1=0";
  $canApprove = !empty($periodsList);
}

//LẤY DỮ LIỆU
$sql = "
  SELECT 
    o.period_label,
    COUNT(o.id) AS total,
    SUM(CASE WHEN o.status = 'Đã nộp' THEN 1 ELSE 0 END) AS paid,
    SUM(CASE WHEN o.status = 'Chưa nộp' THEN 1 ELSE 0 END) AS unpaid
  FROM fee_obligation o
  JOIN users u ON o.user_id = u.userId
  JOIN organization_units ou ON u.unit = ou.id
  WHERE $unitCondition
  GROUP BY o.period_label
  ORDER BY o.period_label DESC
";
$periods = $conn->query($sql);
?>

<!--GIAO DIỆN-->
<div class="container">
  <h2>Duyệt & Khóa dữ liệu đoàn phí theo kỳ</h2>
  <?= $message ?>

  <?php if ($periods && $periods->num_rows > 0): ?>
  <table class="data-table">
    <thead>
      <tr>
        <th>Kỳ</th>
        <th>Tổng đoàn viên</th>
        <th>Đã nộp</th>
        <th>Chưa nộp</th>
        <th>Tỷ lệ thu (%)</th>
        <th>Trạng thái</th>
        <th>Thao tác</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($r = $periods->fetch_assoc()):
        $rate = $r['total'] > 0 ? round(($r['paid'] / $r['total']) * 100, 2) : 0;
        $period_label = htmlspecialchars($r['period_label']);

        // Lấy trạng thái hiện tại của kỳ theo vai trò
        $st = $conn->query("SELECT status FROM fee_approval WHERE period_label='$period_label' AND unit_type='$role' LIMIT 1");
        $statusRow = $st && $st->num_rows > 0 ? $st->fetch_assoc()['status'] : 'Chưa duyệt';
        $locked = ($statusRow === 'Đã khóa dữ liệu');
      ?>
      <tr>
        <td><?= $period_label ?></td>
        <td><?= $r['total'] ?></td>
        <td style="color:green;"><?= $r['paid'] ?></td>
        <td style="color:red;"><?= $r['unpaid'] ?></td>
        <td><b><?= $rate ?>%</b></td>
        <td><span class="<?= $locked ? 'locked' : 'status' ?>"><?= htmlspecialchars($statusRow) ?></span></td>
        <td>
          <?php if ($locked): ?>
            <button class="btn-locked" disabled>Đã khóa dữ liệu</button>
          <?php elseif ($statusRow === 'Đã duyệt'): ?>
            <button class="btn-disabled" disabled>Đã duyệt</button>
          <?php elseif ($canApprove): ?>
            <button class="btn-approve" onclick="openApproveModal('<?= $period_label ?>')">
              <?= ($role === 'BCH Trường') ? 'Khóa dữ liệu' : 'Duyệt kỳ' ?>
            </button>
          <?php else: ?>
            <button class="btn-disabled" disabled>Chờ cấp dưới duyệt</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p style="text-align:center;">Không có dữ liệu nào để hiển thị.</p>
  <?php endif; ?>
</div>

<!-- Modal -->
<div id="approveModal" class="modal">
  <div class="modal-content">
    <h3>Xác nhận duyệt / khóa kỳ</h3>
    <form method="POST">
      <input type="hidden" name="period_label" id="period_label">
      <label>Ghi chú:</label>
      <textarea name="note" rows="3" placeholder="VD: Hoàn thành 100%, gửi lên cấp trên."></textarea>
      <div class="actions">
        <button type="submit" class="btn-confirm">Xác nhận</button>
        <button type="button" class="btn-cancel" onclick="closeModal()">Hủy</button>
      </div>
    </form>
  </div>
</div>

<style>
.container { padding:25px; margin-left:240px; max-width:calc(100% - 310px);}
h2 { text-align:center; color:#2d3436; margin-bottom:25px; }
.data-table { width:100%; border-collapse:collapse; margin-top:20px;}
.data-table th, .data-table td { border:1px solid #ccc; padding:10px; text-align:center; }
.data-table th { background:#f1f2f6; }
.btn-approve { background:#27ae60; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; }
.btn-disabled, .btn-locked { background:#b2bec3; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:not-allowed; }
.btn-approve:hover { background:#219150; }
.locked { color:#e74c3c; font-weight:bold; }
.success { color:#27ae60; font-weight:bold; text-align:center; }
.error { color:#d63031; font-weight:bold; text-align:center; }
.modal { display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);}
.modal-content { background:white; margin:10% auto; padding:20px; border-radius:10px; width:400px;}
textarea { width:100%; padding:8px; border-radius:6px; border:1px solid #ccc; }
.actions { text-align:right; margin-top:15px;}
.btn-confirm { background:#0984e3; color:white; padding:8px 14px; border:none; border-radius:6px; cursor:pointer;}
.btn-cancel { background:#b2bec3; color:white; padding:8px 14px; border:none; border-radius:6px; cursor:pointer;}
</style>

<script>
function openApproveModal(period) {
  document.getElementById('period_label').value = period;
  document.getElementById('approveModal').style.display = 'block';
}
function closeModal() {
  document.getElementById('approveModal').style.display = 'none';
}
</script>

<?php include("../includes/footer.php"); ?>
