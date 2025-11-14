<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

//Chỉ quản trị viên có quyền
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
  echo "<div class='container'><p style='color:red;'>Bạn không có quyền truy cập trang này.</p></div>";
  include("../includes/footer.php");
  exit();
}

$message = "";

//CẬP NHẬT HẠN NỘP QUÁ HẠN THEO CHU KỲ
$conn->query("
  UPDATE fee_policy
  SET due_date = CASE 
    WHEN cycle = 'Tháng' AND due_date < CURDATE() THEN DATE_ADD(due_date, INTERVAL 1 MONTH)
    WHEN cycle = 'Học kỳ' AND due_date < CURDATE() THEN DATE_ADD(due_date, INTERVAL 6 MONTH)
    WHEN cycle = 'Năm' AND due_date < CURDATE() THEN DATE_ADD(due_date, INTERVAL 1 YEAR)
    ELSE due_date
  END
");

//XÓA CHÍNH SÁCH
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {
  $deleteId = intval($_POST['delete_id']);

  $check = $conn->prepare("SELECT status FROM fee_policy WHERE id = ?");
  $check->bind_param("i", $deleteId);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['status'] === 'Active') {
      $message = "<p class='error'>Không thể xóa chính sách đang hoạt động. Vui lòng hủy kích hoạt trước.</p>";
    } else {
      $conn->query("DELETE FROM fee_policy_rule WHERE policy_id = $deleteId");
      $del = $conn->prepare("DELETE FROM fee_policy WHERE id = ?");
      $del->bind_param("i", $deleteId);
      if ($del->execute()) {
        $message = "<p class='success'>Đã xóa chính sách đoàn phí thành công!</p>";
      } else {
        $message = "<p class='error'>Lỗi khi xóa chính sách.</p>";
      }
    }
  }
}

//KÍCH HOẠT CHÍNH SÁCH (chỉ 1 Active toàn hệ thống)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['activate_id'])) {
  $activateId = intval($_POST['activate_id']);

  $check = $conn->query("SELECT id FROM fee_policy WHERE status = 'Active' LIMIT 1");
  if ($check->num_rows > 0) {
    $message = "<p class='error'>Hiện đã có một chính sách đang 'Kích hoạt'. Hãy hủy kích hoạt chính sách hiện tại trước.</p>";
  } else {
    $update = $conn->prepare("UPDATE fee_policy SET status = 'Active' WHERE id = ?");
    $update->bind_param("i", $activateId);
    if ($update->execute()) {
      $message = "<p class='success'>Chính sách đã được kích hoạt thành công!</p>";
    } else {
      $message = "<p class='error'>Lỗi khi kích hoạt chính sách.</p>";
    }
  }
}

//HỦY KÍCH HOẠT (CHUYỂN VỀ NHÁP)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['deactivate_id'])) {
  $deactivateId = intval($_POST['deactivate_id']);

  $update = $conn->prepare("UPDATE fee_policy SET status = 'Draft' WHERE id = ?");
  $update->bind_param("i", $deactivateId);
  if ($update->execute()) {
    $message = "<p class='success'>Chính sách đã được chuyển sang trạng thái 'Nháp'.</p>";
  } else {
    $message = "<p class='error'>Lỗi khi hủy kích hoạt chính sách.</p>";
  }
}

//HÀM TÍNH HẠN NỘP TIẾP THEO
function getNextDue($cycle, $due_date) {
  if (!$due_date) return "—";
  switch ($cycle) {
    case 'Tháng': return date("d/m/Y", strtotime("+1 month", strtotime($due_date)));
    case 'Học kỳ': return date("d/m/Y", strtotime("+6 month", strtotime($due_date)));
    case 'Năm': return date("d/m/Y", strtotime("+1 year", strtotime($due_date)));
    default: return date("d/m/Y", strtotime($due_date));
  }
}

//LẤY DANH SÁCH CHÍNH SÁCH
$sql = "SELECT p.*, u.fullName AS created_by FROM fee_policy p 
        LEFT JOIN users u ON p.created_by = u.userId 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>

<div class="container">
  <h2>Quản lý chính sách đoàn phí</h2>
  <?= $message ?>

  <div class="actions">
    <a href="policy_settings.php" class="btn-add">Thêm chính sách mới</a>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Tên chính sách</th>
        <th>Chu kỳ</th>
        <th>Hạn nộp</th>
        <th>Hạn nộp tiếp theo</th>
        <th>Mức thu (VNĐ)</th>
        <th>Trạng thái</th>
        <th>Người tạo</th>
        <th>Ngày tạo</th>
        <th>Thao tác</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['policy_name']) ?></td>
            <td><?= htmlspecialchars($row['cycle']) ?></td>
            <td><?= date("d/m/Y", strtotime($row['due_date'])) ?></td>
            <td><?= getNextDue($row['cycle'], $row['due_date']) ?></td>
            <td><?= number_format($row['standard_amount'], 0, ',', '.') ?></td>
            <td>
              <?php if ($row['status'] === 'Active'): ?>
                <span class="status active">Kích hoạt</span>
              <?php else: ?>
                <span class="status draft">Nháp</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['created_by'] ?? 'Admin') ?></td>
            <td><?= date("d/m/Y", strtotime($row['created_at'])) ?></td>
            <td>
              <?php if ($row['status'] === 'Active'): ?>
                <form method="POST" style="display:inline;" onsubmit="return confirmDeactivate();">
                  <input type="hidden" name="deactivate_id" value="<?= $row['id'] ?>">
                  <button type="submit" class="btn-deactivate">Hủy kích hoạt</button>
                </form>
              <?php else: ?>
                <form method="POST" style="display:inline;" onsubmit="return confirmActivate();">
                  <input type="hidden" name="activate_id" value="<?= $row['id'] ?>">
                  <button type="submit" class="btn-activate">Kích hoạt</button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirmDelete();">
                  <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                  <button type="submit" class="btn-delete">Xóa</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="10" style="text-align:center;">Không có chính sách nào.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
function confirmDelete() {
  return confirm('Bạn có chắc muốn xóa chính sách này không? Hành động này không thể hoàn tác!');
}
function confirmActivate() {
  return confirm('Bạn có chắc muốn kích hoạt chính sách này không? Hệ thống chỉ được phép có duy nhất 1 chính sách hoạt động!');
}
function confirmDeactivate() {
  return confirm('Bạn có chắc muốn hủy kích hoạt chính sách này và chuyển về trạng thái "Nháp"?');
}
</script>

<style>
.container { padding: 20px; margin-left: 240px; max-width: calc(100% - 300px); }
h2 { text-align: center; color: #2d3436; margin-bottom: 20px; }
.actions { text-align: right; margin-bottom: 15px; }
.btn-add {
  background: #27ae60; color: white; padding: 8px 15px;
  text-decoration: none; border-radius: 6px;
}
.btn-add:hover { background: #219150; }

.table { width: 100%; border-collapse: collapse; font-size: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
th { background: #007bff; color: white; }
tr:nth-child(even) { background: #f9f9f9; }
tr:hover { background: #eef6ff; }

.status.active { color: #27ae60; font-weight: bold; }
.status.draft { color: #f39c12; font-weight: bold; }

.btn-delete, .btn-activate, .btn-deactivate {
  border: none; border-radius: 5px; padding: 5px 10px;
  color: white; cursor: pointer; font-weight: 600;
}
.btn-delete { background: #dc3545; }
.btn-delete:hover { background: #c82333; }
.btn-activate { background: #0984e3; }
.btn-activate:hover { background: #0772c3; }
.btn-deactivate { background: #e67e22; }
.btn-deactivate:hover { background: #cf711f; }

.success { color: #27ae60; font-weight: bold; text-align:center; }
.error { color: #d63031; font-weight: bold; text-align:center; }
</style>

<?php include("../includes/footer.php"); ?>
