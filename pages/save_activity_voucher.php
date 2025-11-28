<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

//KIỂM TRA ĐĂNG NHẬP
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
  echo "<div class='container'><p class='error'>Bạn không có quyền truy cập chức năng này.</p></div>";
  include("../includes/footer.php");
  exit();
}

$message = "";

//LẤY DANH SÁCH HOẠT ĐỘNG CÓ KHOẢN CHI
$activities = $conn->query("
  SELECT id, title, approved_budget, status 
  FROM activity_proposal
  WHERE (status='Đã phê duyệt' OR status='Đang thực hiện') 
    AND unit_id = $userUnit
  ORDER BY approved_at DESC
");

//XỬ LÝ LƯU CHỨNG TỪ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $activity_id = intval($_POST['activity_id']);
  $voucher_number = trim($_POST['voucher_number']);
  $voucher_type = trim($_POST['voucher_type']);
  $voucher_date = $_POST['voucher_date'];
  $amount = floatval($_POST['amount']);
  $description = trim($_POST['description']);

  //Kiểm tra thông tin bắt buộc
  if (!$activity_id || !$voucher_number || !$voucher_date || !$amount || !$description) {
    $message = "<p class='error'>Vui lòng nhập đầy đủ thông tin bắt buộc.</p>";
  } else {
    // Kiểm tra hoạt động hợp lệ 
    $stmt = $conn->prepare("SELECT approved_budget, title FROM activity_proposal WHERE id=? AND (status='Đã phê duyệt' OR status='Đang thực hiện')");
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $act = $stmt->get_result()->fetch_assoc();

    if (!$act) {
      $message = "<p class='error'>Không tìm thấy hoạt động hợp lệ để lưu chứng từ.</p>";
    } else {
      $approved_budget = floatval($act['approved_budget']);

      //Kiểm tra tổng chi hiện tại
      $sum = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total_spent
        FROM fee_cashbook_unit
        WHERE related_voucher_id = ? AND transaction_type = 'Chi hoạt động'
      ");
      $sum->bind_param("i", $activity_id);
      $sum->execute();
      $spent = $sum->get_result()->fetch_assoc()['total_spent'];
      $remaining = $approved_budget - $spent;

      if ($amount > $remaining) {
        $message = "<p class='error'>Số tiền chứng từ (".number_format($amount)."đ) vượt hạn mức còn lại (".number_format($remaining)."đ).</p>";
      } else {
        //Xử lý upload file 
        if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] != 0) {
          $message = "<p class='error'>Vui lòng chọn file minh chứng (PDF/JPG/PNG).</p>";
        } else {
          $file = $_FILES['attachment'];
          $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
          $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
          $size = $file['size'];

          if (!in_array($ext, $allowed)) {
            $message = "<p class='error'>Định dạng file không hợp lệ. Chỉ chấp nhận PDF/JPG/PNG.</p>";
          } elseif ($size > 10 * 1024 * 1024) {
            $message = "<p class='error'>Dung lượng file vượt quá 10MB.</p>";
          } else {
            $target_dir = "../uploads/vouchers/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $filename = "voucher_" . time() . "_" . rand(1000,9999) . "." . $ext;
            $filepath = $target_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
              //Ghi sổ chứng từ
              $conn->begin_transaction();
              try {
                $insert = $conn->prepare("
                  INSERT INTO fee_cashbook_unit
                  (unit_type, unit_id, transaction_type, transaction_date, amount, related_voucher_id, description, recorded_by, created_at)
                  VALUES (?, ?, 'Chi hoạt động', ?, ?, ?, ?, ?, NOW())
                ");
                $insert->bind_param("sisdssi", $userRole, $userUnit, $voucher_date, $amount, $activity_id, $description, $userId);
                $insert->execute();
                $cashbook_id = $conn->insert_id;

                //Lưu log file chứng từ
                $conn->query("
                  INSERT INTO activity_voucher_files (cashbook_id, voucher_number, voucher_type, file_path, uploaded_by, uploaded_at)
                  VALUES ($cashbook_id, '$voucher_number', '$voucher_type', '$filename', $userId, NOW())
                ");

                $conn->commit();
                $message = "<p class='success'>Đã lưu chứng từ <b>$voucher_number</b> thành công!</p>";
              } catch (Exception $e) {
                $conn->rollback();
                $message = "<p class='error'>Lỗi khi lưu chứng từ: " . $e->getMessage() . "</p>";
              }
            } else {
              $message = "<p class='error'>Không thể tải file lên. Vui lòng thử lại.</p>";
            }
          }
        }
      }
    }
  }
}

//DANH SÁCH CHỨNG TỪ ĐÃ LƯU
$vouchers = $conn->query("
  SELECT f.id, f.voucher_number, f.voucher_type, f.file_path, f.uploaded_at, ap.title, fc.amount, fc.description 
  FROM activity_voucher_files f
  JOIN fee_cashbook_unit fc ON f.cashbook_id = fc.id
  JOIN activity_proposal ap ON fc.related_voucher_id = ap.id
  WHERE fc.unit_id = $userUnit
  ORDER BY f.uploaded_at DESC
");
?>

<div class="container">
  <h2>Lưu chứng từ hoạt động</h2>
  <?= $message ?>

  <form method="POST" enctype="multipart/form-data" class="voucher-form">
    <label>Chọn hoạt động:</label>
    <select name="activity_id" required>
      <option value="">-- Chọn hoạt động --</option>
      <?php while ($a = $activities->fetch_assoc()): ?>
        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['title']) ?> (Hạn mức: <?= number_format($a['approved_budget']) ?>đ)</option>
      <?php endwhile; ?>
    </select>

    <label>Số chứng từ:</label>
    <input type="text" name="voucher_number" placeholder="VD: HD00123" required>

    <label>Loại chứng từ:</label>
    <input type="text" name="voucher_type" placeholder="VD: Hóa đơn GTGT, Phiếu chi..." required>

    <label>Ngày chứng từ:</label>
    <input type="date" name="voucher_date" required value="<?= date('Y-m-d') ?>">

    <label>Số tiền (VNĐ):</label>
    <input type="number" name="amount" step="1000" min="0" required>

    <label>Diễn giải:</label>
    <textarea name="description" rows="3" placeholder="VD: Chi tiền thuê sân khấu cho hoạt động Đoàn..." required></textarea>

    <label>File đính kèm (PDF/JPG/PNG ≤10MB):</label>
    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" required>

    <button type="submit" class="btn-confirm">Lưu chứng từ</button>
  </form>

  <h3 style="margin-top:30px;">Danh sách chứng từ đã lưu</h3>
  <table class="data-table">
    <thead>
      <tr>
        <th>Số chứng từ</th>
        <th>Loại</th>
        <th>Hoạt động</th>
        <th>Số tiền</th>
        <th>Diễn giải</th>
        <th>Tệp</th>
        <th>Ngày tải lên</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($vouchers->num_rows > 0): ?>
        <?php while ($v = $vouchers->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($v['voucher_number']) ?></td>
            <td><?= htmlspecialchars($v['voucher_type']) ?></td>
            <td><?= htmlspecialchars($v['title']) ?></td>
            <td><?= number_format($v['amount']) ?>đ</td>
            <td><?= htmlspecialchars($v['description']) ?></td>
            <td><a href="../uploads/vouchers/<?= htmlspecialchars($v['file_path']) ?>" target="_blank">📎 Xem</a></td>
            <td><?= date("d/m/Y H:i", strtotime($v['uploaded_at'])) ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="7" style="text-align:center;">Chưa có chứng từ nào.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<style>
.container { padding:25px; margin-left:240px; max-width:calc(100% - 310px);}
h2, h3 { text-align:center; color:#2d3436; margin-bottom:20px; }
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
