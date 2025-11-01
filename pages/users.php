<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// ✅ Chỉ Admin mới được vào
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
    echo "<div class='container'><p style='color:red;'>🚫 Bạn không có quyền truy cập trang này.</p></div>";
    include("../includes/footer.php");
    exit();
}

// ✅ Lấy danh sách người dùng (JOIN role + organization_units)
$sql = "
    SELECT 
        u.userId, u.userName, u.fullName, u.email, u.identifyCard,
        u.gender, u.birthDate, u.joinDate, u.isAdmin, u.createdAt,
        r.role_name, ou.unit_name
    FROM users u
    LEFT JOIN user_role ur ON u.userId = ur.user_id
    LEFT JOIN role r ON ur.role_id = r.id
    LEFT JOIN organization_units ou ON u.unit = ou.id
    ORDER BY u.userId ASC
";
$result = $conn->query($sql);
?>

<div class="container">
  <h2>👥 Quản lý người dùng</h2>

  <div class="actions">
    <a href="add_user.php" class="btn-add">➕ Thêm người dùng</a>
    <a href="import_users.php" class="btn-import">📂 Import danh sách</a>
  </div>

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
        <th>Thao tác</th>
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
            <td><?= htmlspecialchars($row['role_name'] ?? 'Chưa gán') ?></td>
            <td class="actions-cell">
              <a href="edit_user.php?id=<?= $row['userId'] ?>" class="btn-edit" title="Sửa">✏️</a>
              <a href="delete_user.php?id=<?= $row['userId'] ?>" class="btn-delete" 
                 onclick="return confirm('Bạn có chắc muốn xóa người dùng này không?');" title="Xóa">🗑️</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="11" style="text-align:center;">Không có người dùng nào</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<style>
.container {
  padding: 20px;
  margin-right: 1%;
}
.container {
  margin-left: 240px;
  max-width: calc(100% - 300px);
}
h2 {
  text-align: center;
  margin-bottom: 15px;
  color: #2d3436;
}

/* --- Action buttons --- */
.actions {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
  flex-wrap: wrap;
}

.btn-add, .btn-import {
  color: white;
  padding: 8px 15px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 500;
  transition: 0.2s;
}


.btn-add { background: #28a745; }
.btn-add:hover { background: #218838; }

.btn-import { background: #17a2b8; }
.btn-import:hover { background: #138496; }

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

.btn-edit, .btn-delete {
  text-decoration: none;
  padding: 6px 10px;
  border-radius: 5px;
  color: white;
  font-size: 14px;
}

.btn-edit { background: #007bff; }
.btn-delete { background: #dc3545; }
.btn-edit:hover { background: #0056b3; }
.btn-delete:hover { background: #c82333; }

.actions-cell {
  display: flex;
  justify-content: center;
  gap: 6px;
}
</style>

<?php include("../includes/footer.php"); ?>
