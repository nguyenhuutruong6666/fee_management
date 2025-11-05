<?php  
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

//Chỉ quản trị viên mới được truy cập
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
  echo "<div class='container'><p style='color:red;'>🚫 Bạn không có quyền truy cập trang này.</p></div>";
  include("../includes/footer.php");
  exit();
}

$message = "";

//XỬ LÝ LƯU CHÍNH SÁCH
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $policy_name = trim($_POST['policy_name'] ?? '');
  $cycle = $_POST['cycle'] ?? '';
  $due_day = intval($_POST['due_day'] ?? 0);
  $due_month = intval($_POST['due_month'] ?? 0);
  $standard_amount = floatval($_POST['standard_amount'] ?? 0);
  $discount_truong = floatval($_POST['discount_truong'] ?? 0);
  $discount_khoa = floatval($_POST['discount_khoa'] ?? 0);
  $discount_chidoan = floatval($_POST['discount_chidoan'] ?? 0);
  $created_by = $_SESSION['user']['userId'];
  $current_year = date('Y');
  $current_month = date('n');

  // ✅ Tính toán hạn nộp (due_date)
  if ($cycle === 'Tháng') {
    $due_date = sprintf("%04d-%02d-%02d", $current_year, $current_month, $due_day);
  } elseif ($cycle === 'Học kỳ' || $cycle === 'Năm') {
    $due_date = sprintf("%04d-%02d-%02d", $current_year, $due_month, $due_day);
  } else {
    $due_date = null;
  }

  // Kiểm tra dữ liệu đầu vào
  if (empty($policy_name) || empty($cycle) || !$due_date || $standard_amount <= 0) {
    $message = "<p class='error'>⚠️ Vui lòng nhập đầy đủ thông tin hợp lệ!</p>";
  } else {
    // ✅ Chèn chính sách mới — status mặc định trong DB là 'Draft'
    $stmt = $conn->prepare("
      INSERT INTO fee_policy (policy_name, cycle, due_date, standard_amount, created_by, created_at)
      VALUES (?, ?, ?, ?, ?, NOW())
    ");
    if (!$stmt) die("SQL Error: " . $conn->error);
    $stmt->bind_param("sssdi", $policy_name, $cycle, $due_date, $standard_amount, $created_by);

    if ($stmt->execute()) {
      $policy_id = $stmt->insert_id;

      //THÊM QUY TẮC MIỄN / GIẢM
      $rules = [
        ['BCH Trường', $discount_truong],
        ['BCH Khoa', $discount_khoa],
        ['BCH Chi đoàn', $discount_chidoan]
      ];
      foreach ($rules as $rule) {
        list($role, $amount) = $rule;
        if ($amount > 0) {
          $r = $conn->prepare("INSERT INTO fee_policy_rule (policy_id, role_name, amount, created_at) VALUES (?, ?, ?, NOW())");
          $r->bind_param("isd", $policy_id, $role, $amount);
          $r->execute();
        }
      }

      //GHI LOG LỊCH SỬ ÁP DỤNG CHÍNH SÁCH
      $periods = [];
      if ($cycle === 'Tháng') {
        // 12 tháng của năm hiện tại
        for ($m = 1; $m <= 12; $m++) {
          $periods[] = [$m, sprintf("%04d-%02d-01", $current_year, $m)];
        }
      } elseif ($cycle === 'Học kỳ') {
        // Học kỳ 1 và 2
        $periods[] = [1, sprintf("%04d-01-01", $current_year)];
        $periods[] = [2, sprintf("%04d-07-01", $current_year)];
      } elseif ($cycle === 'Năm') {
        // Một chu kỳ duy nhất
        $periods[] = [1, sprintf("%04d-01-01", $current_year)];
      }

      foreach ($periods as $p) {
        $h = $conn->prepare("
          INSERT INTO fee_policy_history (policy_id, applied_from, is_active, created_at)
          VALUES (?, ?, 0, NOW())
        ");
        if (!$h) die("SQL Error (history): " . $conn->error);
        $h->bind_param("is", $policy_id, $p[1]);
        $h->execute();
      }

      $message = "
        <p class='success'>
          ✅ Chính sách <b>" . htmlspecialchars($policy_name) . "</b> đã được tạo thành công!<br>
          Chu kỳ: <b>$cycle</b> — Hạn nộp: <b>" . date('d/m/Y', strtotime($due_date)) . "</b><br>
          🕒 Trạng thái mặc định: <b>Nháp (Draft)</b><br>
          🧾 Đã ghi log lịch sử áp dụng cho các kỳ tương ứng.
        </p>";
    } else {
      $message = "<p class='error'>❌ Lỗi khi lưu chính sách. Chi tiết: " . htmlspecialchars($conn->error) . "</p>";
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
      <input type="text" name="policy_name" placeholder="VD: Chính sách đoàn phí năm 2025" required>
    </div>

    <div class="form-group">
      <label>Chu kỳ áp dụng:</label>
      <select name="cycle" id="cycle" required onchange="renderDueDate()">
        <option value="">-- Chọn chu kỳ --</option>
        <option value="Tháng">Tháng</option>
        <option value="Học kỳ">Học kỳ</option>
        <option value="Năm">Năm</option>
      </select>
      <p class="note">🔸 Chu kỳ quyết định tần suất thu phí (Tháng, Học kỳ hoặc Năm).</p>
    </div>

    <!-- Vùng hiển thị hạn nộp động -->
    <div class="form-group" id="due_date_container"></div>

    <!-- Giữ nguyên cấu trúc gốc -->
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
//LOGIC CHU KỲ
function renderDueDate() {
  const cycle = document.getElementById('cycle').value;
  const container = document.getElementById('due_date_container');
  const now = new Date();
  const currentMonth = now.getMonth() + 1;
  const currentYear = now.getFullYear();

  if (cycle === "Tháng") {
    container.innerHTML = `
      <label>Hạn nộp (ngày trong tháng):</label>
      <input type="number" name="due_day" min="1" max="31" placeholder="VD: 15" required>
      <input type="hidden" name="due_month" value="${currentMonth}">
      <p class="note">📅 Hệ thống tự động lấy tháng ${currentMonth} và năm ${currentYear}.</p>
    `;
  } else if (cycle === "Học kỳ") {
    let monthOptions = "";
    if (currentMonth >= 1 && currentMonth <= 6) {
      for (let m = 1; m <= 6; m++) monthOptions += `<option value="${m}">${m}</option>`;
    } else {
      for (let m = 7; m <= 12; m++) monthOptions += `<option value="${m}">${m}</option>`;
    }
    container.innerHTML = `
      <label>Hạn nộp (ngày & tháng trong học kỳ):</label>
      <div style="display:flex; gap:10px;">
        <input type="number" name="due_day" min="1" max="31" placeholder="Ngày" required>
        <select name="due_month" required>${monthOptions}</select>
      </div>
      <p class="note">📅 Năm tự động là ${currentYear}. Chỉ chọn tháng trong học kỳ hiện tại.</p>
    `;
  } else if (cycle === "Năm") {
    let monthOptions = "";
    for (let m = 1; m <= 12; m++) monthOptions += `<option value="${m}">${m}</option>`;
    container.innerHTML = `
      <label>Hạn nộp (ngày & tháng trong năm):</label>
      <div style="display:flex; gap:10px;">
        <input type="number" name="due_day" min="1" max="31" placeholder="Ngày" required>
        <select name="due_month" required>${monthOptions}</select>
      </div>
      <p class="note">📅 Năm tự động là ${currentYear}.</p>
    `;
  } else {
    container.innerHTML = "";
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
