<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// Chỉ cho phép Admin truy cập
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
    echo "<div class='container'><p style='color:red;'>Bạn không có quyền truy cập trang này.</p></div>";
    include("../includes/footer.php");
    exit();
}

// Lấy danh sách vai trò & đơn vị
$roles = $conn->query("SELECT id, role_name FROM role ORDER BY id ASC");
$units = $conn->query("SELECT id, unit_name, unit_level FROM organization_units ORDER BY unit_level, unit_name ASC");

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userName = trim($_POST['userName']);
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $identifyCard = trim($_POST['identifyCard']);
    $gender = $_POST['gender'] ?? 'O';
    $birthDate = $_POST['birthDate'] ?? null;
    $joinDate = $_POST['joinDate'] ?? null;
    $unit = intval($_POST['unit']);
    $role_id = intval($_POST['role']);
    $password = $_POST['password'] ?? '123456'; // mật khẩu mặc định
    $isAdmin = isset($_POST['isAdmin']) ? 1 : 0;

    // Kiểm tra trùng email hoặc mã SV/CCCD
    $check = $conn->prepare("SELECT * FROM users WHERE email = ? OR identifyCard = ?");
    $check->bind_param("ss", $email, $identifyCard);
    $check->execute();
    $checkResult = $check->get_result();

    if ($checkResult->num_rows > 0) {
        $message = "<p class='error'>Email hoặc mã SV/CCCD đã tồn tại trong hệ thống!</p>";
    } elseif (empty($userName) || empty($fullName) || empty($email) || empty($role_id) || empty($unit)) {
        $message = "<p class='error'>Vui lòng điền đầy đủ thông tin bắt buộc.</p>";
    } else {
        // Thêm người dùng mới
        $stmt = $conn->prepare("
            INSERT INTO users 
                (userName, fullName, email, identifyCard, gender, birthDate, joinDate, unit, password, isAdmin, createdAt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("sssssssssi", $userName, $fullName, $email, $identifyCard, $gender, $birthDate, $joinDate, $unit, $password, $isAdmin);

        if ($stmt->execute()) {
            $newUserId = $stmt->insert_id;

            // Gán vai trò cho người dùng mới
            $conn->query("INSERT INTO user_role (user_id, role_id, createdAt) VALUES ($newUserId, $role_id, NOW())");

            echo "<script>alert('Tạo tài khoản thành công!'); window.location.href='users.php';</script>";
            exit();
        } else {
            $message = "<p class='error'>Lỗi khi tạo tài khoản. Vui lòng thử lại.</p>";
        }
        $stmt->close();
    }
}
?>

<div class="container">
  <h2>Thêm người dùng mới</h2>
  <?= $message ?>

  <form method="POST" class="form-add">
    <div class="form-group">
      <label>Tên đăng nhập:</label>
      <input type="text" name="userName" required>
    </div>

    <div class="form-group">
      <label>Họ và tên:</label>
      <input type="text" name="fullName" required>
    </div>

    <div class="form-group">
      <label>Email:</label>
      <input type="email" name="email" required>
    </div>

    <div class="form-group">
      <label>MSV/CCCD:</label>
      <input type="text" name="identifyCard" required>
    </div>

    <div class="form-group">
      <label>Giới tính:</label>
      <select name="gender">
        <option value="M">Nam</option>
        <option value="F">Nữ</option>
        <option value="O">Khác</option>
      </select>
    </div>

    <div class="form-group">
      <label>Ngày sinh:</label>
      <input type="date" name="birthDate">
    </div>

    <div class="form-group">
      <label>Ngày vào Đoàn:</label>
      <input type="date" name="joinDate">
    </div>

    <div class="form-group">
      <label>Đơn vị:</label>
      <select name="unit" required>
        <option value="">-- Chọn đơn vị --</option>
        <?php while ($u = $units->fetch_assoc()): ?>
          <option value="<?= $u['id'] ?>">
            <?= htmlspecialchars($u['unit_name']) ?> (<?= $u['unit_level'] ?>)
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Mật khẩu mặc định:</label>
      <input type="text" name="password" value="123456" required>
    </div>

    <div class="form-group">
      <label>Vai trò:</label>
      <select name="role" required>
        <option value="">-- Chọn vai trò --</option>
        <?php while($r = $roles->fetch_assoc()): ?>
          <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-save">Lưu</button>
      <a href="users.php" class="btn-back">Quay lại</a>
    </div>
  </form>
</div>

<style>
.container {
  padding: 20px;
  max-width: 750px;
  margin: 100 auto;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}
h2 {
  text-align: center;
  margin-bottom: 15px;
  color: #2d3436;
}
.form-group {
  margin-bottom: 12px;
}
label {
  font-weight: 600;
  display: block;
  margin-bottom: 4px;
}
input, select {
  width: 100%;
  padding: 8px;
  border-radius: 6px;
  border: 1px solid #ccc;
}
.form-actions {
  margin-top: 20px;
  display: flex;
  justify-content: space-between;
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
  background: #b2bec3;
  color: white;
  padding: 10px 20px;
  text-decoration: none;
  border-radius: 6px;
}
.error { color: #d63031; font-weight: bold; }
.success { color: #27ae60; font-weight: bold; }
</style>

<?php include("../includes/footer.php"); ?>
