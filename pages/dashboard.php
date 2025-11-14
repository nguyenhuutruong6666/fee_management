<?php
include("../includes/header.php");
include("../includes/navbar.php");

if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['user'];
?>

<div class="container">
  <h1>Trang chính</h1>
  <p>Chào mừng <?= htmlspecialchars($user['fullName']) ?> đến với hệ thống quản lý đoàn phí!</p>

  <div class="actions">
    <?php if (!$user['isAdmin']): ?>
      <a href="pay_fee.php" class="btn-pay">Nộp đoàn phí</a>
    <?php endif; ?>

    <?php if ($user['isAdmin']): ?>
      <a href="manage_transactions.php" class="btn-view">Xem giao dịch đoàn phí</a>
      <a href="policy_settings.php" class="btn-policy">Thiết lập chính sách đoàn phí</a>
      <a href="generate_fee_obligation.php" class="btn-policy">Sinh nghĩa vụ đoàn phí</a>
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
  color: #555;
  font-size: 16px;
}
.actions {
  margin-top: 30px;
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
}
.actions a {
  display: inline-block;
  padding: 14px 28px;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
}

/* 💰 Nộp đoàn phí */
.btn-pay {
  background: linear-gradient(135deg, #00b894, #00cec9);
  color: white;
  box-shadow: 0 4px 10px rgba(0, 206, 201, 0.3);
}
.btn-pay:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 14px rgba(0, 206, 201, 0.4);
}

/* 📜 Xem giao dịch */
.btn-view {
  background: linear-gradient(135deg, #0984e3, #74b9ff);
  color: white;
  box-shadow: 0 4px 10px rgba(9, 132, 227, 0.3);
}
.btn-view:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 14px rgba(9, 132, 227, 0.4);
}

/* ⚙️ Thiết lập chính sách đoàn phí */
.btn-policy {
  background: linear-gradient(135deg, #6c5ce7, #a29bfe);
  color: white;
  box-shadow: 0 4px 10px rgba(108, 92, 231, 0.3);
}
.btn-policy:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 14px rgba(108, 92, 231, 0.4);
}

@media (max-width: 768px) {
  .container {
    margin-left: 0;
    max-width: 100%;
  }
  .actions a {
    width: 100%;
    text-align: center;
  }
}
</style>

<?php include("../includes/footer.php"); ?>
