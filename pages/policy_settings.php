<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// Chỉ cho phép Quản trị viên truy cập
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
  echo "<div class='container'><p style='color:red;'>🚫 Bạn không có quyền truy cập trang này.</p></div>";
  include("../includes/footer.php");
  exit();
}

$message = "";

// Xử lý khi bấm Lưu chính sách
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $policy_name = trim($_POST['policy_name'] ?? '');
  $cycle = $_POST['cycle'] ?? '';
  $due_day = intval($_POST['due_day'] ?? 15);
  $due_type = $_POST['due_type'] ?? 'tháng';
  $standard_amount = floatval($_POST['standard_amount'] ?? 0);
  $status = $_POST['status'] ?? 'Draft';
  $discount_truong = floatval($_POST['discount_truong'] ?? 0);
  $discount_khoa = floatval($_POST['discount_khoa'] ?? 0);
  $discount_chidoan = floatval($_POST['discount_chidoan'] ?? 0);
  $created_by = $_SESSION['user']['userId'];

  if (empty($policy_name) || empty($cycle) || $standard_amount <= 0) {
    $message = "<p class='error'>⚠️ Vui lòng nhập đầy đủ thông tin hợp lệ!</p>";
  } else {
    // Kiểm tra trùng chính sách hiệu lực cùng chu kỳ
    $check = $conn->prepare("SELECT id FROM fee_policy WHERE cycle=? AND status='Active' LIMIT 1");
    $check->bind_param("s", $cycle);
    $check->execute();
    $checkResult = $check->get_result();

    if ($checkResult->num_rows > 0 && $status === 'Active') {
      $message = "<p class='error'>⚠️ Đã có chính sách đang hiệu lực cho chu kỳ này. Vui lòng chọn trạng thái 'Nháp'.</p>";
    } else {
      // Thêm vào bảng fee_policy
      $stmt = $conn->prepare("
        INSERT INTO fee_policy (policy_name, cycle, due_day, due_type, standard_amount, status, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
      ");
      $stmt->bind_param("ssisdsi", $policy_name, $cycle, $due_day, $due_type, $standard_amount, $status, $created_by);

      if ($stmt->execute()) {
        $policy_id = $stmt->insert_id;

        // Lưu quy tắc giảm phí vào fee_policy_rule
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

        // Lưu lịch sử áp dụng
        $h = $conn->prepare("
          INSERT INTO fee_policy_history (policy_id, applied_from, is_active, created_at)
          VALUES (?, CURDATE(), ?, NOW())
        ");
        $activeFlag = ($status === 'Active') ? 1 : 0;
        $h->bind_param("ii", $policy_id, $activeFlag);
        $h->execute();

        $message = "<p class='success'>✅ Chính sách đoàn phí đã được tạo thành công!</p>";
      } else {
        $message = "<p class='error'>❌ Lỗi khi lưu chính sách. Vui lòng thử lại!</p>";
      }
    }
  }
}
?>

<div class="container">
  <h2>⚙️ Thiết lập chính sách đoàn phí</h2>
  <?= $message ?>

  <form method="POST" class="form-policy">
    <div class="form-group">
      <label>Tên chính sách:</label>
      <input type="text" name="policy_name" placeholder="VD: Chính sách đoàn phí 2025 - Kỳ I" required>
    </div>

    <div class="form-group">
      <label>Chu kỳ áp dụng:</label>
      <select name="cycle" required>
        <option value="">-- Chọn chu kỳ --</option>
        <option value="Tháng">Tháng</option>
        <option value="Học kỳ">Học kỳ</option>
        <option value="Năm">Năm</option>
      </select>
    </div>

    <div class="form-group">
      <label>Ngày hạn nộp (VD: 15):</label>
      <input type="number" name="due_day" min="1" max="31" value="15" required>
    </div>

    <div class="form-group">
      <label>Đơn vị hạn nộp:</label>
      <select name="due_type">
        <option value="tháng">Tháng</option>
        <option value="học kỳ">Học kỳ</option>
        <option value="năm">Năm</option>
      </select>
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

    <div class="form-group">
      <label>Trạng thái:</label>
      <select name="status">
        <option value="Draft">Nháp</option>
        <option value="Active">Kích hoạt</option>
      </select>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-save">💾 Lưu chính sách</button>
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
h2 {
  text-align: center;
  color: #2d3436;
  margin-bottom: 20px;
}
.form-policy {
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
.discount-group div {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}
.discount-group span { min-width: 120px; display: inline-block; }
.form-actions {
  margin-top: 20px;
  display: flex;
  justify-content: space-between;
}
.btn-save {
  background: linear-gradient(135deg, #6c5ce7, #a29bfe);
  color: white;
  border: none;
  padding: 10px 22px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}
.btn-save:hover {
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
