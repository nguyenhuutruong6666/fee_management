<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// ✅ Kiểm tra quyền truy cập (chỉ Admin)
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
  echo "<div class='container'><p style='color:red;'>🚫 Bạn không có quyền truy cập trang này.</p></div>";
  include("../includes/footer.php");
  exit();
}

/**
 * 🧩 Hàm đệ quy hiển thị cây tổ chức
 * Hiển thị các cấp: Trường → Khoa → Chi đoàn
 */
function renderTree($conn, $parent_id = NULL, $level = 0) {
  if ($parent_id === NULL) {
    $sql = "SELECT * FROM organization_units WHERE parent_id IS NULL ORDER BY unit_name";
  } else {
    $sql = "SELECT * FROM organization_units WHERE parent_id = $parent_id ORDER BY unit_name";
  }

  $result = $conn->query($sql);
  if ($result && $result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {

      // Gán icon theo cấp đơn vị (dùng if thay match để tránh lỗi PHP cũ)
      if ($row['unit_level'] === 'Truong') {
        $icon = '🏫';
      } elseif ($row['unit_level'] === 'Khoa') {
        $icon = '🏢';
      } elseif ($row['unit_level'] === 'ChiDoan') {
        $icon = '👥';
      } else {
        $icon = '📁';
      }

      echo "<li>";
      echo "$icon <b>" . htmlspecialchars($row['unit_name']) . "</b> 
            <span style='color:gray;'>(" . $row['unit_level'] . ")</span>";

      // Nút thêm cấp con
      if ($row['unit_level'] === 'Truong') {
        echo " <a href='add_unit.php?parent_id={$row['id']}&next=Khoa' class='btn-small green' title='Thêm Khoa'>➕ Khoa</a>";
      } elseif ($row['unit_level'] === 'Khoa') {
        echo " <a href='add_unit.php?parent_id={$row['id']}&next=ChiDoan' class='btn-small blue' title='Thêm Chi đoàn'>➕ Chi đoàn</a>";
      }

      // Nút sửa & xóa
      echo " <a href='edit_unit.php?id={$row['id']}' class='btn-small orange' title='Sửa'>✏️</a>";
      echo " <a href='delete_unit.php?id={$row['id']}' onclick=\"return confirm('Bạn có chắc muốn xóa đơn vị này và các cấp con không?');\" class='btn-small red' title='Xóa'>🗑️</a>";

      // Đệ quy hiển thị cấp dưới
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
    <a href="add_unit.php?next=Truong" class="btn-add">➕ Thêm Trường</a>
  </div>

  <div class="tree-container">
    <?php renderTree($conn); ?>
  </div>
</div>

<style>
/* ==== CSS giao diện đẹp và dễ nhìn ==== */
.container {
  padding: 20px;
  max-width: 1000px;
  margin: auto;
  background: #f8f9fa;
  border-radius: 10px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

h2 {
  text-align: center;
  margin-bottom: 20px;
  color: #2d3436;
}

.actions {
  text-align: right;
  margin-bottom: 15px;
}

.btn-add {
  background: #28a745;
  color: white;
  padding: 8px 15px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 500;
}
.btn-add:hover { background: #218838; }

.tree-container ul {
  list-style: none;
  padding-left: 30px;
  margin: 10px 0;
}

.tree-container li {
  margin: 10px 0;
  padding: 6px 10px;
  background: #ffffff;
  border-left: 3px solid #007bff;
  border-radius: 6px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}

.tree-container li:hover {
  background: #f1f9ff;
}

.btn-small {
  padding: 3px 8px;
  font-size: 13px;
  border-radius: 4px;
  text-decoration: none;
  margin-left: 6px;
  color: white;
}

.green { background: #27ae60; }
.blue { background: #0984e3; }
.orange { background: #e17055; }
.red { background: #d63031; }

.btn-small:hover {
  opacity: 0.85;
}
</style>

<?php include("../includes/footer.php"); ?>
