<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// Chỉ Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
  header("Location: login.php");
  exit();
}

$parent_id = $_GET['parent_id'] ?? null;
$level = $_GET['level'] ?? null;
$message = "";

// Xác định cấp mới dựa theo cấp cha
$next_level = 'Truong';
if ($level == 'Truong') $next_level = 'Khoa';
elseif ($level == 'Khoa') $next_level = 'ChiDoan';
elseif ($level == 'ChiDoan') $next_level = 'Lop';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $unit_name = trim($_POST['unit_name']);
  $unit_level = $_POST['unit_level'];
  $parent_id = $_POST['parent_id'] ?: "NULL";

  if (empty($unit_name)) {
    $message = "<p class='error'>⚠️ Tên đơn vị không được để trống.</p>";
  } else {
    $stmt = $conn->prepare("INSERT INTO organization_units (unit_name, unit_level, parent_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $unit_name, $unit_level, $parent_id);
    if ($stmt->execute()) {
      header("Location: units.php");
      exit();
    } else {
      $message = "<p class='error'>❌ Lỗi khi thêm đơn vị.</p>";
    }
    $stmt->close();
  }
}
?>

<div class="container">
  <h2>➕ Thêm đơn vị mới</h2>
  <?= $message ?>

  <form method="POST">
    <div class="form-group">
      <label>Tên đơn vị:</label>
      <input type="text" name="unit_name" required>
    </div>

    <div class="form-group">
      <label>Cấp tổ chức:</label>
      <select name="unit_level" required>
        <?php if ($parent_id == null): ?>
          <option value="Truong">Trường</option>
        <?php else: ?>
          <option value="<?= $next_level ?>"><?= $next_level ?></option>
        <?php endif; ?>
      </select>
    </div>

    <?php if ($parent_id): ?>
      <input type="hidden" name="parent_id" value="<?= intval($parent_id) ?>">
    <?php endif; ?>

    <button type="submit" class="btn-save">💾 Lưu</button>
    <a href="units.php" class="btn-back">⬅️ Quay lại</a>
  </form>
</div>

<style>
.container { padding:20px; max-width:600px; margin:auto; }
form { background:white; padding:20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
.form-group { margin-bottom:12px; }
label { font-weight:bold; }
input, select { width:100%; padding:8px; border:1px solid #ccc; border-radius:5px; }
.btn-save { background:#0984e3; color:white; padding:10px 20px; border:none; border-radius:6px; cursor:pointer; }
.btn-back { background:#b2bec3; color:white; text-decoration:none; padding:10px 20px; border-radius:6px; }
.error { color:#d63031; }
</style>

<?php include("../includes/footer.php"); ?>
