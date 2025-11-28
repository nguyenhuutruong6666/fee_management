<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

//KIỂM TRA QUYỀN
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['user'];
$user_id = $user['userId'];
$user_role = $user['role_name'] ?? '';

if (!in_array($user_role, ['BCH Khoa', 'BCH Trường'])) {
  echo "<div class='container'><p style='color:red;'>Bạn không có quyền truy cập chức năng này.</p></div>";
  include("../includes/footer.php");
  exit();
}

$message = "";

//XỬ LÝ PHÊ DUYỆT / TỪ CHỐI
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $proposal_id = intval($_POST['proposal_id']);
  $action = $_POST['action'];
  $approved_budget = floatval($_POST['approved_budget'] ?? 0);
  $comment = trim($_POST['approval_comment'] ?? '');
  $reason = trim($_POST['rejection_reason'] ?? '');

  if ($action === 'approve') {
    if ($approved_budget <= 0) {
      $message = "<p class='error'>Vui lòng nhập hạn mức kinh phí hợp lệ khi phê duyệt.</p>";
    } else {
      $stmt = $conn->prepare("
        UPDATE activity_proposal 
        SET status='Đã phê duyệt', approved_by=?, approved_at=NOW(), approved_budget=?, approval_comment=? 
        WHERE id=? AND status='Chờ phê duyệt'
      ");
      $stmt->bind_param("idsi", $user_id, $approved_budget, $comment, $proposal_id);
      if ($stmt->execute()) {
        $log = $conn->prepare("
          INSERT INTO activity_approval_log (proposal_id, action, performed_by, performed_at, note)
          VALUES (?, 'Phê duyệt', ?, NOW(), ?)
        ");
        $note = "Đề xuất được phê duyệt với hạn mức " . number_format($approved_budget, 0, ',', '.') . "đ";
        $log->bind_param("iis", $proposal_id, $user_id, $note);
        $log->execute();
        $message = "<p class='success'>Đã phê duyệt đề xuất thành công!</p>";
      }
    }
  }

  if ($action === 'reject') {
    if (empty($reason)) {
      $message = "<p class='error'>Vui lòng nhập lý do từ chối.</p>";
    } else {
      $stmt = $conn->prepare("
        UPDATE activity_proposal 
        SET status='Từ chối', approved_by=?, approved_at=NOW(), rejection_reason=? 
        WHERE id=? AND status='Chờ phê duyệt'
      ");
      $stmt->bind_param("isi", $user_id, $reason, $proposal_id);
      if ($stmt->execute()) {
        $log = $conn->prepare("
          INSERT INTO activity_approval_log (proposal_id, action, performed_by, performed_at, note)
          VALUES (?, 'Từ chối', ?, NOW(), ?)
        ");
        $log->bind_param("iis", $proposal_id, $user_id, $reason);
        $log->execute();
        $message = "<p class='success'>Đã từ chối đề xuất thành công!</p>";
      }
    }
  }
}

//LẤY ĐỀ XUẤT CHỜ PHÊ DUYỆT
$proposals = $conn->query("
  SELECT p.id, p.title, p.content, p.estimated_budget, p.expected_date, p.created_at, 
         u.fullName AS proposer_name, ou.unit_name
  FROM activity_proposal p
  JOIN users u ON p.proposer_id = u.userId
  LEFT JOIN organization_units ou ON u.unit = ou.id
  WHERE p.status = 'Chờ phê duyệt'
  ORDER BY p.created_at DESC
");

//LẤY ĐỀ XUẤT ĐÃ DUYỆT
$approved = $conn->query("
  SELECT p.id, p.title, p.approved_budget, p.approval_comment, p.approved_at, u.fullName
  FROM activity_proposal p
  JOIN users u ON p.proposer_id = u.userId
  WHERE p.status = 'Đã phê duyệt'
  ORDER BY p.approved_at DESC
");
?>

<div class="container">
  <h2>Quản lý phê duyệt hoạt động</h2>
  <?= $message ?>

  <h3>Hoạt động chờ phê duyệt</h3>
  <?php if ($proposals->num_rows > 0): ?>
    <div class="proposal-grid">
      <?php while ($p = $proposals->fetch_assoc()): ?>
        <div class="proposal-card" id="card-<?= $p['id'] ?>">
          <h4><?= htmlspecialchars($p['title']) ?></h4>
          <p><b>Người đề xuất:</b> <?= htmlspecialchars($p['proposer_name']) ?> (<?= htmlspecialchars($p['unit_name']) ?>)</p>
          <p><b>Dự toán:</b> <?= number_format($p['estimated_budget'], 0, ',', '.') ?>đ</p>
          <p><b>Ngày dự kiến:</b> <?= date("d/m/Y", strtotime($p['expected_date'])) ?></p>
          <p><b>Nội dung:</b> <?= nl2br(htmlspecialchars($p['content'])) ?></p>

          <div class="actions">
            <button class="btn-approve" onclick="showApproveForm(<?= $p['id'] ?>)">Phê duyệt</button>
            <button class="btn-reject" onclick="showRejectForm(<?= $p['id'] ?>)">Từ chối</button>
          </div>

          <!-- Form phê duyệt -->
          <form method="POST" class="approval-form hidden" id="approve-form-<?= $p['id'] ?>">
            <input type="hidden" name="proposal_id" value="<?= $p['id'] ?>">
            <label>Hạn mức kinh phí:</label>
            <input type="number" name="approved_budget" min="0" step="1000" required>
            <label>Ghi chú:</label>
            <textarea name="approval_comment" rows="2"></textarea>
            <div class="form-actions">
              <button type="submit" name="action" value="approve" class="btn-confirm">Xác nhận phê duyệt</button>
              <button type="button" class="btn-cancel" onclick="hideForms(<?= $p['id'] ?>)">Hủy</button>
            </div>
          </form>

          <!-- Form từ chối -->
          <form method="POST" class="rejection-form hidden" id="reject-form-<?= $p['id'] ?>">
            <input type="hidden" name="proposal_id" value="<?= $p['id'] ?>">
            <label>Lý do từ chối:</label>
            <textarea name="rejection_reason" rows="2" required></textarea>
            <div class="form-actions">
              <button type="submit" name="action" value="reject" class="btn-confirm">Xác nhận từ chối</button>
              <button type="button" class="btn-cancel" onclick="hideForms(<?= $p['id'] ?>)">Hủy</button>
            </div>
          </form>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <p style="text-align:center;">Không có đề xuất nào chờ phê duyệt.</p>
  <?php endif; ?>

  <hr>
  <h3>Hoạt động đã phê duyệt</h3>
  <?php if ($approved->num_rows > 0): ?>
    <div class="approved-grid">
      <?php while ($a = $approved->fetch_assoc()): ?>
        <div class="approved-card">
          <h4><?= htmlspecialchars($a['title']) ?></h4>
          <p><b>Hạn mức kinh phí:</b> <?= number_format($a['approved_budget'], 0, ',', '.') ?>đ</p>
          <p><b>Người đề xuất:</b> <?= htmlspecialchars($a['fullName']) ?></p>
          <p><b>Ghi chú:</b> <?= htmlspecialchars($a['approval_comment']) ?></p>
          <p><b>Phê duyệt ngày:</b> <?= date("d/m/Y H:i", strtotime($a['approved_at'])) ?></p>
          
          <!-- Nút chuyển sang disbursement -->
          <a href="disbursement.php?id=<?= $a['id'] ?>" class="btn-transfer">Tạm ứng/Chi tiền</a>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <p style="text-align:center;">Chưa có hoạt động nào được phê duyệt.</p>
  <?php endif; ?>
</div>

<script>
function showApproveForm(id) {
  hideForms(id);
  document.getElementById("approve-form-" + id).classList.remove("hidden");
}
function showRejectForm(id) {
  hideForms(id);
  document.getElementById("reject-form-" + id).classList.remove("hidden");
}
function hideForms(id) {
  document.getElementById("approve-form-" + id).classList.add("hidden");
  document.getElementById("reject-form-" + id).classList.add("hidden");
}
</script>

<style>
.container { padding:25px; margin-left:240px; max-width:calc(100% - 300px);}
h2, h3 { text-align:center; color:#2d3436; margin-bottom:20px;}
.proposal-grid, .approved-grid {
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));
  gap:20px;
}
.proposal-card, .approved-card {
  background:white;
  border-radius:12px;
  padding:20px;
  box-shadow:0 3px 8px rgba(0,0,0,0.1);
  transition:0.2s;
}
.proposal-card:hover, .approved-card:hover { transform:translateY(-3px);}
.actions { display:flex; justify-content:space-between; margin-top:10px;}
.btn-approve, .btn-reject {
  border:none; padding:8px 14px; border-radius:6px; color:white; cursor:pointer;
}
.btn-approve { background:#27ae60; }
.btn-approve:hover { background:#1f8c4a; }
.btn-reject { background:#e74c3c; }
.btn-reject:hover { background:#c0392b; }
.hidden { display:none; }
form { margin-top:10px; background:#f9f9f9; padding:10px; border-radius:8px;}
input, textarea { width:100%; border:1px solid #ccc; border-radius:6px; padding:6px; margin-top:4px;}
.form-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:10px;}
.btn-confirm { background:#0984e3; color:white; border:none; padding:6px 14px; border-radius:6px;}
.btn-cancel { background:#b2bec3; color:white; border:none; padding:6px 14px; border-radius:6px;}
.btn-transfer { margin-top:10px; display:inline-block; width:100%; text-align:center; background:#f1c40f; border:none; padding:10px; border-radius:6px; font-weight:bold; cursor:pointer; color:black; text-decoration:none;}
.btn-transfer:hover { background:#d4ac0d; color:white; }
.success { color:#27ae60; font-weight:bold; text-align:center; }
.error { color:#e74c3c; font-weight:bold; text-align:center; }
</style>

<?php include("../includes/footer.php"); ?>
