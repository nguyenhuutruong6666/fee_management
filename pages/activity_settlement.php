<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// ✅ KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['user'];
$userId = intval($user['userId']);
$userRole = $user['role_name'] ?? '';
$userUnit = intval($user['unit'] ?? 0);
$isAdmin = intval($user['isAdmin'] ?? 0);

$allowedRoles = ['BCH Khoa', 'BCH Chi đoàn'];
if ($isAdmin != 1 && !in_array($userRole, $allowedRoles)) {
  echo "<div class='container'><p class='error'>🚫 Bạn không có quyền truy cập chức năng này.</p></div>";
  include("../includes/footer.php");
  exit();
}

$message = "";

// ✅ LẤY DANH SÁCH HOẠT ĐỘNG ĐÃ HOÀN THÀNH (chưa quyết toán)
$activities = $conn->query("
  SELECT id, title, approved_budget, estimated_budget, status
  FROM activity_proposal
  WHERE (status='Đã thực hiện' OR status='Đã phê duyệt')
    AND unit_id = $userUnit
  ORDER BY approved_at DESC
");

// ✅ XỬ LÝ NỘP QUYẾT TOÁN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $activity_id = intval($_POST['activity_id']);
  $actual_expense = floatval($_POST['actual_expense']);
  $difference = floatval($_POST['difference']);
  $explanation = trim($_POST['explanation']);
  $report_file = $_FILES['report_file'] ?? null;

  // Kiểm tra đầu vào
  if (!$activity_id || !$actual_expense) {
    $message = "<p class='error'>❌ Vui lòng nhập đầy đủ số tiền thực chi và chọn hoạt động.</p>";
  } else {
    // Lấy dữ liệu hoạt động
    $stmt = $conn->prepare("
      SELECT approved_budget, title 
      FROM activity_proposal 
      WHERE id=? AND (status='Đã phê duyệt' OR status='Đã thực hiện')
    ");
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $activity = $stmt->get_result()->fetch_assoc();

    if (!$activity) {
      $message = "<p class='error'>❌ Hoạt động không hợp lệ hoặc chưa đến giai đoạn quyết toán.</p>";
    } else {
      $approved_budget = floatval($activity['approved_budget']);
      $title = $activity['title'];

      // Tính tổng chi thực tế trong sổ quỹ
      $sum = $conn->prepare("
        SELECT COALESCE(SUM(amount),0) AS total_spent 
        FROM fee_cashbook_unit 
        WHERE related_voucher_id=? AND transaction_type IN ('Chi hoạt động','Tạm ứng','Hoàn ứng')
      ");
      $sum->bind_param("i", $activity_id);
      $sum->execute();
      $total_spent = floatval($sum->get_result()->fetch_assoc()['total_spent']);

      // Kiểm tra tổng chi không vượt dự toán
      if ($actual_expense > $approved_budget) {
        $message = "<p class='error'>⚠️ Tổng chi thực tế (".number_format($actual_expense)."đ) vượt dự toán duyệt (".number_format($approved_budget)."đ).</p>";
      } elseif ($total_spent == 0) {
        $message = "<p class='error'>❌ Hoạt động này chưa có chứng từ chi tiêu. Không thể quyết toán.</p>";
      } else {
        // Xử lý upload file
        if (!$report_file || $report_file['error'] != 0) {
          $message = "<p class='error'>❌ Vui lòng đính kèm file báo cáo (PDF, Excel hoặc DOCX).</p>";
        } else {
          $ext = strtolower(pathinfo($report_file['name'], PATHINFO_EXTENSION));
          $allowed = ['pdf', 'xls', 'xlsx', 'doc', 'docx'];
          $size = $report_file['size'];

          if (!in_array($ext, $allowed)) {
            $message = "<p class='error'>❌ Định dạng file không hợp lệ. Chỉ chấp nhận PDF/XLS/XLSX/DOC/DOCX.</p>";
          } elseif ($size > 10 * 1024 * 1024) {
            $message = "<p class='error'>❌ Dung lượng file vượt quá 10MB.</p>";
          } else {
            $target_dir = "../uploads/settlements/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $filename = "settlement_" . time() . "_" . rand(1000,9999) . "." . $ext;
            $filepath = $target_dir . $filename;

            if (move_uploaded_file($report_file['tmp_name'], $filepath)) {
              // Ghi vào bảng quyết toán
              $conn->begin_transaction();
              try {
                $insert = $conn->prepare("
                  INSERT INTO activity_settlement
                  (activity_id, proposed_budget, actual_expense, difference, explanation, file_path, submitted_by, submitted_at, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Chờ duyệt')
                ");
                $insert->bind_param("idddssi", $activity_id, $approved_budget, $actual_expense, $difference, $explanation, $filename, $userId);
                $insert->execute();

                // Cập nhật trạng thái hoạt động
                $update = $conn->prepare("UPDATE activity_proposal SET status='Chờ duyệt quyết toán', updated_at=NOW() WHERE id=?");
                $update->bind_param("i", $activity_id);
                $update->execute();

                $conn->commit();
                $message = "<p class='success'>✅ Đã nộp báo cáo quyết toán cho hoạt động <b>$title</b> thành công!</p>";
              } catch (Exception $e) {
                $conn->rollback();
                $message = "<p class='error'>❌ Lỗi khi lưu quyết toán: " . $e->getMessage() . "</p>";
              }
            } else {
              $message = "<p class='error'>❌ Không thể tải file lên. Vui lòng thử lại.</p>";
            }
          }
        }
      }
    }
  }
}

// ✅ LẤY DANH SÁCH QUYẾT TOÁN ĐÃ GỬI
$settlements = $conn->query("
  SELECT s.id, ap.title, s.actual_expense, s.difference, s.status, s.submitted_at, s.file_path
  FROM activity_settlement s
  JOIN activity_proposal ap ON s.activity_id = ap.id
  WHERE ap.unit_id = $userUnit
  ORDER BY s.submitted_at DESC
");
?>

<div class="container">
  <h2>🧾 Quyết toán hoạt động</h2>
  <?= $message ?>

  <form method="POST" enctype="multipart/form-data" class="settlement-form">
    <label>Chọn hoạt động:</label>
    <select name="activity_id" required>
      <option value="">-- Chọn hoạt động --</option>
      <?php while ($a = $activities->fetch_assoc()): ?>
        <option value="<?= $a['id'] ?>">
          <?= htmlspecialchars($a['title']) ?> (Dự toán: <?= number_format($a['approved_budget']) ?>đ)
        </option>
      <?php endwhile; ?>
    </select>

    <label>Số tiền thực chi (VNĐ):</label>
    <input type="number" name="actual_expense" step="1000" min="0" required>

    <label>Chênh lệch (+/-):</label>
    <input type="number" name="difference" step="1000" placeholder="Tự nhập hoặc hệ thống tự tính">

    <label>Giải trình (nếu có):</label>
    <textarea name="explanation" rows="3" placeholder="VD: Vượt dự toán do phát sinh chi phí thuê thêm thiết bị..."></textarea>

    <label>File báo cáo quyết toán (PDF/Excel/DOCX ≤10MB):</label>
    <input type="file" name="report_file" accept=".pdf,.xls,.xlsx,.doc,.docx" required>

    <button type="submit" class="btn-confirm">📤 Nộp quyết toán</button>
  </form>

  <h3 style="margin-top:30px;">📚 Danh sách quyết toán đã nộp</h3>
  <table class="data-table">
    <thead>
      <tr>
        <th>Hoạt động</th>
        <th>Thực chi</th>
        <th>Chênh lệch</th>
        <th>Trạng thái</th>
        <th>Ngày nộp</th>
        <th>Tệp</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($settlements->num_rows > 0): ?>
        <?php while ($s = $settlements->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($s['title']) ?></td>
            <td><?= number_format($s['actual_expense']) ?>đ</td>
            <td><?= number_format($s['difference']) ?>đ</td>
            <td><?= htmlspecialchars($s['status']) ?></td>
            <td><?= date("d/m/Y H:i", strtotime($s['submitted_at'])) ?></td>
            <td><a href="../uploads/settlements/<?= htmlspecialchars($s['file_path']) ?>" target="_blank">📎 Xem</a></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="6" style="text-align:center;">Chưa có báo cáo quyết toán nào.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<style>
.container { padding:25px; margin-left:240px; max-width:calc(100% - 310px);}
h2, h3 { text-align:center; color:#2d3436; margin-bottom:20px;}
form { background:#f8f9fa; padding:20px; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
label { display:block; margin-top:10px; font-weight:bold;}
input, select, textarea { width:100%; padding:8px; border-radius:6px; border:1px solid #ccc; margin-top:5px;}
.btn-confirm { margin-top:15px; width:100%; background:#27ae60; color:white; border:none; padding:10px; border-radius:6px; cursor:pointer; font-size:16px;}
.btn-confirm:hover { background:#219150;}
.success { color:#27ae60; font-weight:bold; text-align:center;}
.error { color:#d63031; font-weight:bold; text-align:center;}
table { width:100%; border-collapse:collapse; margin-top:10px;}
th, td { border:1px solid #ccc; padding:8px; text-align:center;}
th { background:#f1f2f6;}
</style>

<?php include("../includes/footer.php"); ?>
