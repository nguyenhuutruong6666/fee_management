<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

//KIỂM TRA QUYỀN TRUY CẬP
if (!isset($_SESSION['user'])) {
    echo "<div class='container'><p style='color:red;'>🚫 Bạn chưa đăng nhập.</p></div>";
    include("../includes/footer.php");
    exit();
}

$currentUser = $_SESSION['user'];
$role = $currentUser['role_name'] ?? '';
$userId = intval($currentUser['userId']);
$userUnit = intval($currentUser['unit'] ?? 0);
$allowedRoles = ['BCH Trường', 'BCH Khoa', 'BCH Chi đoàn'];

if ($currentUser['isAdmin'] != 1 && !in_array($role, $allowedRoles)) {
    echo "<div class='container'><p style='color:red;'>🚫 Bạn không có quyền truy cập trang này.</p></div>";
    include("../includes/footer.php");
    exit();
}

$message = "";

//XỬ LÝ XÓA ĐOÀN VIÊN
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {
  $deleteId = intval($_POST['delete_id']);

  //Ngăn người dùng tự xóa chính mình
  if ($deleteId === $userId) {
      $message = "<p class='error'>⚠️ Bạn không thể tự xóa tài khoản của chính mình.</p>";
  } else {
      // Lấy vai trò của người bị xóa
      $stmt = $conn->prepare("
          SELECT r.role_name 
          FROM user_role ur 
          JOIN role r ON ur.role_id = r.id 
          WHERE ur.user_id = ?
      ");
      $stmt->bind_param("i", $deleteId);
      $stmt->execute();
      $res = $stmt->get_result();
      $targetRole = $res->num_rows > 0 ? $res->fetch_assoc()['role_name'] : 'Đoàn viên';

      //Ngăn xóa người có cùng cấp vai trò (trừ Admin)
      if ($currentUser['isAdmin'] != 1 && $role === $targetRole) {
          $message = "<p class='error'>🚫 Bạn không thể xóa người có cùng vai trò ($targetRole) với mình.</p>";
      } else {
          // Kiểm tra tồn tại người bị xóa
          $check = $conn->prepare("SELECT userId FROM users WHERE userId=?");
          $check->bind_param("i", $deleteId);
          $check->execute();
          $result = $check->get_result();

          if ($result->num_rows > 0) {
              // Thực hiện xóa dữ liệu liên quan
              $conn->query("DELETE FROM user_role WHERE user_id=$deleteId");
              $conn->query("DELETE FROM fee_cashbook WHERE recorded_by=$deleteId");
              $conn->query("DELETE FROM fee_payment WHERE payer_id=$deleteId OR collector_id=$deleteId");
              $conn->query("DELETE FROM fee_obligation WHERE user_id=$deleteId");
              $conn->query("DELETE FROM fee_receipt WHERE issued_by=$deleteId");
              $conn->query("DELETE FROM users WHERE userId=$deleteId");

              $message = "<p class='success'>✅ Đã xóa đoàn viên ID #$deleteId thành công!</p>";
          } else {
              $message = "<p class='error'>⚠️ Không tìm thấy đoàn viên cần xóa.</p>";
          }
      }
  }
}

//LẤY DANH SÁCH VAI TRÒ THEO QUYỀN
if ($currentUser['isAdmin'] == 1 || $role === 'BCH Trường') {
    $roles = $conn->query("SELECT DISTINCT role_name FROM role ORDER BY role_name ASC");
} elseif ($role === 'BCH Khoa') {
    $roles = $conn->query("SELECT DISTINCT role_name FROM role WHERE role_name IN ('BCH Khoa','BCH Chi đoàn','Đoàn viên') ORDER BY role_name ASC");
} elseif ($role === 'BCH Chi đoàn') {
    $roles = $conn->query("SELECT DISTINCT role_name FROM role WHERE role_name IN ('BCH Chi đoàn','Đoàn viên') ORDER BY role_name ASC");
}

//LẤY DANH SÁCH ĐƠN VỊ THEO QUYỀN
if ($currentUser['isAdmin'] == 1 || $role === 'BCH Trường') {
    $units = $conn->query("SELECT id, unit_name FROM organization_units ORDER BY unit_name ASC");
} elseif ($role === 'BCH Khoa') {
    $units = $conn->query("SELECT id, unit_name FROM organization_units WHERE id=$userUnit OR parent_id=$userUnit ORDER BY unit_name ASC");
} elseif ($role === 'BCH Chi đoàn') {
    $units = $conn->query("SELECT id, unit_name FROM organization_units WHERE id=$userUnit ORDER BY unit_name ASC");
}

//LỌC THEO FORM
$filterRole = $_GET['filter_role'] ?? '';
$filterUnit = $_GET['filter_unit'] ?? '';
$conditions = [];

if ($filterRole !== '') $conditions[] = "r.role_name = '" . $conn->real_escape_string($filterRole) . "'";
if ($filterUnit !== '') $conditions[] = "ou.id = " . intval($filterUnit);

$whereClause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

//LỌC THEO QUYỀN TRUY CẬP
if ($currentUser['isAdmin'] == 1 || $role === 'BCH Trường') {
    // full quyền
} elseif ($role === 'BCH Khoa') {
    $whereClause .= ($whereClause ? " AND " : "WHERE ") . "(ou.id = $userUnit OR ou.parent_id = $userUnit)";
} elseif ($role === 'BCH Chi đoàn') {
    $whereClause .= ($whereClause ? " AND " : "WHERE ") . "ou.id = $userUnit";
}

//LẤY DANH SÁCH NGƯỜI DÙNG
$sql = "
    SELECT 
        u.userId, u.userName, u.fullName, u.email, u.identifyCard,
        u.gender, u.birthDate, u.joinDate, u.isAdmin, u.createdAt,
        r.role_name, ou.unit_name
    FROM users u
    LEFT JOIN user_role ur ON u.userId = ur.user_id
    LEFT JOIN role r ON ur.role_id = r.id
    LEFT JOIN organization_units ou ON u.unit = ou.id
    $whereClause
    ORDER BY u.userId ASC
";
$result = $conn->query($sql);
?>

<div class="container">
  <h2>📋 Danh sách đoàn viên</h2>
  <?= $message ?>

  <!-- BỘ LỌC -->
  <form method="GET" class="filter-form">
    <h3>Vai trò:</h3>
    <select name="filter_role">
      <option value="">-- Chọn vai trò --</option>
      <?php while ($r = $roles->fetch_assoc()): ?>
        <option value="<?= htmlspecialchars($r['role_name']) ?>" <?= $filterRole == $r['role_name'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($r['role_name']) ?>
        </option>
      <?php endwhile; ?>
    </select>
    <h3>Đơn vị:</h3>
    <select name="filter_unit">
      <option value="">-- Chọn đơn vị --</option>
      <?php while ($u = $units->fetch_assoc()): ?>
        <option value="<?= $u['id'] ?>" <?= $filterUnit == $u['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($u['unit_name']) ?>
        </option>
      <?php endwhile; ?>
    </select>

    <button type="submit" class="btn-filter">✅ Xác nhận</button>
    <a href="?" class="btn-clear">🧹 Xóa lọc</a>
  </form>

  <!-- BẢNG DANH SÁCH -->
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Họ và tên</th>
        <th>Email</th>
        <th>MSV/CCCD</th>
        <th>Giới tính</th>
        <th>Năm sinh</th>
        <th>Ngày vào Đoàn</th>
        <th>Đơn vị</th>
        <th>Vai trò</th>
        <th>Thao tác</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['userId'] ?></td>
            <td><?= htmlspecialchars($row['fullName']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['identifyCard'] ?? '-') ?></td>
            <td><?= $row['gender'] == 'M' ? 'Nam' : ($row['gender'] == 'F' ? 'Nữ' : 'Khác') ?></td>
            <td><?= $row['birthDate'] ? date("d/m/Y", strtotime($row['birthDate'])) : '-' ?></td>
            <td><?= $row['joinDate'] ? date("d/m/Y", strtotime($row['joinDate'])) : '-' ?></td>
            <td><?= htmlspecialchars($row['unit_name'] ?? 'Chưa cập nhật') ?></td>
            <td><?= htmlspecialchars($row['role_name'] ?? 'Đoàn viên') ?></td>
            <td>
              <?php if ($row['userId'] != $userId): // ✅ Ẩn nút xóa chính mình ?>
              <form method="POST" onsubmit="return confirmDelete(this);">
                <input type="hidden" name="delete_id" value="<?= $row['userId'] ?>">
                <button type="submit" class="btn-delete">🗑️</button>
              </form>
              <?php else: ?>
                <span style="color:#999;">--</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="10" style="text-align:center;">Không có đoàn viên nào.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
function confirmDelete(form) {
  return confirm('⚠️ Bạn có chắc muốn xóa đoàn viên này không? Hành động này không thể hoàn tác!');
}
</script>

<style>
.container { padding: 20px; margin-left: 240px; max-width: calc(100% - 300px); }
h2 { text-align: center; margin-bottom: 15px; color: #2d3436; }
.success, .error { font-weight: bold; text-align:center; }
.success { color: #27ae60; }
.error { color: #d63031; }

.filter-form { display: flex; justify-content: center; align-items: center; gap: 10px; margin-bottom: 15px; }
.filter-form select { padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
.btn-filter, .btn-clear {
  background: #007bff; color: white; border: none; padding: 8px 12px;
  border-radius: 6px; cursor: pointer; text-decoration: none;
}
.btn-filter:hover { background: #0056b3; }
.btn-clear { background: #6c757d; }
.btn-clear:hover { background: #5a6268; }

table { width: 100%; border-collapse: collapse; font-size: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
th { background: #007bff; color: white; }
tr:nth-child(even) { background: #f9f9f9; }
tr:hover { background: #eaf3ff; }
.btn-delete {
  background: #dc3545; color: white; border: none;
  padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 14px;
}
.btn-delete:hover { background: #c82333; }
</style>

<?php include("../includes/footer.php"); ?>
