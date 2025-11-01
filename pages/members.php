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

// ✅ Lấy danh sách ID đơn vị mà người dùng được phép xem
$allowedUnits = [];

if ($currentUser['isAdmin'] == 1 || $role === 'BCH Trường') {
    // Admin & BCH Trường => thấy tất cả
    $unitFilter = "";
} elseif ($role === 'BCH Khoa') {
    // BCH Khoa => thấy khoa mình, chi đoàn và đoàn viên trực thuộc
    $unitFilter = "WHERE ou.id = $userUnit OR ou.parent_id = $userUnit";
} elseif ($role === 'BCH Chi đoàn') {
    // BCH Chi đoàn => chỉ thấy đoàn viên trong chi đoàn
    $unitFilter = "WHERE ou.id = $userUnit";
} else {
    $unitFilter = "WHERE 1=0"; // Không có quyền
}

// ✅ Lấy danh sách user
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
  <h2>📋 Quản lý đoàn viên</h2>

  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Tên đăng nhập</th>
        <th>Họ và tên</th>
        <th>Email</th>
        <th>MSV/CCCD</th>
        <th>Giới tính</th>
        <th>Năm sinh</th>
        <th>Ngày vào Đoàn</th>
        <th>Đơn vị</th>
        <th>Vai trò</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['userId'] ?></td>
            <td><?= htmlspecialchars($row['userName']) ?></td>
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
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="10" style="text-align:center;">Không có đoàn viên nào.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

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
</style>

<?php include("../includes/footer.php"); ?>
