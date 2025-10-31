<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hệ thống Quản lý Đoàn phí</title>
  <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
<header class="header">
  <div class="header-left">
    <h2>HỆ THỐNG QUẢN LÝ ĐOÀN PHÍ</h2>
  </div>
  <div class="header-right">
    <?php if(isset($_SESSION['user'])): ?>
      <span>👋 Xin chào, <b><?php echo htmlspecialchars($_SESSION['user']['userName']); ?></b></span>
      <a href="logout.php" class="btn-logout">Đăng xuất</a>
    <?php endif; ?>
  </div>
</header>
