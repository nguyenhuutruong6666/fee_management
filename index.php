<?php
session_start();

// Nếu chưa đăng nhập → điều hướng đến trang login
if (!isset($_SESSION['user'])) {
    header("Location: pages/login.php");
    exit();
} else {
    // Nếu đã đăng nhập → chuyển thẳng vào dashboard
    header("Location: pages/dashboard.php");
    exit();
}
?>
