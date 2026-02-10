# Hướng dẫn Khắc phục lỗi nút "Lưu & Xuất bản"

## 🔍 Vấn đề chính được xác định và sửa chữa

### 1. **Lỗi tìm nút submit** ❌ → ✅
**Vấn đề**: JavaScript tìm nút submit theo cách sai dẫn đến null reference error
```javascript
// CŨ - SAI
const submitButton = document.querySelector(`button[form="${form.id}"]`);
const originalButtonText = submitButton.innerHTML; // Crash nếu null

// MỚI - ĐÚNG
const submitButton = form.querySelector('button[type="submit"]') || document.querySelector(`button[form="${form.id}"]`);
if (!submitButton) {
    showToast('Lỗi: Không tìm thấy nút submit.', 'error');
    return;
}
```

### 2. **Session cookies không được gửi** ❌ → ✅
**Vấn đề**: Khi gửi AJAX tới API, cookies phiên không được gửi kèm → API nhận `$_SESSION['user_id']` là null
```javascript
// CŨ - THIẾU CREDENTIALS
const response = await fetch(apiUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
    // ❌ Không gửi cookies!
});

// MỚI - CÓ CREDENTIALS
const response = await fetch(apiUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
    credentials: 'same-origin'  // ✅ Gửi session cookies
});
```

### 3. **Cấu trúc URL API không an toàn** ❌ → ✅
```javascript
// CŨ - Có thể tạo URL sai nếu finalBaseUrl không kết thúc bằng /
const apiUrl = finalBaseUrl + `public/api/forms_api.php?action=${apiAction}`;
// Kết quả có thể: http://localhost/khaservice-itpublic/api/forms_api.php (SAI)

// MỚI - An toàn
let baseUrl = finalBaseUrl.replace(/\/$/, ''); // Loại bỏ / cuối
const apiUrl = baseUrl + `/public/api/forms_api.php?action=${apiAction}`;
// Kết quả luôn: http://localhost/khaservice-it/public/api/forms_api.php (ĐÚNG)
```

### 4. **Thêm Logging để Debug** ❌ → ✅
```javascript
// Thêm các dòng debug log
console.log('API URL:', apiUrl);
console.log('Response status:', response.status);
console.log('API Result:', result);
console.error('Form submission error:', error);
```

---

## 🛠️ Cách Test trên Hosting

### Bước 1: Mở Chrome DevTools
1. Nhấn **F12** hoặc **Ctrl+Shift+I**
2. Vào tab **Console** để xem các thông báo lỗi

### Bước 2: Thử tạo biểu mẫu
1. Nhập tiêu đề biểu mẫu
2. Thêm ít nhất 1 câu hỏi
3. Nhấn "Lưu & Xuất bản"
4. Quan sát Console để xem:
   - `API URL: http://...` - Đường dẫn API
   - `Response status: 200` - Trạng thái HTTP
   - `API Result: {...}` - Phản hồi từ server

### Bước 3: Kiểm tra Network Tab
1. Vào tab **Network**
2. Nhấn "Lưu & Xuất bản"
3. Tìm request `forms_api.php?action=save_form`
4. Kiểm tra:
   - **Status**: Phải là 200 (không phải 401, 403, 404, 500)
   - **Headers → Cookies**: Phải có session cookie
   - **Response**: Phải là JSON hợp lệ

---

## 📋 File Debug Đã Tạo

**File**: `/public/api/debug_form_submission.php`

**Cách sử dụng:**
1. Truy cập: `http://your-domain.com/public/api/debug_form_submission.php`
2. Kiểm tra các thông tin:
   - ✅ Session có được bắt đầu không? (ACTIVE)
   - ✅ `user_id` có được set không?
   - ✅ Database có kết nối được không?
   - ✅ Các table có tồn tại không?

---

## ⚠️ Các Vấn đề Thông Thường Trên Hosting

| Vấn đề | Triệu chứng | Giải pháp |
|------|-----------|----------|
| **Session không được chia sẻ** | Lỗi "Bạn cần đăng nhập" | Kiểm tra session.save_path, session.cookie_domain |
| **Database không kết nối** | Lỗi "Lỗi máy chủ" | Kiểm tra config/db.php, thông tin kết nối DB |
| **Path sai** | Status 404 | Kiểm tra Apache DocumentRoot và cấu trúc thư mục |
| **CORS issue** | Request bị chặn | Thêm CORS headers vào API |
| **Permissions** | File không đọc được | Kiểm tra file permissions (755 cho thư mục) |

---

## 📝 Các Tệp đã Sửa

1. **`assets/js/form_builder.js`**
   - Sửa selector nút submit
   - Thêm credentials cho fetch
   - Cải thiện URL construction
   - Thêm error logging

2. **`public/api/debug_form_submission.php`** (NEW)
   - File debug để kiểm tra lỗi trên hosting

---

## 🔄 Quy Trình Kiểm Tra

```
1. Test trên localhost
   ↓
2. Upload lên hosting
   ↓
3. Mở F12 Console
   ↓
4. Thử lưu biểu mẫu
   ↓
5. Kiểm tra các log:
   - API URL có đúng không?
   - Response status 200?
   - Có session cookies không?
   ↓
6. Nếu còn lỗi:
   - Truy cập debug_form_submission.php
   - Kiểm tra session, database, files
   ↓
7. Nếu session null:
   - Kiểm tra config/db.php
   - Kiểm tra .htaccess hoặc cấu hình server
```

---

## 💡 Mẹo Addition

Nếu còn lỗi, hãy thêm vào `public/api/forms_api.php` dòng sau ở đầu:
```php
error_log("DEBUG: user_id = " . $_SESSION['user_id'] . ", action = " . $_GET['action']);
error_log("DEBUG: POST data = " . file_get_contents('php://input'));
```

Rồi kiểm tra file `error_log` của hosting để xem chi tiết.

---

## ✅ Kiểm Tra Sau Khi Sửa

- [ ] Nút "Lưu & Xuất bản" không còn "tê liệt"
- [ ] Console không có lỗi JavaScript
- [ ] Network tab hiển thị status 200
- [ ] Biểu mẫu được lưu vào database thành công
- [ ] Trang tự động chuyển hướng tới danh sách biểu mẫu
