<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

//KIỂM TRA PHÂN QUYỀN
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['user'];
$user_id = $user['userId'];
$user_role = $user['role_name'] ?? '';
$unit_type = $user['unit_type'] ?? '';
$unit_id = $user['unit_id'] ?? 0;

if (!in_array($user_role, ['BCH Chi đoàn', 'BCH Khoa'])) {
  echo "<div class='container'><p style='color:red;'>Bạn không có quyền truy cập chức năng này.</p></div>";
  include("../includes/footer.php");
  exit();
}

$message = "";

//XỬ LÝ LƯU ĐỀ XUẤT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['title'])) {
  $title = trim($_POST['title']);
  $content = trim($_POST['content']);
  $estimated_budget = floatval($_POST['estimated_budget']);
  $expected_date = $_POST['expected_date'];

  if (empty($title) || empty($estimated_budget) || empty($expected_date)) {
    $message = "<p class='error'>Vui lòng nhập đầy đủ thông tin bắt buộc.</p>";
  } else {
    // Kiểm tra trùng tên trong cùng đơn vị
    $check = $conn->prepare("SELECT id FROM activity_proposal WHERE title = ? AND unit_id = ?");
    $check->bind_param("si", $title, $unit_id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;

    if ($exists) {
      $message = "<p class='error'>Tên hoạt động này đã tồn tại. Vui lòng đổi tên khác.</p>";
    } else {
      $stmt = $conn->prepare("
        INSERT INTO activity_proposal 
        (title, content, estimated_budget, expected_date, proposer_id, unit_type, unit_id, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Chờ phê duyệt', NOW(), NOW())
      ");
      $stmt->bind_param("ssdsiis", $title, $content, $estimated_budget, $expected_date, $user_id, $unit_type, $unit_id);

      if ($stmt->execute()) {
        $proposal_id = $stmt->insert_id;

        // Ghi log hành động
        $log = $conn->prepare("
          INSERT INTO activity_approval_log (proposal_id, action, performed_by, performed_at, note)
          VALUES (?, 'Tạo đề xuất', ?, NOW(), 'Đề xuất hoạt động mới được tạo')
        ");
        $log->bind_param("ii", $proposal_id, $user_id);
        $log->execute();

        $message = "<p class='success'>Đề xuất hoạt động đã được lưu và chờ phê duyệt.</p>";
      } else {
        $message = "<p class='error'>Lỗi khi lưu dữ liệu. Vui lòng thử lại.</p>";
      }
    }
  }
}
?>

<div class="container">
  <h2>Đề xuất hoạt động phong trào</h2>
  <?= $message ?>

  <form method="POST" class="proposal-form">
    <div class="form-group">
      <label>Tên hoạt động <span class="required">*</span></label>
      <input type="text" name="title" placeholder="VD: Xuân tình nguyện 2026" required>
    </div>

    <div class="form-group">
      <label>Nội dung chi tiết</label>
      <textarea name="content" rows="5" placeholder="Mục tiêu, hình thức tổ chức, đối tượng tham gia..."></textarea>
    </div>

    <div class="form-group">
      <label>Dự toán kinh phí (VNĐ) <span class="required">*</span></label>
      <input type="number" name="estimated_budget" min="0" step="1000" placeholder="VD: 5000000" required>
    </div>

    <div class="form-group">
      <label>Ngày dự kiến tổ chức <span class="required">*</span></label>
      <input type="date" name="expected_date" required>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-save">Lưu đề xuất</button>
      <a href="dashboard.php" class="btn-back">Quay lại</a>
    </div>
  </form>
</div>

<style>
.container { padding:25px; margin-left:240px; max-width:calc(100% - 300px); }
h2 { text-align:center; margin-bottom:25px; color:#2d3436; }
.proposal-form { background:#fff; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1); padding:20px; }
.form-group { margin-bottom:15px; }
label { font-weight:600; display:block; margin-bottom:5px; }
input[type="text"], input[type="number"], input[type="date"], textarea {
  width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-size:15px;
}
textarea { resize:vertical; }
.form-actions { display:flex; justify-content:space-between; align-items:center; margin-top:20px; }
.btn-save { background:#27ae60; color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; }
.btn-save:hover { background:#219150; }
.btn-back { background:#b2bec3; color:white; text-decoration:none; padding:10px 20px; border-radius:6px; }
.required { color:red; }
.success { color:#27ae60; font-weight:bold; text-align:center; }
.error { color:#d63031; font-weight:bold; text-align:center; }
</style>

<?php include("../includes/footer.php"); ?>
