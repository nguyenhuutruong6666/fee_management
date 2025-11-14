<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// Chỉ admin mới có quyền
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
  echo "<div class='container'><p style='color:red;'>Bạn không có quyền truy cập trang này.</p></div>";
  include("../includes/footer.php");
  exit();
}

// Lấy tham số từ URL
$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : NULL;
$next_level = $_GET['next'] ?? 'Truong'; // Mặc định là Trường
$message = "";

// Khi submit form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $unit_name = trim($_POST['unit_name']);
  $unit_level = $_POST['unit_level'];
  $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;

  if (empty($unit_name)) {
    $message = "<p class='error'>Vui lòng nhập tên đơn vị.</p>";
  } else {
    //Kiểm tra xem tên đơn vị đã tồn tại chưa (không phân biệt hoa/thường)
    $check = $conn->prepare("SELECT id FROM organization_units WHERE LOWER(unit_name) = LOWER(?)");
    $check->bind_param("s", $unit_name);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
      $message = "<p class='error'>Tên đơn vị <b>'" . htmlspecialchars($unit_name) . "'</b> đã tồn tại. Vui lòng chọn tên khác.</p>";
    } else {
      // Thêm mới
      $stmt = $conn->prepare("INSERT INTO organization_units (unit_name, unit_level, parent_id) VALUES (?, ?, ?)");
      $stmt->bind_param("ssi", $unit_name, $unit_level, $parent_id);

      if ($stmt->execute()) {
        header("Location: units.php");
        exit();
      } else {
        $message = "<p class='error'>Lỗi khi thêm đơn vị mới: " . $conn->error . "</p>";
      }
      $stmt->close();
    }
    $check->close();
  }
}
?>

<div class="container">
  <h2>Thêm đơn vị mới</h2>
  <?= $message ?>

  <form method="POST" class="form-add">
    <div class="form-group">
      <label>Tên đơn vị:</label>
      <input type="text" name="unit_name" required>
    </div>

    <div class="form-group">
      <label>Loại đơn vị:</label>
      <select name="unit_level" required>
        <option value="Truong" <?= ($next_level === 'Truong') ? 'selected' : '' ?>>Trường</option>
        <option value="Khoa" <?= ($next_level === 'Khoa') ? 'selected' : '' ?>>Khoa</option>
        <option value="ChiDoan" <?= ($next_level === 'ChiDoan') ? 'selected' : '' ?>>Chi đoàn</option>
      </select>
    </div>

    <?php if ($parent_id): ?>
      <input type="hidden" name="parent_id" value="<?= $parent_id ?>">
      <p><i>Thuộc đơn vị cha ID: <?= $parent_id ?></i></p>
    <?php endif; ?>

    <div class="form-actions">
      <button type="submit" class="btn-save">Lưu</button>
      <a href="units.php" class="btn-back">Quay lại</a>
    </div>
  </form>
</div>

<style>
.container {
  max-width: 600px;
  margin: 40px auto;
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}
.form-group { margin-bottom: 15px; }
label { display: block; margin-bottom: 6px; font-weight: bold; }
input, select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 6px;
}
.btn-save {
  background: #0984e3;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
.btn-save:hover { background: #74b9ff; }
.btn-back {
  background: #636e72;
  color: white;
  padding: 10px 20px;
  text-decoration: none;
  border-radius: 6px;
}
.error {
  background: #ffecec;
  color: #d63031;
  font-weight: bold;
  padding: 10px;
  border-radius: 6px;
}
</style>

<?php include("../includes/footer.php"); ?>
