<?php
session_start();
include("../config/db.php");

// Kiểm tra quyền (chỉ admin được xóa)
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
    echo "<script>alert('Bạn không có quyền xóa người dùng!'); window.location.href='users.php';</script>";
    exit();
}

// Kiểm tra ID hợp lệ
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID người dùng không hợp lệ!'); window.location.href='users.php';</script>";
    exit();
}

$userId = intval($_GET['id']);

// Không cho xóa chính mình (để tránh tự xóa tài khoản admin đang đăng nhập)
if ($userId == $_SESSION['user']['userId']) {
    echo "<script>alert('Bạn không thể xóa tài khoản của chính mình!'); window.location.href='users.php';</script>";
    exit();
}

// Xóa dữ liệu liên quan trước (nếu có liên kết)
$conn->query("DELETE FROM user_role WHERE user_id = $userId");

// Xóa user khỏi bảng chính
if ($conn->query("DELETE FROM users WHERE userId = $userId")) {
    echo "<script>alert('Xóa người dùng thành công!'); window.location.href='users.php';</script>";
} else {
    echo "<script>alert('Lỗi khi xóa người dùng!'); window.location.href='users.php';</script>";
}
?>
