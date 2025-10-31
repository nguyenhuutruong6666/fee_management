<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// Lấy danh sách người dùng
$sql = "SELECT userId, userName, fullName, email, role, unit, isAdmin, createdAt FROM users ORDER BY userId ASC";
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
        <th>Đơn vị</th>
        <th>Vai trò</th>
        <th>Loại tài khoản</th>
        <th>Ngày tạo</th>
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
            <td><?= htmlspecialchars($row['unit']) ?></td>
            <td><?= htmlspecialchars($row['role']) ?></td>
            <td><?= $row['isAdmin'] ? '🛡️ Admin' : '👤 Người dùng' ?></td>
            <td><?= date("d/m/Y", strtotime($row['createdAt'])) ?></td>
            <td>
              <a href="edit_user.php?id=<?= $row['userId'] ?>" class="btn-edit">✏️ Sửa</a>
              <a href="delete_user.php?id=<?= $row['userId'] ?>" class="btn-delete" onclick="return confirm('Bạn có chắc muốn xóa người dùng này không?');">🗑️ Xóa</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="9" style="text-align:center;">Không có người dùng nào</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include("../includes/footer.php"); ?>
