<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['user'];
$userId = isset($_GET['id']) ? intval($_GET['id']) : $currentUser['userId'];

// Phân quyền: chỉ Admin được sửa người khác
// if (!$currentUser['isAdmin'] && $currentUser['userId'] !== $userId) {
//     echo "<div class='container'><p style='color:red;'>❌ Bạn không có quyền chỉnh sửa tài khoản này.</p></div>";
//     include("../includes/footer.php");
//     exit();
// }

// Lấy thông tin user cần sửa
$result = $conn->query("SELECT * FROM users WHERE userId = $userId");
if (!$result || $result->num_rows === 0) {
    echo "<div class='container'><p>❌ Không tìm thấy tài khoản.</p></div>";
    include("../includes/footer.php");
    exit();
}
$user = $result->fetch_assoc();

// Xử lý cập nhật
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $unit = trim($_POST['unit']);
    $birthDate = $_POST['birthDate'] ?? null;
    $joinDate = $_POST['joinDate'] ?? null;
    $gender = $_POST['gender'] ?? 'O';
    $role = $currentUser['isAdmin'] ? trim($_POST['role']) : $user['role'];

    if (empty($fullName) || empty($email) || empty($unit)) {
        $message = "<p class='error'>⚠️ Vui lòng nhập đầy đủ thông tin.</p>";
    } else {
        $stmt = $conn->prepare("UPDATE users 
            SET fullName=?, email=?, unit=?, birthDate=?, joinDate=?, gender=?, role=? 
            WHERE userId=?");
        $stmt->bind_param("sssssssi", $fullName, $email, $unit, $birthDate, $joinDate, $gender, $role, $userId);
        if ($stmt->execute()) {
            $message = "<p class='success'>✅ Cập nhật thành công!</p>";

            // Nếu là chính mình thì cập nhật session
            if ($currentUser['userId'] === $userId) {
                $_SESSION['user']['fullName'] = $fullName;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['unit'] = $unit;
                $_SESSION['user']['role'] = $role;
            }
        } else {
            $message = "<p class='error'>❌ Lỗi khi cập nhật dữ liệu, vui lòng thử lại.</p>";
        }
        $stmt->close();
    }
}
?>

<div class="container">
  <h2>✏️ Chỉnh sửa thông tin tài khoản</h2>
  <?= $message ?>

  <form method="POST" class="form-edit">
    <div class="form-group">
      <label>Tên đăng nhập:</label>
      <input type="text" value="<?= htmlspecialchars($user['userName']) ?>" disabled>
    </div>

    <div class="form-group">
      <label>Họ và tên:</label>
      <input type="text" name="fullName" value="<?= htmlspecialchars($user['fullName']) ?>" required>
    </div>

    <div class="form-group">
      <label>Email:</label>
      <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
    </div>

    <div class="form-group">
      <label>Năm sinh:</label>
      <input type="date" name="birthDate" value="<?= htmlspecialchars($user['birthDate'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label>Giới tính:</label>
      <select name="gender">
        <option value="M" <?= ($user['gender'] == 'M') ? 'selected' : '' ?>>Nam</option>
        <option value="F" <?= ($user['gender'] == 'F') ? 'selected' : '' ?>>Nữ</option>
        <option value="O" <?= ($user['gender'] == 'O') ? 'selected' : '' ?>>Khác</option>
      </select>
    </div>

    <div class="form-group">
      <label>Ngày vào Đoàn:</label>
      <input type="date" name="joinDate" value="<?= htmlspecialchars($user['joinDate'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label>Đơn vị:</label>
      <input type="text" name="unit" value="<?= htmlspecialchars($user['unit']) ?>" required>
    </div>

    <?php if ($currentUser['isAdmin']): ?>
      <div class="form-group">
        <label>Vai trò:</label>
        <select name="role" required>
          <option value="Admin" <?= $user['role'] == 'Admin' ? 'selected' : '' ?>>Quản trị viên</option>
          <option value="BCH_Truong" <?= $user['role'] == 'BCH_Truong' ? 'selected' : '' ?>>BCH Trường</option>
          <option value="BCH_Khoa" <?= $user['role'] == 'BCH_Khoa' ? 'selected' : '' ?>>BCH Khoa</option>
          <option value="BCH_ChiDoan" <?= $user['role'] == 'BCH_ChiDoan' ? 'selected' : '' ?>>BCH Chi đoàn</option>
          <option value="DoanVien" <?= $user['role'] == 'DoanVien' ? 'selected' : '' ?>>Đoàn viên</option>
        </select>
      </div>
    <?php else: ?>
      <div class="form-group">
        <label>Vai trò:</label>
        <input type="text" value="<?= htmlspecialchars($user['role']) ?>" disabled>
      </div>
    <?php endif; ?>

    <div class="form-actions">
      <button type="submit" class="btn-save">💾 Lưu thay đổi</button>
      <a href="profile.php" class="btn-back">⬅️ Quay lại</a>
    </div>
  </form>
</div>
<style>
  
  input, select {
    width: 100%;
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #ccc;
  }
  .form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
  }
  .btn-save {
    background: #0984e3;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
  }
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
