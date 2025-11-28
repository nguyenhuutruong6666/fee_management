<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");

if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['user'];
$user_role = $user['role_name'] ?? '';
?>

<div class="container">
  <h1>Quản lý hoạt động</h1>
  
  <div class="actions">
    <!-- BCH Chi đoàn & BCH Khoa: Đề xuất hoạt động -->
    <?php if (in_array($user_role, ['BCH Chi đoàn', 'BCH Khoa'])): ?>
      <a href="activity_proposal.php" class="btn-action">
        Đề xuất hoạt động
      </a>
    <?php endif; ?>

    <!-- BCH Khoa & BCH Trường: Phê duyệt hoạt động -->
    <?php if (in_array($user_role, ['BCH Khoa', 'BCH Trường'])): ?>
      <a href="activity_approval.php" class="btn-action">
        Phê duyệt hoạt động
      </a>
    <?php endif; ?>

    <!-- BCH Chi đoàn, Khoa, Trường: Tạm ứng / Chi tiền -->
    <?php if (in_array($user_role, ['BCH Chi đoàn', 'BCH Khoa', 'BCH Trường'])): ?>
      <a href="disbursement.php" class="btn-action">
        Tạm ứng / Chi tiền
      </a>
    <?php endif; ?>

    <!-- BCH Chi đoàn, Khoa, Trường: Lưu chứng từ -->
    <?php if (in_array($user_role, ['BCH Chi đoàn', 'BCH Khoa', 'BCH Trường'])): ?>
      <a href="save_activity_voucher.php" class="btn-action">
        Lưu chứng từ hoạt động
      </a>
    <?php endif; ?>

    <!-- BCH Khoa, Chi đoàn: Quyết toán -->
    <?php if (in_array($user_role, ['BCH Khoa', 'BCH Chi đoàn'])): ?>
      <a href="activity_settlement.php" class="btn-action">
        Quyết toán hoạt động
      </a>
    <?php endif; ?>

    <!-- BCH Trường & BCH Khoa: Duyệt & Khóa quyết toán -->
    <?php if (in_array($user_role, ['BCH Trường', 'BCH Khoa'])): ?>
      <a href="approve_activity_settlement.php" class="btn-action">
        Duyệt & Khóa quyết toán
      </a>
    <?php endif; ?>
  </div>
</div>

<style>
body {
  font-family: "Segoe UI", sans-serif;
  background: #f7f9fc;
}

.container {
  margin-left: 240px;
  max-width: calc(100% - 320px);
  padding: 40px 30px;
  transition: margin-left 0.3s ease;
}

h1 {
  color: #2d3436;
  margin-bottom: 10px;
}

p {
  color: #636e72;
  font-size: 16px;
  margin-bottom: 25px;
}

/* Vùng các nút chức năng */
.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

.btn-action {
  display: inline-block;
  flex: 1 1 250px;
  min-width: 250px;
  text-align: center;
  padding: 18px 25px;
  border-radius: 12px;
  color: white;
  text-decoration: none;
  font-weight: 600;
  font-size: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  transition: all 0.25s ease;
}

/* Màu ngẫu nhiên cho từng nút (gradient) */
.btn-action:nth-child(1) { background: linear-gradient(135deg, #00b894, #00cec9); }
.btn-action:nth-child(2) { background: linear-gradient(135deg, #0984e3, #74b9ff); }
.btn-action:nth-child(3) { background: linear-gradient(135deg, #f39c12, #f1c40f); color:#2d3436; }
.btn-action:nth-child(4) { background: linear-gradient(135deg, #6c5ce7, #a29bfe); }
.btn-action:nth-child(5) { background: linear-gradient(135deg, #2ecc71, #27ae60); }
.btn-action:nth-child(6) { background: linear-gradient(135deg, #d63031, #e17055); }
.btn-action:nth-child(7) { background: linear-gradient(135deg, #16a085, #1abc9c); }

.btn-action:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 18px rgba(0,0,0,0.15);
}

@media (max-width: 768px) {
  .container {
    margin-left: 0;
    max-width: 100%;
  }
  .btn-action {
    width: 100%;
  }
}
</style>

<?php include("../includes/footer.php"); ?>
