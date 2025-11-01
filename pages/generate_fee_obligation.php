<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// ✅ Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
  echo "<div class='container'><p style='color:red;'>🚫 Bạn không có quyền truy cập chức năng này.</p></div>";
  include("../includes/footer.php");
  exit();
}

$message = "";
$total_success = 0;
$total_failed = 0;

// ✅ Khi admin bấm nút “Sinh nghĩa vụ”
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $cycle_label = trim($_POST['cycle_label'] ?? '');
  $policy_id = intval($_POST['policy_id'] ?? 0);
  $run_by = $_SESSION['user']['userId'];
  $start_time = microtime(true);

  if (empty($cycle_label) || $policy_id <= 0) {
    $message = "<p class='error'>⚠️ Vui lòng chọn chính sách và nhập nhãn chu kỳ!</p>";
  } else {
    // ✅ Lấy thông tin chính sách
    $policy_sql = "SELECT * FROM fee_policy WHERE id=? AND status='Active' LIMIT 1";
    $stmt = $conn->prepare($policy_sql);
    $stmt->bind_param("i", $policy_id);
    $stmt->execute();
    $policy = $stmt->get_result()->fetch_assoc();

    if (!$policy) {
      $message = "<p class='error'>❌ Không tìm thấy chính sách đoàn phí đang hiệu lực!</p>";
    } else {
      // ✅ Lấy quy tắc giảm phí theo vai trò
      $rules = [];
      $rquery = $conn->prepare("SELECT role_name, amount FROM fee_policy_rule WHERE policy_id=?");
      $rquery->bind_param("i", $policy_id);
      $rquery->execute();
      $rresult = $rquery->get_result();
      while ($r = $rresult->fetch_assoc()) {
        $rules[$r['role_name']] = floatval($r['amount']);
      }

      // ✅ Lấy danh sách đoàn viên
      $sql_users = "
        SELECT u.userId, u.fullName, u.identifyCard, r.role_name
        FROM users u
        LEFT JOIN user_role ur ON u.userId = ur.user_id
        LEFT JOIN role r ON ur.role_id = r.id
        WHERE r.role_name IN ('Đoàn viên', 'BCH Trường', 'BCH Khoa', 'BCH Chi đoàn')
      ";
      $users = $conn->query($sql_users);

      if ($users->num_rows == 0) {
        $message = "<p class='error'>⚠️ Không có đoàn viên nào trong hệ thống.</p>";
      } else {
        $due_date = date('Y-m-d', strtotime("+{$policy['due_day']} days"));

        while ($u = $users->fetch_assoc()) {
          $amount = floatval($policy['standard_amount']);
          $role = $u['role_name'] ?? 'Đoàn viên';
          if (isset($rules[$role])) {
            $amount = max(0, $amount - $rules[$role]);
          }

          // ✅ Kiểm tra trùng kỳ
          $check = $conn->prepare("SELECT id FROM fee_obligation WHERE user_id=? AND period_label=? LIMIT 1");
          $check->bind_param("is", $u['userId'], $cycle_label);
          $check->execute();
          $exists = $check->get_result()->num_rows > 0;

          if ($exists) {
            $total_failed++;
            continue;
          }

          // ✅ Sinh mã tham chiếu
          $reference = "DV-" . $u['identifyCard'] . "-" . $cycle_label;

          // ✅ Tạo bản ghi nghĩa vụ
          $insert = $conn->prepare("
            INSERT INTO fee_obligation (user_id, policy_id, period_label, amount, due_date, status, reference_code, created_at)
            VALUES (?, ?, ?, ?, ?, 'Chưa nộp', ?, NOW())
          ");
          $insert->bind_param("iisdss", $u['userId'], $policy_id, $cycle_label, $amount, $due_date, $reference);

          if ($insert->execute()) {
            $total_success++;
          } else {
            $total_failed++;
          }
        }

        // ✅ Ghi log
        $end_time = microtime(true);
        $runtime = round($end_time - $start_time, 2);

        $log = $conn->prepare("
          INSERT INTO fee_generation_log (policy_id, run_by, cycle_label, total_success, total_failed, run_time, note)
          VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        $note = "Sinh nghĩa vụ đoàn phí kỳ $cycle_label hoàn tất.";
        $log->bind_param("iisiss", $policy_id, $run_by, $cycle_label, $total_success, $total_failed, $note);
        $log->execute();

        $message = "<p class='success'>✅ Hoàn tất: $total_success thành công, $total_failed lỗi. (Thời gian: {$runtime}s)</p>";
      }
    }
  }
}

// ✅ Lấy danh sách chính sách khả dụng
$policies = $conn->query("SELECT id, policy_name, cycle, standard_amount FROM fee_policy WHERE status='Active'");
?>

<div class="container">
  <h2>⚙️ Sinh nghĩa vụ đoàn phí theo kỳ</h2>
  <?= $message ?>

  <form method="POST" class="form-generate">
    <div class="form-group">
      <label>Chọn chính sách đoàn phí:</label>
      <select name="policy_id" required>
        <option value="">-- Chọn chính sách --</option>
        <?php while ($p = $policies->fetch_assoc()): ?>
          <option value="<?= $p['id'] ?>">
            <?= htmlspecialchars($p['policy_name']) ?> (<?= $p['cycle'] ?> - <?= number_format($p['standard_amount'], 0) ?>đ)
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Nhập nhãn chu kỳ (VD: 01/2025):</label>
      <input type="text" name="cycle_label" placeholder="VD: 01/2025" required>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-generate">⚡ Sinh nghĩa vụ</button>
      <a href="dashboard.php" class="btn-back">⬅️ Quay lại</a>
    </div>
  </form>
</div>

<style>
.container {
  padding: 20px;
  margin-left: 240px;
  max-width: calc(100% - 300px);
}
h2 { text-align: center; color: #2d3436; margin-bottom: 20px; }
.form-generate {
  background: #fff;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.form-group { margin-bottom: 18px; }
label { font-weight: 600; display: block; margin-bottom: 6px; color: #333; }
input, select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 6px;
}
.form-actions {
  margin-top: 20px;
  display: flex;
  justify-content: space-between;
}
.btn-generate {
  background: linear-gradient(135deg, #6c5ce7, #a29bfe);
  color: white;
  border: none;
  padding: 10px 22px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}
.btn-generate:hover {
  background: linear-gradient(135deg, #5e56d6, #938df5);
}
.btn-back {
  background: #b2bec3;
  color: white;
  padding: 10px 20px;
  text-decoration: none;
  border-radius: 8px;
}
.error { color: #d63031; font-weight: bold; }
.success { color: #27ae60; font-weight: bold; }
</style>

<?php include("../includes/footer.php"); ?>
