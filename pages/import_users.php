<?php
session_start();
include("../includes/header.php");
include("../includes/navbar.php");
include("../config/db.php");

// Chỉ Admin được vào
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
    echo "<div class='container'><p style='color:red;'>Bạn không có quyền truy cập trang này.</p></div>";
    include("../includes/footer.php");
    exit();
}

$message = "";
$previewData = [];

// Bước 1: Upload & xem trước file CSV
if (isset($_POST['preview'])) {
    if (is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        $file = fopen($_FILES['csv_file']['tmp_name'], "r");
        $header = fgetcsv($file); // dòng tiêu đề

        // Cột chuẩn CSV
        $expected = ["userName","fullName","email","identifyCard","gender","birthDate","joinDate","unit_name","password","role_name","isAdmin"];
        if ($header !== $expected) {
            $message = "<p class='error'>File CSV không đúng định dạng. Vui lòng tải lại <a href='../public/templates/users_template.csv'>file mẫu</a>.</p>";
        } else {
            while (($row = fgetcsv($file)) !== FALSE) {
                if (count($row) == count($expected)) {
                    $previewData[] = $row;
                }
            }
        }
        fclose($file);
    } else {
        $message = "<p class='error'>Bạn chưa chọn file CSV để tải lên.</p>";
    }
}

// Bước 2: Import xác nhận vào CSDL
if (isset($_POST['import_confirm'])) {
    $data = json_decode($_POST['data'], true);
    $success = 0;
    $fail = 0;
    $missingUnits = [];
    $existingUsers = [];

    foreach ($data as $row) {
        [$userName, $fullName, $email, $identifyCard, $gender, $birthDate, $joinDate, $unit_name, $password, $role_name, $isAdmin] = $row;
        $isAdmin = intval($isAdmin);

        // Kiểm tra trùng email hoặc CCCD
        $check = $conn->prepare("SELECT userId FROM users WHERE email=? OR identifyCard=?");
        $check->bind_param("ss", $email, $identifyCard);
        $check->execute();
        $res = $check->get_result();
        if ($res->num_rows > 0) {
            $existingUsers[] = $email;
            $fail++;
            continue;
        }

        // lấy role_id từ role_name
        $roleQuery = $conn->prepare("SELECT id FROM role WHERE role_name=? LIMIT 1");
        $roleQuery->bind_param("s", $role_name);
        $roleQuery->execute();
        $roleRes = $roleQuery->get_result();
        $role = $roleRes->fetch_assoc();
        $role_id = $role ? $role['id'] : null;

        // Lấy unit_id từ unit_name
        $unitQuery = $conn->prepare("SELECT id FROM organization_units WHERE unit_name=? LIMIT 1");
        $unitQuery->bind_param("s", $unit_name);
        $unitQuery->execute();
        $unitRes = $unitQuery->get_result();
        $unit = $unitRes->fetch_assoc();
        $unit_id = $unit ? $unit['id'] : null;

        if (!$unit_id) {
            $missingUnits[] = $unit_name;
            $fail++;
            continue;
        }

        if ($role_id && $unit_id) {
            $stmt = $conn->prepare("
                INSERT INTO users 
                    (userName, fullName, email, identifyCard, gender, birthDate, joinDate, unit, password, isAdmin, createdAt)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("sssssssssi", $userName, $fullName, $email, $identifyCard, $gender, $birthDate, $joinDate, $unit_id, $password, $isAdmin);
            if ($stmt->execute()) {
                $userId = $stmt->insert_id;
                $conn->query("INSERT INTO user_role (user_id, role_id, createdAt) VALUES ($userId, $role_id, NOW())");
                $success++;
            } else {
                $fail++;
            }
        } else {
            $fail++;
        }
    }

    // Thông báo kết quả
    $msg = "Import hoàn tất: $success thành công, $fail lỗi.";
    if (!empty($missingUnits)) $msg .= "\\nĐơn vị chưa tồn tại: " . implode(", ", array_unique($missingUnits));
    if (!empty($existingUsers)) $msg .= "\\nNgười dùng trùng email/CCCD: " . implode(", ", $existingUsers);

    echo "<script>alert('$msg'); window.location.href='users.php';</script>";
    exit();
}
?>

<div class="container">
  <h2>Import danh sách người dùng</h2>
  <?= $message ?>

  <form method="POST" enctype="multipart/form-data" class="form-import">
    <div class="form-group">
      <label>Chọn file CSV:</label>
      <input type="file" name="csv_file" accept=".csv" required>
    </div>

    <div class="form-actions">
      <button type="submit" name="preview" class="btn-preview">Xem trước</button>
      <a href="../public/templates/test.csv" class="btn-template" download>Tải file mẫu</a>
      <a href="users.php" class="btn-back">Quay lại</a>
    </div>
  </form>

  <?php if (!empty($previewData)): ?>
    <h3>Bản xem trước dữ liệu:</h3>
    <form method="POST">
      <input type="hidden" name="data" value='<?= json_encode($previewData) ?>'>
      <table class="table">
        <thead>
          <tr>
            <th>Tên đăng nhập</th><th>Họ tên</th><th>Email</th><th>CCCD/MSV</th>
            <th>Giới tính</th><th>Ngày sinh</th><th>Ngày vào Đoàn</th>
            <th>Đơn vị</th><th>Mật khẩu</th><th>Vai trò</th><th>Admin</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($previewData as $row): ?>
            <tr>
              <?php for ($i = 0; $i < count($row); $i++): ?>
                <td><?= htmlspecialchars($row[$i]) ?></td>
              <?php endfor; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <button type="submit" name="import_confirm" class="btn-save">Xác nhận Import</button>
    </form>
  <?php endif; ?>
</div>

<style>
.container { padding: 20px; margin-right: 1%; margin-left: 18%;}
.form-import { margin-bottom: 20px; }
.form-actions { margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap; }
input[type="file"] { border: 1px solid #ccc; padding: 8px; border-radius: 6px; }
.btn-preview, .btn-template, .btn-save, .btn-back {
  padding: 8px 15px; border-radius: 6px; color: #fff; text-decoration: none; border: none; cursor: pointer;
}
.btn-preview { background: #007bff; }
.btn-template { background: #00b894; }
.btn-save { background: #28a745; margin-top: 10px; }
.btn-back { background: #b2bec3; }
.table { width: 100%; border-collapse: collapse; margin-top: 15px; }
th, td { border: 1px solid #ddd; padding: 6px; text-align: center; }
th { background: #0984e3; color: white; }
tr:nth-child(even) { background: #f9f9f9; }
.error { color: #d63031; font-weight: bold; }
</style>

<?php include("../includes/footer.php"); ?>
