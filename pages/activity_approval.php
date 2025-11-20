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

//XỬ LÝ DUYỆT / TỪ CHỐI
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
        // Ghi log
        $log = $conn->prepare("
          INSERT INTO activity_approval_log (proposal_id, action, performed_by, performed_at, note)
          VALUES (?, 'Phê duyệt', ?, NOW(), ?)
        ");
        $note = "Đề xuất được phê duyệt với hạn mức " . number_format($approved_budget, 0, ',', '.') . "đ";
        $log->bind_param("iis", $proposal_id, $user_id, $note);
        $log->execute();

        $message = "<p class='success'>Đã phê duyệt đề xuất thành công!</p>";
      } else {
        $message = "<p class='error'>Lỗi khi cập nhật dữ liệu.</p>";
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
        // Ghi log
        $log = $conn->prepare("
          INSERT INTO activity_approval_log (proposal_id, action, performed_by, performed_at, note)
          VALUES (?, 'Từ chối', ?, NOW(), ?)
        ");
        $log->bind_param("iis", $proposal_id, $user_id, $reason);
        $log->execute();

        $message = "<p class='success'>Đã từ chối đề xuất thành công!</p>";
      } else {
        $message = "<p class='error'>Lỗi khi cập nhật dữ liệu.</p>";
      }
    }
  }
}

//LẤY DANH SÁCH ĐỀ XUẤT CHỜ DUYỆT
$sql = "
  SELECT p.id, p.title, p.content, p.estimated_budget, p.expected_date, p.created_at, u.fullName AS proposer_name, u.unit AS unit_name
  FROM activity_proposal p
  JOIN users u ON p.proposer_id = u.userId
  WHERE p.status = 'Chờ phê duyệt'
  ORDER BY p.created_at DESC
";
$proposals = $conn->query($sql);
?>

<div class="container">
  <h2>Danh sách đề xuất hoạt động chờ phê duyệt</h2>
  <?= $message ?>

  <?php if ($proposals->num_rows > 0): ?>
    <?php while ($p = $proposals->fetch_assoc()): ?>
      <div class="proposal-card">
        <h3><?= htmlspecialchars($p['title']) ?></h3>
        <p><b>Người đề xuất:</b> <?= htmlspecialchars($p['proposer_name']) ?> (<?= htmlspecialchars($p['unit_name']) ?>)</p>
        <p><b>Dự toán:</b> <?= number_format($p['estimated_budget'], 0, ',', '.') ?>đ</p>
        <p><b>Ngày dự kiến:</b> <?= date("d/m/Y", strtotime($p['expected_date'])) ?></p>
        <p><b>Nội dung:</b> <?= nl2br(htmlspecialchars($p['content'])) ?></p>
        <hr>
        <form method="POST" class="approval-form">
          <input type="hidden" name="proposal_id" value="<?= $p['id'] ?>">
          
          <div class="form-section">
            <label>Hạn mức kinh phí (nếu phê duyệt):</label>
            <input type="number" name="approved_budget" min="0" step="1000" placeholder="VD: 4500000">
          </div>

          <div class="form-section">
            <label>Ghi chú (nếu phê duyệt):</label>
            <textarea name="approval_comment" rows="2" placeholder="Ghi chú thêm (nếu có)..."></textarea>
          </div>

          <div class="form-section">
            <label>Lý do từ chối (nếu từ chối):</label>
            <textarea name="rejection_reason" rows="2" placeholder="Nhập lý do từ chối..."></textarea>
          </div>

          <div class="form-actions">
            <button type="submit" name="action" value="approve" class="btn-approve">Phê duyệt</button>
            <button type="submit" name="action" value="reject" class="btn-reject">Từ chối</button>
          </div>
        </form>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>Không có đề xuất nào đang chờ phê duyệt.</p>
  <?php endif; ?>
</div>

<style>
.container { padding:25px; margin-left:240px; max-width:calc(100% - 300px); }
h2 { text-align:center; color:#2d3436; margin-bottom:25px; }
.proposal-card { background:#fff; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1); padding:20px; margin-bottom:25px; }
.proposal-card h3 { color:#2d3436; margin-bottom:10px; }
.form-section { margin-top:10px; }
label { font-weight:600; display:block; margin-bottom:5px; }
input, textarea { width:100%; padding:8px; border:1px solid #ccc; border-radius:6px; }
.form-actions { display:flex; justify-content:space-between; margin-top:15px; }
.btn-approve { background:#27ae60; color:white; border:none; padding:10px 18px; border-radius:6px; cursor:pointer; }
.btn-reject { background:#d63031; color:white; border:none; padding:10px 18px; border-radius:6px; cursor:pointer; }
.btn-approve:hover { background:#1f954f; }
.btn-reject:hover { background:#c0392b; }
.success { color:#27ae60; font-weight:bold; text-align:center; }
.error { color:#d63031; font-weight:bold; text-align:center; }
</style>

<?php include("../includes/footer.php"); ?>
