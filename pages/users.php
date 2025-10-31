<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// ✅ Kiểm tra quyền truy cập (chỉ Admin mới được vào)
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
    echo "<div class='container'><p style='color:red;'>🚫 Bạn không có quyền truy cập trang này.</p></div>";
    include("../includes/footer.php");
    exit();
}

// ✅ Lấy danh sách người dùng
$sql = "SELECT userId, userName, fullName, email, role, unit, gender, birthDate, joinDate, isAdmin, createdAt 
        FROM users ORDER BY userId ASC";
$result = $conn->query($sql);
?>

<div class="container">
  <h2>👥 Quản lý người dùng</h2>

  <div class="actions">
    <a href="add_user.php" class="btn-add">➕ Thêm người dùng</a>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Tên đăng nhập</th>
        <th>Họ và tên</th>
        <th>Email</th>
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
        <?php while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['userId'] ?></td>
            <td><?= htmlspecialchars($row['userName']) ?></td>
            <td><?= htmlspecialchars($row['fullName']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td>
              <?php 
                if ($row['gender'] == 'M') echo 'Nam';
                elseif ($row['gender'] == 'F') echo 'Nữ';
                else echo 'Khác';
              ?>
            </td>
            <td><?= $row['birthDate'] ? date("d/m/Y", strtotime($row['birthDate'])) : '-' ?></td>
            <td><?= $row['joinDate'] ? date("d/m/Y", strtotime($row['joinDate'])) : '-' ?></td>
            <td><?= htmlspecialchars($row['unit']) ?></td>
            <td><?= htmlspecialchars($row['role']) ?></td>
            <td class="actions-cell">
              <a href="edit_user.php?id=<?= $row['userId'] ?>" class="btn-edit">✏️</a>
              <a href="delete_user.php?id=<?= $row['userId'] ?>" class="btn-delete" 
                 onclick="return confirm('Bạn có chắc muốn xóa người dùng này không?');">🗑️</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="12" style="text-align:center;">Không có người dùng nào</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<style>
  .actions {
    text-align: right;
    margin-bottom: 10px;
  }

  .btn-add {
    background: #28a745;
    color: white;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
  }

  .btn-add:hover {
    background: #218838;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15px;
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

  tr:nth-child(even) {
    background: #f9f9f9;
  }

  tr:hover {
    background: #f1f9ff;
  }

  .btn-edit, .btn-delete {
    text-decoration: none;
    padding: 6px 10px;
    border-radius: 5px;
    margin: 0 2px;
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
