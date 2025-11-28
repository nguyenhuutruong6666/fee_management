<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// ====== KIỂM TRA ĐĂNG NHẬP ======
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['user'];
$user_id = intval($user['userId']);
$user_role = $user['role_name'] ?? '';
$user_unit = intval($user['unit'] ?? 0);
$isAdmin = intval($user['isAdmin'] ?? 0);

$allowedRoles = ['BCH Chi đoàn', 'BCH Khoa', 'BCH Trường'];
if ($isAdmin != 1 && !in_array($user_role, $allowedRoles)) {
  echo "<div class='container'><p class='error'>Bạn không có quyền truy cập chức năng này.</p></div>";
  include("../includes/footer.php");
  exit();
}

$message = "";

// ====== LẤY TỶ LỆ PHÂN BỔ HIỆN HÀNH ======
$ratioQuery = $conn->query("
  SELECT * FROM fee_distribution_ratio
  WHERE CURDATE() BETWEEN effective_from AND effective_to
  ORDER BY id DESC LIMIT 1
");
$currentRatio = $ratioQuery ? $ratioQuery->fetch_assoc() : null;
$chi_doan_ratio = $currentRatio['chi_doan_ratio'] ?? 0;
$khoa_ratio = $currentRatio['khoa_ratio'] ?? 0;
$truong_ratio = $currentRatio['truong_ratio'] ?? 0;

// ====== XỬ LÝ PHÂN BỔ & CHUYỂN NỘP ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $period_label = trim($_POST['period_label']);
  $total_amount = floatval($_POST['total_amount']);
  $chi_doan_ratio = floatval($_POST['chi_doan_ratio']);
  $khoa_ratio = floatval($_POST['khoa_ratio']);
  $truong_ratio = floatval($_POST['truong_ratio']);

  // Kiểm tra tỷ lệ
  if (round($chi_doan_ratio + $khoa_ratio + $truong_ratio, 2) != 100) {
    $message = "<p class='error'>Tổng tỷ lệ phân bổ phải bằng 100%.</p>";
  } elseif ($total_amount <= 0) {
    $message = "<p class='error'>Số tiền phân bổ phải lớn hơn 0.</p>";
  } else {
    // Kiểm tra số dư sổ quỹ
    $balanceCheck = $conn->query("
      SELECT SUM(CASE WHEN transaction_type='Thu' THEN amount ELSE -amount END) AS balance
      FROM fee_cashbook_unit
      WHERE unit_type='$user_role' AND unit_id=$user_unit
    ");
    $currentBalance = $balanceCheck ? floatval($balanceCheck->fetch_assoc()['balance']) : 0;

    if ($currentBalance < $total_amount) {
      $message = "<p class='error'>Không đủ số dư để chuyển nộp. (Số dư hiện tại: " . number_format($currentBalance, 0, ',', '.') . "đ)</p>";
    } else {
      $conn->begin_transaction();
      try {
        // Tính toán phân bổ
        $chi_doan_keep = round($total_amount * $chi_doan_ratio / 100, 2);
        $khoa_keep = round($total_amount * $khoa_ratio / 100, 2);
        $truong_keep = round($total_amount * $truong_ratio / 100, 2);

        $transferred_to_unit = ($user_role === 'BCH Chi đoàn') ? 'BCH Khoa' :
                              (($user_role === 'BCH Khoa') ? 'BCH Trường' : 'Không');
        $transferred_amount = ($user_role === 'BCH Trường') ? 0 : $total_amount - $chi_doan_keep;

        // Ghi phiếu phân bổ
        $stmt = $conn->prepare("
          INSERT INTO fee_allocation_voucher
          (period_label, unit_type, unit_id, total_amount, chi_doan_keep, khoa_keep, truong_keep,
           transferred_to_unit, transferred_amount, transfer_date, status, created_by, created_at)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Đã lập', ?, NOW())
        ");
        $stmt->bind_param(
          "ssissssddi",
          $period_label,
          $user_role,
          $user_unit,
          $total_amount,
          $chi_doan_keep,
          $khoa_keep,
          $truong_keep,
          $transferred_to_unit,
          $transferred_amount,
          $user_id
        );
        $stmt->execute();
        $voucher_id = $conn->insert_id;

        // Ghi vào sổ quỹ
        $desc = "Phân bổ kỳ $period_label - Giữ lại & chuyển cấp trên";
        $book = $conn->prepare("
          INSERT INTO fee_cashbook_unit
          (unit_type, unit_id, transaction_type, transaction_date, amount, related_voucher_id, description, recorded_by, created_at)
          VALUES (?, ?, 'Phân bổ', NOW(), ?, ?, ?, ?, NOW())
        ");
        $book->bind_param("sidssi", $user_role, $user_unit, $total_amount, $voucher_id, $desc, $user_id);
        $book->execute();

        $conn->commit();
        $message = "<p class='success'>Đã tạo phiếu phân bổ kỳ <b>$period_label</b> thành công!</p>";
      } catch (Exception $e) {
        $conn->rollback();
        $message = "<p class='error'>Lỗi khi tạo phiếu: " . $e->getMessage() . "</p>";
      }
    }
  }
}

// ====== LẤY DANH SÁCH KỲ THU PHÍ ======
$sumSQL = "
  SELECT 
    o.period_label, 
    SUM(o.amount) AS total_amount
  FROM fee_obligation o
  WHERE o.status = 'Đã nộp'
  GROUP BY o.period_label
  ORDER BY o.period_label DESC
";
$totalPeriods = $conn->query($sumSQL);
?>

<!-- ====== GIAO DIỆN ====== -->
<div class="container">
  <h2>Phân bổ & Chuyển nộp đoàn phí</h2>
  <?= $message ?>

  <form method="POST" class="allocation-form">
    <label>Chọn kỳ thu phí:</label>
    <select name="period_label" required>
      <option value="">-- Chọn kỳ thu phí --</option>
      <?php 
      if ($totalPeriods && $totalPeriods->num_rows > 0) {
        while ($p = $totalPeriods->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($p['period_label']) ?>">
            <?= htmlspecialchars($p['period_label']) ?> - Tổng: <?= number_format($p['total_amount'], 0, ',', '.') ?>đ
          </option>
      <?php endwhile;
      } else {
        echo '<option disabled>Không có kỳ thu phí khả dụng</option>';
      }
      ?>
    </select>

    <label>Tổng số tiền cần phân bổ (VNĐ):</label>
    <input type="number" name="total_amount" min="0" step="1000" placeholder="Nhập số tiền thực tế" required>

    <h3>Tỷ lệ phân bổ (%)</h3>
    <div class="ratio-group">
      <div><span>Chi đoàn giữ lại:</span>
        <input type="number" name="chi_doan_ratio" value="<?= $chi_doan_ratio ?>" step="0.1" required>
      </div>
      <div><span>Khoa giữ lại:</span>
        <input type="number" name="khoa_ratio" value="<?= $khoa_ratio ?>" step="0.1" required>
      </div>
      <div><span>Trường giữ lại:</span>
        <input type="number" name="truong_ratio" value="<?= $truong_ratio ?>" step="0.1" required>
      </div>
    </div>

    <button type="submit" class="btn-confirm">Thực hiện phân bổ & chuyển nộp</button>
  </form>

  <hr>
  <h3>Báo cáo phân bổ gần nhất</h3>
  <table class="data-table">
    <thead>
      <tr>
        <th>Kỳ</th>
        <th>Đơn vị lập phiếu</th>
        <th>Tổng tiền</th>
        <th>Chi đoàn giữ lại</th>
        <th>Khoa giữ lại</th>
        <th>Trường giữ lại</th>
        <th>Chuyển nộp lên</th>
        <th>Ngày tạo</th>
        <th>Trạng thái</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $list = $conn->query("
        SELECT * FROM fee_allocation_voucher
        WHERE unit_type='$user_role' AND unit_id=$user_unit
        ORDER BY created_at DESC LIMIT 10
      ");
      if ($list && $list->num_rows > 0):
        while ($row = $list->fetch_assoc()):
      ?>
      <tr>
        <td><?= htmlspecialchars($row['period_label']) ?></td>
        <td><?= htmlspecialchars($row['unit_type']) ?></td>
        <td><?= number_format($row['total_amount'], 0, ',', '.') ?>đ</td>
        <td><?= number_format($row['chi_doan_keep'], 0, ',', '.') ?>đ</td>
        <td><?= number_format($row['khoa_keep'], 0, ',', '.') ?>đ</td>
        <td><?= number_format($row['truong_keep'], 0, ',', '.') ?>đ</td>
        <td><?= htmlspecialchars($row['transferred_to_unit']) ?> (<?= number_format($row['transferred_amount'], 0, ',', '.') ?>đ)</td>
        <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
        <td><b><?= htmlspecialchars($row['status']) ?></b></td>
      </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="9" style="text-align:center;">Chưa có phiếu phân bổ nào.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<style>
.container { padding:25px; margin-left:240px; max-width:calc(100% - 310px);}
h2 { text-align:center; color:#2d3436; margin-bottom:25px; }
form { background:#f8f9fa; padding:20px; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
label { display:block; margin-top:10px; font-weight:bold;}
input, select { width:100%; padding:8px; border-radius:6px; border:1px solid #ccc; margin-top:5px;}
.ratio-group { display:flex; justify-content:space-between; gap:10px; margin-top:10px;}
.ratio-group div { flex:1; background:#fff; padding:10px; border-radius:8px; border:1px solid #ddd;}
.btn-confirm { margin-top:15px; width:100%; background:#27ae60; color:white; border:none; padding:10px; border-radius:6px; cursor:pointer; font-size:16px;}
.btn-confirm:hover { background:#219150; }
.data-table { width:100%; border-collapse:collapse; margin-top:20px;}
.data-table th, .data-table td { border:1px solid #ccc; padding:8px; text-align:center; }
.data-table th { background:#f1f2f6; }
.success { color:#27ae60; font-weight:bold; text-align:center; }
.error { color:#d63031; font-weight:bold; text-align:center; }
</style>

<?php include("../includes/footer.php"); ?>
