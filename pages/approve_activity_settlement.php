<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

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

$allowedRoles = ['BCH Khoa', 'BCH Trường'];
if ($isAdmin != 1 && !in_array($userRole, $allowedRoles)) {
  echo "<div class='container'><p class='error'>Bạn không có quyền truy cập chức năng này.</p></div>";
  include("../includes/footer.php");
  exit();
}

$message = "";

//XỬ LÝ DUYỆT / TỪ CHỐI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $settlement_id = intval($_POST['settlement_id']);
  $action = $_POST['action'];
  $note = trim($_POST['note']);

  // Lấy dữ liệu quyết toán
  $stmt = $conn->prepare("
    SELECT s.*, ap.title, ap.unit_id, ap.unit_type
    FROM activity_settlement s
    JOIN activity_proposal ap ON s.activity_id = ap.id
    WHERE s.id = ? AND s.status = 'Chờ duyệt'
  ");
  $stmt->bind_param("i", $settlement_id);
  $stmt->execute();
  $settlement = $stmt->get_result()->fetch_assoc();

  if (!$settlement) {
    $message = "<p class='error'>Không tìm thấy báo cáo hợp lệ để duyệt.</p>";
  } else {
    $conn->begin_transaction();
    try {
      $activity_id = $settlement['activity_id'];
      $actual = floatval($settlement['actual_expense']);
      $proposed = floatval($settlement['proposed_budget']);
      $unit_type = $settlement['unit_type'];
      $unit_id = $settlement['unit_id'];
      $file_path = $settlement['file_path'];

      if ($action === 'approve') {
        // Ghi vào sổ quỹ tổng hợp
        $insertVoucher = $conn->prepare("
          INSERT INTO fee_allocation_voucher
          (period_label, unit_type, unit_id, total_amount, status, created_by, created_at)
          VALUES (?, ?, ?, ?, 'Approved', ?, NOW())
        ");
        $period = date("Y") . " - Quyết toán HĐ";
        $insertVoucher->bind_param("ssidi", $period, $userRole, $userUnit, $actual, $userId);
        $insertVoucher->execute();

        // Cập nhật tổng hợp kỳ (fee_summary)
        $updateSummary = $conn->prepare("
          UPDATE fee_summary 
          SET total_collected = total_collected + ?, approval_status='Approved & Locked', locked=1, updated_at=NOW()
          WHERE unit_id=? AND unit_type=? AND period_label=?
        ");
        $updateSummary->bind_param("diss", $actual, $unit_id, $unit_type, $period);
        $updateSummary->execute();

        // Cập nhật trạng thái quyết toán & hoạt động
        $updateSettle = $conn->prepare("UPDATE activity_settlement SET status='Đã duyệt & khóa', approved_by=?, approved_at=NOW(), note=? WHERE id=?");
        $updateSettle->bind_param("isi", $userId, $note, $settlement_id);
        $updateSettle->execute();

        $updateActivity = $conn->prepare("UPDATE activity_proposal SET status='Đã duyệt quyết toán', updated_at=NOW() WHERE id=?");
        $updateActivity->bind_param("i", $activity_id);
        $updateActivity->execute();

        $conn->commit();
        $message = "<p class='success'>Đã phê duyệt & khóa quyết toán cho hoạt động <b>{$settlement['title']}</b>.</p>";

      } elseif ($action === 'reject') {
        // Từ chối duyệt
        $updateReject = $conn->prepare("UPDATE activity_settlement SET status='Bị từ chối', note=?, approved_by=?, approved_at=NOW() WHERE id=?");
        $updateReject->bind_param("sii", $note, $userId, $settlement_id);
        $updateReject->execute();

        $updateAct = $conn->prepare("UPDATE activity_proposal SET status='Bị từ chối quyết toán', updated_at=NOW() WHERE id=?");
        $updateAct->bind_param("i", $settlement['activity_id']);
        $updateAct->execute();

        $conn->commit();
        $message = "<p class='error'>Đã từ chối quyết toán cho hoạt động <b>{$settlement['title']}</b>.</p>";
      }

    } catch (Exception $e) {
      $conn->rollback();
      $message = "<p class='error'>Lỗi xử lý duyệt: " . $e->getMessage() . "</p>";
    }
  }
}

//DANH SÁCH QUYẾT TOÁN CHỜ DUYỆT
$sql = "
  SELECT s.id, ap.title, s.proposed_budget, s.actual_expense, s.difference, s.status, s.file_path, s.submitted_at
  FROM activity_settlement s
  JOIN activity_proposal ap ON s.activity_id = ap.id
  WHERE s.status = 'Chờ duyệt'
  ORDER BY s.submitted_at DESC
";
$pending = $conn->query($sql);
?>

<div class="container">
  <h2>Duyệt & Khóa quyết toán</h2>
  <?= $message ?>

  <?php if ($pending->num_rows > 0): ?>
  <table class="data-table">
    <thead>
      <tr>
        <th>Hoạt động</th>
        <th>Dự toán</th>
        <th>Thực chi</th>
        <th>Chênh lệch</th>
        <th>Tệp</th>
        <th>Ngày nộp</th>
        <th>Thao tác</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($s = $pending->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($s['title']) ?></td>
          <td><?= number_format($s['proposed_budget']) ?>đ</td>
          <td><?= number_format($s['actual_expense']) ?>đ</td>
          <td><?= number_format($s['difference']) ?>đ</td>
          <td><a href="../uploads/settlements/<?= htmlspecialchars($s['file_path']) ?>" target="_blank">📎 Xem</a></td>
          <td><?= date("d/m/Y H:i", strtotime($s['submitted_at'])) ?></td>
          <td>
            <button class="btn-approve" onclick="openModal('approve', <?= $s['id'] ?>)">Duyệt</button>
            <button class="btn-reject" onclick="openModal('reject', <?= $s['id'] ?>)">Từ chối</button>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p>Không có quyết toán nào chờ duyệt.</p>
  <?php endif; ?>
</div>

<!-- Modal xác nhận -->
<div id="actionModal" class="modal">
  <div class="modal-content">
    <h3 id="modalTitle"></h3>
    <form method="POST">
      <input type="hidden" name="settlement_id" id="settlement_id">
      <input type="hidden" name="action" id="action_type">
      <label>Ghi chú (nếu có):</label>
      <textarea name="note" rows="3" placeholder="Nhập ghi chú duyệt / lý do từ chối..."></textarea>
      <div class="actions">
        <button type="submit" class="btn-confirm">Xác nhận</button>
        <button type="button" class="btn-cancel" onclick="closeModal()">Hủy</button>
      </div>
    </form>
  </div>
</div>

<style>
.container { padding:25px; margin-left:240px; max-width:calc(100% - 310px);}
h2 { text-align:center; color:#2d3436; margin-bottom:25px;}
.data-table { width:100%; border-collapse:collapse; margin-top:20px;}
.data-table th, .data-table td { border:1px solid #ccc; padding:10px; text-align:center;}
.data-table th { background:#f1f2f6;}
.btn-approve, .btn-reject { border:none; padding:6px 12px; border-radius:6px; cursor:pointer; }
.btn-approve { background:#27ae60; color:white;}
.btn-reject { background:#e74c3c; color:white;}
.success { color:#27ae60; font-weight:bold; text-align:center;}
.error { color:#e74c3c; font-weight:bold; text-align:center;}
.modal { display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);}
.modal-content { background:white; margin:10% auto; padding:20px; border-radius:10px; width:400px;}
.actions { text-align:right; margin-top:15px;}
.btn-confirm { background:#0984e3; color:white; padding:8px 14px; border:none; border-radius:6px; cursor:pointer;}
.btn-cancel { background:#b2bec3; color:white; padding:8px 14px; border:none; border-radius:6px; cursor:pointer;}
</style>

<script>
function openModal(type, id) {
  document.getElementById('actionModal').style.display = 'block';
  document.getElementById('settlement_id').value = id;
  document.getElementById('action_type').value = type;
  document.getElementById('modalTitle').innerText = (type === 'approve') ? "Xác nhận duyệt & khóa quyết toán" : "Từ chối quyết toán";
}
function closeModal() {
  document.getElementById('actionModal').style.display = 'none';
}
</script>

<?php include("../includes/footer.php"); ?>
