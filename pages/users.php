<?php
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

$result = $conn->query("SELECT userId, userName, gender, isAdmin FROM users");
?>
<div class="container">
  <h2>👥 Quản lý người dùng</h2>
  <table class="table">
    <tr><th>ID</th><th>Tên người dùng</th><th>Giới tính</th><th>Vai trò</th></tr>
    <?php while($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= $row['userId'] ?></td>
        <td><?= htmlspecialchars($row['userName']) ?></td>
        <td><?= $row['gender'] ?></td>
        <td><?= $row['isAdmin'] ? 'Admin' : 'Người dùng' ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
</div>
<?php include("../includes/footer.php"); ?>
