<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

//Chỉ cho phép quản trị viên
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
  echo "<div class='container'><p style='color:red;'>🚫 Bạn không có quyền truy cập chức năng này.</p></div>";
  include("../includes/footer.php");
  exit();
}

$message = "";
$total_success = 0;
$total_failed = 0;

//Khi admin bấm “Sinh nghĩa vụ đoàn phí”
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $cycle_label = trim($_POST['cycle_label'] ?? '');
  $policy_id = intval($_POST['policy_id'] ?? 0);
  $run_by = $_SESSION['user']['userId'];

  if (empty($cycle_label) || $policy_id <= 0) {
    $message = "<p class='error'>⚠️ Vui lòng chọn chính sách và nhập nhãn chu kỳ hợp lệ!</p>";
  } else {
    //Lấy chính sách đang kích hoạt
    $stmt = $conn->prepare("SELECT * FROM fee_policy WHERE id=? AND status='Active' LIMIT 1");
    if (!$stmt) die("❌ SQL Error (policy): " . $conn->error);
    $stmt->bind_param("i", $policy_id);
    $stmt->execute();
    $policy = $stmt->get_result()->fetch_assoc();

    if (!$policy) {
      $message = "<p class='error'>❌ Không tìm thấy chính sách đoàn phí đang kích hoạt.</p>";
    } else {
      //Lấy quy tắc miễn giảm
      $rules = [];
      $rquery = $conn->prepare("SELECT role_name, amount FROM fee_policy_rule WHERE policy_id=?");
      if (!$rquery) die("❌ SQL Error (rule): " . $conn->error);
      $rquery->bind_param("i", $policy_id);
      $rquery->execute();
      $rresult = $rquery->get_result();
      while ($r = $rresult->fetch_assoc()) {
        $rules[$r['role_name']] = floatval($r['amount']);
      }

      //Lấy danh sách đoàn viên
      $sql_users = "
        SELECT u.userId, u.fullName, u.identifyCard, COALESCE(r.role_name, 'Đoàn viên') AS role_name
        FROM users u
        LEFT JOIN user_role ur ON u.userId = ur.user_id
        LEFT JOIN role r ON ur.role_id = r.id
        WHERE r.role_name IN ('Đoàn viên', 'BCH Trường', 'BCH Khoa', 'BCH Chi đoàn')
      ";
      $users = $conn->query($sql_users);
      if (!$users) die("❌ SQL Error (users): " . $conn->error);

      if ($users->num_rows == 0) {
        $message = "<p class='error'>⚠️ Không có đoàn viên nào trong hệ thống.</p>";
      } else {
        //Tính hạn nộp theo chu kỳ
        if (!empty($policy['due_date'])) {
          $due_date = $policy['due_date'];
        } else {
          $month = date('n');
          $year = date('Y');
          switch ($policy['cycle']) {
            case 'Tháng':
              $due_date = date('Y-m-15');
              break;
            case 'Học kỳ':
              $due_date = ($month <= 6) ? "$year-04-15" : "$year-12-15";
              break;
            case 'Năm':
              $due_date = "$year-12-15";
              break;
            default:
              $due_date = date('Y-m-d', strtotime("+15 days"));
              break;
          }
        }

        //Sinh nghĩa vụ cho từng đoàn viên
        while ($u = $users->fetch_assoc()) {
          $amount = floatval($policy['standard_amount']);
          $role = $u['role_name'] ?? 'Đoàn viên';

          // Áp dụng quy tắc giảm
          if (isset($rules[$role])) {
            $amount = max(0, $amount - $rules[$role]);
          }

          // Kiểm tra trùng kỳ
          $check = $conn->prepare("SELECT id FROM fee_obligation WHERE user_id=? AND period_label=? LIMIT 1");
          if (!$check) die("❌ SQL Error (check): " . $conn->error);
          $check->bind_param("is", $u['userId'], $cycle_label);
          $check->execute();
          $exists = $check->get_result()->num_rows > 0;

          if ($exists) {
            $total_failed++;
            continue;
          }

          // Sinh mã tham chiếu
          $reference = "DV-" . $u['identifyCard'] . "-" . $cycle_label;

          // Thêm bản ghi nghĩa vụ
          $insert = $conn->prepare("
            INSERT INTO fee_obligation (user_id, policy_id, period_label, amount, due_date, status, reference_code, created_at)
            VALUES (?, ?, ?, ?, ?, 'Chưa nộp', ?, NOW())
          ");
          if (!$insert) die("❌ SQL Error (insert): " . $conn->error);
          $insert->bind_param("iisdss", $u['userId'], $policy_id, $cycle_label, $amount, $due_date, $reference);

          if ($insert->execute()) {
            $total_success++;
          } else {
            $total_failed++;
          }
        }

        //Ghi log quá trình (phù hợp cấu trúc bảng bạn cho)
        $note = "Sinh nghĩa vụ đoàn phí kỳ $cycle_label hoàn tất: $total_success thành công, $total_failed lỗi.";
        $log_time = date('Y-m-d H:i:s');

        $log = $conn->prepare("
          INSERT INTO fee_generation_log (policy_id, run_by, cycle_label, total_success, total_failed, run_time, note)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$log) die("❌ SQL Error (log): " . $conn->error);
        $log->bind_param("iisiiis", $policy_id, $run_by, $cycle_label, $total_success, $total_failed, $log_time, $note);
        $log->execute();

        $message = "<p class='success'>✅ Sinh nghĩa vụ đoàn phí thành công!<br>
        ✔️ $total_success thành công — ⚠️ $total_failed lỗi<br>
        🕒 Ghi log: $log_time</p>";
      }
    }
  }
}

//Lấy danh sách chính sách đang kích hoạt
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
        <?php while ($p = $policies && $p_row = $policies->fetch_assoc()): ?>
          <option value="<?= $p_row['id'] ?>">
            <?= htmlspecialchars($p_row['policy_name']) ?> (<?= $p_row['cycle'] ?> - <?= number_format($p_row['standard_amount'], 0, ',', '.') ?>đ)
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Nhập nhãn chu kỳ (VD: 01/2025):</label>
      <input type="text" name="cycle_label" placeholder="VD: 01/2025 hoặc HK1/2025" required>
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
.error { color: #d63031; font-weight: bold; text-align:center; }
.success { color: #27ae60; font-weight: bold; text-align:center; }
</style>

<?php include("../includes/footer.php"); ?>
