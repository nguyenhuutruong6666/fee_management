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

$currentUser = $_SESSION['user'];
$userId = intval($currentUser['userId']);
$userRole = $currentUser['role_name'] ?? '';
$userUnit = intval($currentUser['unit'] ?? 0);
$isAdmin = intval($currentUser['isAdmin'] ?? 0);

$allowedRoles = ['BCH Chi đoàn', 'BCH Khoa', 'BCH Trường'];
if ($isAdmin != 1 && !in_array($userRole, $allowedRoles)) {
  echo "<div class='container'><p class='error'>Bạn không có quyền truy cập chức năng này.</p></div>";
  include("../includes/footer.php");
  exit();
}

$message = "";

//LẤY DANH SÁCH HOẠT ĐỘNG ĐÃ DUYỆT
$activities = $conn->query("
  SELECT id, title, approved_budget, status, unit_type, unit_id
  FROM activity_proposal
  WHERE status='Đã phê duyệt' 
  AND unit_id = $userUnit
  ORDER BY approved_at DESC
");

//XỬ LÝ SUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $activity_id = intval($_POST['activity_id']);
  $transaction_type = trim($_POST['transaction_type']); // "Chi" hoặc "Tạm ứng"
  $amount = floatval($_POST['amount']);
  $transaction_date = $_POST['transaction_date'];
  $description = trim($_POST['description']);
  $receiver = trim($_POST['receiver']);
  $payment_method = trim($_POST['payment_method']);

  //Kiểm tra dữ liệu bắt buộc
  if (!$activity_id || !$transaction_type || !$amount || !$receiver) {
    $message = "<p class='error'>Vui lòng nhập đầy đủ thông tin bắt buộc.</p>";
  } else {
    //Lấy hạn mức còn lại
    $check = $conn->prepare("
      SELECT approved_budget, COALESCE(SUM(fc.amount), 0) AS total_spent
      FROM activity_proposal ap
      LEFT JOIN fee_cashbook_unit fc ON fc.related_voucher_id = ap.id 
      AND fc.transaction_type IN ('Chi','Tạm ứng')
      WHERE ap.id = ?
      GROUP BY ap.approved_budget
    ");
    $check->bind_param("i", $activity_id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();

    if (!$res) {
      $message = "<p class='error'>Không tìm thấy hoạt động hợp lệ để chi tiền.</p>";
    } else {
      $approved_budget = floatval($res['approved_budget']);
      $total_spent = floatval($res['total_spent']);
      $remaining = $approved_budget - $total_spent;

      if ($amount > $remaining) {
        $message = "<p class='error'>Số tiền đề nghị chi (".number_format($amount)."đ) vượt hạn mức còn lại (".number_format($remaining)."đ).</p>";
      } else {
        // ----- Ghi sổ quỹ -----
        $conn->begin_transaction();
        try {
          $insert = $conn->prepare("
            INSERT INTO fee_cashbook_unit
            (unit_type, unit_id, transaction_type, transaction_date, amount, related_voucher_id, description, recorded_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
          ");
          $insert->bind_param(
            "sissdssi",
            $userRole,
            $userUnit,
            $transaction_type,
            $transaction_date,
            $amount,
            $activity_id,
            $description,
            $userId
          );
          $insert->execute();

          // Cập nhật tổng đã chi/tạm ứng 
          $update = $conn->prepare("
            UPDATE activity_proposal
            SET approved_budget = approved_budget, -- không đổi
                updated_at = NOW()
            WHERE id = ?
          ");
          $update->bind_param("i", $activity_id);
          $update->execute();

          $conn->commit();
          $message = "<p class='success'>Đã ghi sổ chi/tạm ứng cho hoạt động thành công!</p>";
        } catch (Exception $e) {
          $conn->rollback();
          $message = "<p class='error'>Lỗi khi ghi sổ: " . $e->getMessage() . "</p>";
        }
      }
    }
  }
}
?>

<!-- GIAO DIỆN  -->
<div class="container">
  <h2>Tạm ứng / Chi tiền cho hoạt động</h2>
  <?= $message ?>

  <form method="POST" class="form-disbursement">
    <label>Chọn hoạt động đã duyệt:</label>
    <select name="activity_id" required>
      <option value="">-- Chọn hoạt động --</option>
      <?php while ($a = $activities->fetch_assoc()): ?>
        <option value="<?= $a['id'] ?>">
          <?= htmlspecialchars($a['title']) ?> — Hạn mức: <?= number_format($a['approved_budget']) ?>đ
        </option>
      <?php endwhile; ?>
    </select>

    <label>Loại chi:</label>
    <select name="transaction_type" required>
      <option value="Chi">Chi thẳng</option>
      <option value="Tạm ứng">Tạm ứng</option>
    </select>

    <label>Số tiền (VNĐ):</label>
    <input type="number" name="amount" step="1000" min="0" required>

    <label>Ngày chi:</label>
    <input type="date" name="transaction_date" required value="<?= date('Y-m-d') ?>">

    <label>Hình thức thanh toán:</label>
    <select name="payment_method">
      <option value="Tiền mặt">Tiền mặt</option>
      <option value="Chuyển khoản">Chuyển khoản</option>
    </select>

    <label>Người nhận:</label>
    <input type="text" name="receiver" placeholder="VD: Nguyễn Văn A" required>

    <label>Diễn giải:</label>
    <textarea name="description" rows="3" placeholder="VD: Chi tiền mua dụng cụ hoạt động Đoàn..."></textarea>

    <button type="submit" class="btn-confirm">Lưu chứng từ chi</button>
  </form>
</div>

<style>
.container { padding:25px; margin-left:240px; max-width:calc(100% - 310px);}
h2 { text-align:center; color:#2d3436; margin-bottom:25px;}
form { background:#f8f9fa; padding:20px; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
label { display:block; margin-top:10px; font-weight:bold;}
input, select, textarea { width:100%; padding:8px; border-radius:6px; border:1px solid #ccc; margin-top:5px;}
.btn-confirm { margin-top:15px; width:100%; background:#27ae60; color:white; border:none; padding:10px; border-radius:6px; cursor:pointer; font-size:16px;}
.btn-confirm:hover { background:#219150;}
.success { color:#27ae60; font-weight:bold; text-align:center; }
.error { color:#d63031; font-weight:bold; text-align:center; }
</style>

<?php include("../includes/footer.php"); ?>
