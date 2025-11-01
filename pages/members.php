<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// ✅ Kiểm tra quyền truy cập
if (!isset($_SESSION['user'])) {
    echo "<div class='container'><p style='color:red;'>🚫 Bạn chưa đăng nhập.</p></div>";
    include("../includes/footer.php");
    exit();
}

$currentUser = $_SESSION['user'];
$role = $currentUser['role_name'] ?? '';
$userUnit = intval($currentUser['unit'] ?? 0);

// ✅ Chỉ cho phép Admin, BCH Trường, BCH Khoa, BCH Chi đoàn
$allowedRoles = ['BCH Trường', 'BCH Khoa', 'BCH Chi đoàn'];
if ($currentUser['isAdmin'] != 1 && !in_array($role, $allowedRoles)) {
    echo "<div class='container'><p style='color:red;'>🚫 Bạn không có quyền truy cập trang này.</p></div>";
    include("../includes/footer.php");
    exit();
}

$message = "";

// ✅ Xử lý yêu cầu xóa đoàn viên
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {
    $deleteId = intval($_POST['delete_id']);

    // Kiểm tra có tồn tại không
    $check = $conn->prepare("SELECT userId FROM users WHERE userId=?");
    $check->bind_param("i", $deleteId);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Xóa các bảng liên quan (user_role, fee_obligation, fee_payment, fee_receipt, fee_cashbook)
        $conn->query("DELETE FROM user_role WHERE user_id=$deleteId");
        $conn->query("DELETE FROM fee_cashbook WHERE recorded_by=$deleteId");
        $conn->query("DELETE FROM fee_payment WHERE payer_id=$deleteId OR collector_id=$deleteId");
        $conn->query("DELETE FROM fee_obligation WHERE user_id=$deleteId");
        $conn->query("DELETE FROM fee_receipt WHERE issued_by=$deleteId");

        // Xóa người dùng
        $del = $conn->prepare("DELETE FROM users WHERE userId=?");
        $del->bind_param("i", $deleteId);
        if ($del->execute()) {
            $message = "<p class='success'>✅ Đã xóa đoàn viên ID #$deleteId thành công!</p>";
        } else {
            $message = "<p class='error'>❌ Lỗi khi xóa đoàn viên. Vui lòng thử lại.</p>";
        }
    } else {
        $message = "<p class='error'>⚠️ Không tìm thấy đoàn viên cần xóa.</p>";
    }
}

// ✅ Lọc dữ liệu theo quyền
if ($currentUser['isAdmin'] == 1 || $role === 'BCH Trường') {
    $unitFilter = "";
} elseif ($role === 'BCH Khoa') {
    $unitFilter = "WHERE ou.id = $userUnit OR ou.parent_id = $userUnit";
} elseif ($role === 'BCH Chi đoàn') {
    $unitFilter = "WHERE ou.id = $userUnit";
} else {
    $unitFilter = "WHERE 1=0";
}

// ✅ Lấy danh sách đoàn viên
$sql = "
    SELECT 
        u.userId, u.userName, u.fullName, u.email, u.identifyCard,
        u.gender, u.birthDate, u.joinDate, u.isAdmin, u.createdAt,
        r.role_name, ou.unit_name, ou.unit_level
    FROM users u
    LEFT JOIN user_role ur ON u.userId = ur.user_id
    LEFT JOIN role r ON ur.role_id = r.id
    LEFT JOIN organization_units ou ON u.unit = ou.id
    $unitFilter
    ORDER BY u.userId ASC
";
$result = $conn->query($sql);
?>

<div class="container">
  <h2>📋 Danh sách đoàn viên</h2>
  <?= $message ?>

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
            <td>
              <?php 
                if ($row['gender'] == 'M') echo 'Nam';
                elseif ($row['gender'] == 'F') echo 'Nữ';
                else echo 'Khác';
              ?>
            </td>
            <td><?= $row['birthDate'] ? date("d/m/Y", strtotime($row['birthDate'])) : '-' ?></td>
            <td><?= $row['joinDate'] ? date("d/m/Y", strtotime($row['joinDate'])) : '-' ?></td>
            <td><?= htmlspecialchars($row['unit_name'] ?? 'Chưa cập nhật') ?></td>
            <td><?= htmlspecialchars($row['role_name'] ?? 'Đoàn viên') ?></td>
            <td>
              <form method="POST" onsubmit="return confirmDelete(this);">
                <input type="hidden" name="delete_id" value="<?= $row['userId'] ?>">
                <button type="submit" class="btn-delete">🗑️</button>
              </form>
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
.container {
  padding: 20px;
  margin-left: 240px;
  max-width: calc(100% - 300px);
}
h2 {
  text-align: center;
  margin-bottom: 15px;
  color: #2d3436;
}
.success { color: #27ae60; font-weight: bold; text-align:center; }
.error { color: #d63031; font-weight: bold; text-align:center; }

/* --- Table --- */
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 15px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
th, td {
  border: 1px solid #ddd;
  padding: 10px;
  text-align: center;
}
th {
  background: #007bff;
  color: white;
}
tr:nth-child(even) { background: #f9f9f9; }
tr:hover { background: #eaf3ff; }

.btn-delete {
  background: #dc3545;
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
}
.btn-delete:hover { background: #c82333; }
</style>

<?php include("../includes/footer.php"); ?>
