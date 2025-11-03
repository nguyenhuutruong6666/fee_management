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

// Lấy thông tin người dùng kèm vai trò & đơn vị
$query = "
    SELECT u.*, r.id AS role_id, r.role_name, ou.unit_name
    FROM users u
    LEFT JOIN user_role ur ON u.userId = ur.user_id
    LEFT JOIN role r ON ur.role_id = r.id
    LEFT JOIN organization_units ou ON u.unit = ou.id
    WHERE u.userId = $userId
";
$result = $conn->query($query);
if (!$result || $result->num_rows === 0) {
    echo "<div class='container'><p>❌ Không tìm thấy tài khoản.</p></div>";
    include("../includes/footer.php");
    exit();
}
$user = $result->fetch_assoc();

$message = "";

// Xử lý cập nhật khi người dùng nhấn “Lưu”
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $birthDate = $_POST['birthDate'] ?? null;
    $joinDate = $_POST['joinDate'] ?? null;
    $gender = $_POST['gender'] ?? 'O';
    $role_id = $currentUser['isAdmin'] ? intval($_POST['role_id'] ?? $user['role_id']) : $user['role_id'];

    // Nếu là admin mới được cập nhật đơn vị
    $unit = $currentUser['isAdmin'] ? intval($_POST['unit']) : $user['unit'];

    if (empty($fullName) || empty($email)) {
        $message = "<p class='error'>⚠️ Vui lòng nhập đầy đủ thông tin.</p>";
    } else {
        // Cập nhật bảng users
        $stmt = $conn->prepare("
            UPDATE users 
            SET fullName=?, email=?, unit=?, birthDate=?, joinDate=?, gender=? 
            WHERE userId=?
        ");
        $stmt->bind_param("ssssssi", $fullName, $email, $unit, $birthDate, $joinDate, $gender, $userId);

        if ($stmt->execute()) {
            // Nếu là admin → cập nhật vai trò
            if ($currentUser['isAdmin']) {
                $checkRole = $conn->query("SELECT * FROM user_role WHERE user_id=$userId");
                if ($checkRole->num_rows > 0) {
                    $conn->query("UPDATE user_role SET role_id=$role_id WHERE user_id=$userId");
                } else {
                    $conn->query("INSERT INTO user_role (user_id, role_id) VALUES ($userId, $role_id)");
                }
            }

            // Cập nhật session nếu sửa chính mình
            if ($currentUser['userId'] === $userId) {
                $_SESSION['user']['fullName'] = $fullName;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['unit'] = $unit;
            }

            $stmt->close();
            header("Location: users.php");
            exit();
        } else {
            $message = "<p class='error'>❌ Lỗi khi cập nhật dữ liệu, vui lòng thử lại.</p>";
        }
    }
}

//Lấy danh sách vai trò
$roles = $conn->query("SELECT id, role_name FROM role ORDER BY id ASC");

//Lấy danh sách đơn vị từ organization_units
$units = $conn->query("SELECT id, unit_name, unit_level FROM organization_units ORDER BY unit_level, unit_name ASC");
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
      <label>MSV/CCCD:</label>
      <input type="text" value="<?= htmlspecialchars($user['identifyCard']) ?>" disabled>
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

    <?php if ($currentUser['isAdmin']): ?>
      <div class="form-group">
        <label>Đơn vị:</label>
        <select name="unit" required>
          <option value="">-- Chọn đơn vị --</option>
          <?php while ($u = $units->fetch_assoc()): ?>
            <option value="<?= $u['id'] ?>" 
              <?= ($user['unit'] == $u['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($u['unit_name']) ?> (<?= $u['unit_level'] ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </div>
    <?php else: ?>
      <div class="form-group">
        <label>Đơn vị:</label>
        <input type="text" value="<?= htmlspecialchars($user['unit_name']) ?>" disabled>
      </div>
    <?php endif; ?>

    <?php if ($currentUser['isAdmin']): ?>
      <div class="form-group">
        <label>Vai trò:</label>
        <select name="role_id" required>
          <?php while ($r = $roles->fetch_assoc()): ?>
            <option value="<?= $r['id'] ?>" <?= ($user['role_id'] == $r['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($r['role_name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
    <?php else: ?>
      <div class="form-group">
        <label>Vai trò:</label>
        <input type="text" value="<?= htmlspecialchars($user['role_name']) ?>" disabled>
      </div>
    <?php endif; ?>

    <div class="form-actions">
      <button type="submit" class="btn-save">💾 Lưu thay đổi</button>
      <a href="users.php" class="btn-back">⬅️ Quay lại</a>
    </div>
  </form>
</div>

<style>
.container {
  margin-left: 240px;
  max-width: calc(100% - 300px);
}
input, select {
  width: 100%;
  padding: 8px;
  border-radius: 6px;
  border: 1px solid #ccc;
}
.form-group { margin-bottom: 15px; }
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
