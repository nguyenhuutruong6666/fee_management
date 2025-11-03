## 🧭 1. Cài đặt XAMPP
### Bước 1: Tải XAMPP
- Truy cập: 👉 [https://www.apachefriends.org/download.html](https://www.apachefriends.org/download.html)
- Chọn **XAMPP for Windows (PHP 8.2.x)** hoặc bản tương thích.

### Bước 2: Cài đặt
- Mở file cài đặt `.exe` vừa tải → Nhấn **Next** liên tục.  
- Giữ nguyên các lựa chọn mặc định:  
  - Apache ✔️  
  - MySQL ✔️  
  - PHPMyAdmin ✔️  
- Cài đặt xong → mở **XAMPP Control Panel**

### Bước 3: Khởi động dịch vụ
Mở **XAMPP Control Panel** → Bấm **Start** ở hai dòng:
- ✅ Apache  
- ✅ MySQL  

Nếu cả hai dòng chuyển màu xanh là thành công.

---

## 💾 2. Tải dự án PHP

### Bước 1: Tải mã nguồn dự án
- Nếu bạn có file nén (.zip / .rar): Giải nén ra thành một thư mục.  
  Ví dụ: `fee_management`

### Bước 2: Đặt dự án vào thư mục XAMPP
- Mở đường dẫn: C:\xampp\htdocs\
- Dán (hoặc giải nén) thư mục dự án của bạn vào đây: C:\xampp\htdocs\fee_management

## 🗃️ 3. Tạo và cấu hình cơ sở dữ liệu

### Bước 1: Truy cập PHPMyAdmin
- Mở trình duyệt và truy cập: http://localhost/phpmyadmin/

### Bước 2: Tạo cơ sở dữ liệu mới
- Trong menu bên trái → bấm **New**
- Ở ô **Database name**, nhập tên CSDL (ví dụ: `db_fee_management`)
- Chọn **Collation:** `utf8_general_ci`
- Nhấn **Create**

### Bước 3: Import dữ liệu SQL
- Chọn cơ sở dữ liệu vừa tạo (ví dụ: `db_fee_management`)
- Chuyển sang tab **Import**
- Bấm **Choose File / Chọn tệp**
- Chọn file `.sql` của dự án ( db_fee_management.sql ở folder fee_management nha )
- Nhấn **Go**

✅ Sau vài giây, hệ thống sẽ hiển thị thông báo:
> “Import has been successfully finished.”

## 🌐 4. Chạy dự án
- Mở trình duyệt và truy cập: http://localhost/fee_management/
