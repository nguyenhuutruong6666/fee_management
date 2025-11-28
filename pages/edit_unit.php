<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
  header("Location: login.php");
  exit();
}

$id = $_GET['id'] ?? 0;
$result = $conn->query("SELECT * FROM organization_units WHERE id=$id");
if (!$result || $result->num_rows == 0) {
  echo "<p style='color:red;'>Không tìm thấy đơn vị.</p>";
  exit();
}
$unit = $result->fetch_assoc();
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $unit_name = trim($_POST['unit_name']);
  $leader_id = $_POST['leader_id'] ?: null;
  $deputy_id = $_POST['deputy_id'] ?: null;

  $stmt = $conn->prepare("UPDATE organization_units SET unit_name=?, leader_id=?, deputy_id=? WHERE id=?");
  $stmt->bind_param("siii", $unit_name, $leader_id, $deputy_id, $id);
  if ($stmt->execute()) {
    header("Location: units.php");
    exit();
  } else {
    $message = "<p class='error'>Lỗi khi cập nhật đơn vị.</p>";
  }
}
?>

<div class="container">
  <h2>Sửa thông tin đơn vị</h2>
  <?= $message ?>

  <form method="POST">
    <div class="form-group">
      <label>Tên đơn vị:</label>
      <input type="text" name="unit_name" value="<?= htmlspecialchars($unit['unit_name']) ?>" required>
    </div>

    <!-- <div class="form-group">
      <label>Bí thư:</label>
      <input type="number" name="leader_id" value="<?= $unit['leader_id'] ?? '' ?>" placeholder="Nhập ID người dùng">
    </div>

    <div class="form-group">
      <label>Phó Bí thư:</label>
      <input type="number" name="deputy_id" value="<?= $unit['deputy_id'] ?? '' ?>" placeholder="Nhập ID người dùng">
    </div> -->

    <button type="submit" class="btn-save">Lưu</button>
    <a href="units.php" class="btn-back">Quay lại</a>
  </form>
</div>

<style>
.container { padding:20px; max-width:600px; margin:auto; margin-top: 2%;}
form { background:white; padding:20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
.form-group { margin-bottom:12px; }
input { width:100%; padding:8px; border:1px solid #ccc; border-radius:5px; }
.btn-save { background:#0984e3; color:white; padding:10px 20px; border:none; border-radius:6px; cursor:pointer; }
.btn-back { background:#b2bec3; color:white; text-decoration:none; padding:10px 20px; border-radius:6px; }
.error { color:#d63031; }
</style>

<?php include("../includes/footer.php"); ?>
