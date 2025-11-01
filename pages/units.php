<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// ✅ Chỉ Admin được phép vào
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
  echo "<div class='container'><p style='color:red;'>🚫 Bạn không có quyền truy cập trang này.</p></div>";
  include("../includes/footer.php");
  exit();
}

// ✅ Hàm đệ quy hiển thị cây tổ chức
function renderTree($conn, $parent_id = NULL, $level = 0) {
  $sql = $parent_id ? 
    "SELECT * FROM organization_units WHERE parent_id=$parent_id ORDER BY unit_name" :
    "SELECT * FROM organization_units WHERE parent_id IS NULL ORDER BY unit_name";
  $result = $conn->query($sql);

  if ($result && $result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
      echo "<li>";
      echo str_repeat("&nbsp;&nbsp;&nbsp;", $level);
      echo "📁 <b>" . htmlspecialchars($row['unit_name']) . "</b> ";
      echo "<span style='color:gray;'>(" . $row['unit_level'] . ")</span>";
      echo " <a href='add_unit.php?parent_id={$row['id']}&level={$row['unit_level']}' class='btn-small'>➕</a>";
      echo " <a href='edit_unit.php?id={$row['id']}' class='btn-small blue'>✏️</a>";
      echo " <a href='delete_unit.php?id={$row['id']}' onclick=\"return confirm('Xóa đơn vị này và các đơn vị con?');\" class='btn-small red'>🗑️</a>";
      renderTree($conn, $row['id'], $level + 1);
      echo "</li>";
    }
    echo "</ul>";
  }
}

?>

<div class="container">
  <h2>🏫 Cấu hình tổ chức</h2>
  <div class="actions">
    <a href="add_unit.php" class="btn-add">➕ Thêm Trường</a>
  </div>

  <div class="tree-container">
    <?php renderTree($conn); ?>
  </div>
</div>

<style>
.container { padding: 20px; }
h2 { text-align:center; color:#2d3436; margin-bottom:15px; }
.actions { text-align:right; margin-bottom:15px; }

.btn-add {
  background:#28a745;
  color:white;
  padding:8px 15px;
  border-radius:6px;
  text-decoration:none;
}
.btn-add:hover { background:#218838; }

.tree-container ul { list-style:none; padding-left:20px; }
.tree-container li { margin:6px 0; }

.btn-small {
  background:#dfe6e9;
  padding:3px 7px;
  border-radius:4px;
  font-size:13px;
  text-decoration:none;
  color:#2d3436;
}
.btn-small.blue { background:#74b9ff; color:white; }
.btn-small.red { background:#ff7675; color:white; }
</style>

<?php include("../includes/footer.php"); ?>
