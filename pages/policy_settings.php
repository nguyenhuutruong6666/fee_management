<?php 
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// ========== Chỉ cho phép Quản trị viên truy cập ==========
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
  echo "<div class='container'><p style='color:red;'>🚫 Bạn không có quyền truy cập trang này.</p></div>";
  include("../includes/footer.php");
  exit();
}

$message = "";

// ========== Xử lý khi bấm Lưu chính sách ==========
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $policy_name = trim($_POST['policy_name'] ?? '');
  $cycle = $_POST['cycle'] ?? '';
  $due_date = $_POST['due_date'] ?? null;
  $standard_amount = floatval($_POST['standard_amount'] ?? 0);
  $status = 'Draft'; // ✅ Mặc định luôn là "Nháp"
  $discount_truong = floatval($_POST['discount_truong'] ?? 0);
  $discount_khoa = floatval($_POST['discount_khoa'] ?? 0);
  $discount_chidoan = floatval($_POST['discount_chidoan'] ?? 0);
  $created_by = $_SESSION['user']['userId'];

  if (empty($policy_name) || empty($cycle) || empty($due_date) || $standard_amount <= 0) {
    $message = "<p class='error'>⚠️ Vui lòng nhập đầy đủ thông tin hợp lệ!</p>";
  } else {
    // Lưu chính sách mới
    $stmt = $conn->prepare("
      INSERT INTO fee_policy (policy_name, cycle, due_date, standard_amount, status, created_by, created_at)
      VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    if (!$stmt) die("SQL Error (policy): " . $conn->error);
    $stmt->bind_param("sssdis", $policy_name, $cycle, $due_date, $standard_amount, $status, $created_by);

    if ($stmt->execute()) {
      $policy_id = $stmt->insert_id;

      // Thêm quy tắc miễn giảm
      $rules = [
        ['BCH Trường', $discount_truong],
        ['BCH Khoa', $discount_khoa],
        ['BCH Chi đoàn', $discount_chidoan]
      ];
      foreach ($rules as $rule) {
        list($role, $amount) = $rule;
        if ($amount > 0) {
          $r = $conn->prepare("
            INSERT INTO fee_policy_rule (policy_id, role_name, amount, created_at)
            VALUES (?, ?, ?, NOW())
          ");
          if (!$r) die("SQL Error (rule): " . $conn->error);
          $r->bind_param("isd", $policy_id, $role, $amount);
          $r->execute();
        }
      }

      // === TỰ ĐỘNG SINH CHU KỲ ===
      $year = date('Y');
      $periods = [];

      if ($cycle === 'Tháng') {
        // 12 kỳ tháng 1–12
        for ($m = 1; $m <= 12; $m++) {
          $label = sprintf("%02d/%s", $m, $year);
          $day = date('d', strtotime($due_date));
          $date = date('Y-m-d', strtotime("$year-$m-$day"));
          $periods[] = [$label, $date];
        }
      } elseif ($cycle === 'Học kỳ') {
        // 2 kỳ học: HK1 (15/12), HK2 (15/04)
        $periods[] = ["HK1/$year", "$year-12-15"];
        $periods[] = ["HK2/$year", "$year-04-15"];
      } elseif ($cycle === 'Năm') {
        // 4 năm học liên tiếp
        for ($i = 1; $i <= 4; $i++) {
          $label = "Năm $i/" . ($year + $i - 1);
          $date = ($year + $i - 1) . "-12-15";
          $periods[] = [$label, $date];
        }
      }

      // Ghi log lịch sử
      foreach ($periods as $p) {
        $h = $conn->prepare("
          INSERT INTO fee_policy_history (policy_id, applied_from, is_active, created_at)
          VALUES (?, ?, 0, NOW())
        ");
        if (!$h) die("SQL Error (history): " . $conn->error);
        $h->bind_param("is", $policy_id, $p[1]);
        $h->execute();
      }

      $message = "<p class='success'>✅ Chính sách đoàn phí đã được tạo và sinh chu kỳ tự động!<br>Trạng thái mặc định: <b>Nháp (Chưa kích hoạt)</b></p>";
    } else {
      $message = "<p class='error'>❌ Lỗi khi lưu chính sách. " . htmlspecialchars($conn->error) . "</p>";
    }
  }
}
?>

<div class="container">
  <h2>⚙️ Thiết lập chính sách đoàn phí</h2>
  <?= $message ?>
  <form method="POST" class="form-policy">
    <a href="manage_policy.php" class="btn-manage">📋 Quản lý chính sách</a>
    
    <div class="form-group">
      <label>Tên chính sách:</label>
      <input type="text" name="policy_name" placeholder="VD: Chính sách đoàn phí Học kỳ I - Năm 2025" required>
    </div>

    <div class="form-group">
      <label>Chu kỳ áp dụng:</label>
      <select name="cycle" id="cycle" required onchange="updateDueDateOptions()">
        <option value="">-- Chọn chu kỳ --</option>
        <option value="Tháng">Tháng</option>
        <option value="Học kỳ">Học kỳ</option>
        <option value="Năm">Năm</option>
      </select>
      <p class="note">🔸 Chu kỳ quyết định tần suất sinh nghĩa vụ (tháng, học kỳ hoặc năm học).</p>
    </div>

    <div class="form-group" id="dueDateContainer">
      <label>Hạn nộp (Due Date):</label>
      <input type="date" name="due_date" id="due_date" required>
      <p class="note" id="dueDateNote">📅 Chọn ngày hạn cụ thể.</p>
    </div>

    <div class="form-group">
      <label>Mức thu chuẩn (VNĐ):</label>
      <input type="number" name="standard_amount" min="0" step="100" placeholder="VD: 3000" required>
    </div>

    <div class="form-group">
      <label>Quy tắc miễn/giảm (VNĐ):</label>
      <div class="discount-group">
        <div><span>BCH Trường:</span> <input type="number" name="discount_truong" min="0" step="100" placeholder="1000"></div>
        <div><span>BCH Khoa:</span> <input type="number" name="discount_khoa" min="0" step="100" placeholder="2000"></div>
        <div><span>BCH Chi đoàn:</span> <input type="number" name="discount_chidoan" min="0" step="100" placeholder="2000"></div>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-save">💾 Lưu chính sách</button>
      <a href="dashboard.php" class="btn-back">⬅️ Quay lại</a>
    </div>
  </form>
</div>

<script>
function updateDueDateOptions() {
  const cycle = document.getElementById('cycle').value;
  const container = document.getElementById('dueDateContainer');

  if (cycle === 'Tháng') {
    container.innerHTML = `
      <label>Hạn nộp (Due Date):</label>
      <input type="date" name="due_date" id="due_date" required>
      <p class="note">📅 Chọn một ngày trong tháng (VD: 15). Hệ thống sẽ tự động tạo 12 kỳ từ tháng 1–12.</p>
    `;
  } else if (cycle === 'Học kỳ') {
    container.innerHTML = `
      <label>Hạn nộp (Due Date):</label>
      <select name="due_date" id="due_date" required>
        <option value="">-- Chọn hạn nộp --</option>
        <option value="${new Date().getFullYear()}-12-15">15/12 (Học kỳ I)</option>
        <option value="${new Date().getFullYear()}-04-15">15/04 (Học kỳ II)</option>
      </select>
      <p class="note">📅 HK1: 15/12 | HK2: 15/04</p>
    `;
  } else if (cycle === 'Năm') {
    container.innerHTML = `
      <label>Hạn nộp (Due Date):</label>
      <input type="text" name="due_date" id="due_date" value="${new Date().getFullYear()}-12-15" readonly>
      <p class="note">📅 Mặc định: 15/12 mỗi năm. Hệ thống sẽ tự động tạo 4 năm học.</p>
    `;
  } else {
    container.innerHTML = `
      <label>Hạn nộp (Due Date):</label>
      <input type="date" name="due_date" id="due_date" required>
    `;
  }
}
</script>

<style>
.container { padding: 20px; margin-left: 240px; max-width: calc(100% - 300px); }
h2 { text-align: center; color: #2d3436; margin-bottom: 20px; }
.form-policy { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.form-group { margin-bottom: 18px; }
label { font-weight: 600; display: block; margin-bottom: 6px; color: #333; }
input, select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; }
.note { font-size: 13px; color: #636e72; margin-top: 4px; }
.discount-group div { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
.discount-group span { min-width: 120px; display: inline-block; }
.form-actions { margin-top: 20px; display: flex; justify-content: space-between; gap: 10px; }
.btn-save { background: linear-gradient(135deg, #6c5ce7, #a29bfe); color: white; border: none; padding: 10px 22px; border-radius: 8px; cursor: pointer; font-weight: 600; }
.btn-save:hover { background: linear-gradient(135deg, #5e56d6, #938df5); }
.btn-manage { background: linear-gradient(135deg, #00b894, #00cec9); color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: 600; }
.btn-manage:hover { background: linear-gradient(135deg, #019875, #00b5ad); }
.btn-back { background: #b2bec3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; }
.error { color: #d63031; font-weight: bold; text-align: center; }
.success { color: #27ae60; font-weight: bold; text-align: center; }
</style>

<?php include("../includes/footer.php"); ?>
