-- =========================================
-- DATABASE: khaservice_it
-- Mục đích: Quản lý thiết bị IT & hệ thống bãi xe KHASERVICE
-- PHIÊN BẢN KHÔNG CÓ RÀNG BUỘC KHÓA NGOẠI (FLAT/NO FOREIGN KEYS)
-- =========================================

-- Tắt kiểm tra khóa ngoại để đảm bảo chèn dữ liệu dễ dàng
SET FOREIGN_KEY_CHECKS = 0;

-- 1. TẠO DATABASE
CREATE DATABASE IF NOT EXISTS khaservice_it
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE khaservice_it;

-- 2. TẠO CÁC BẢNG (KHÔNG CÓ RÀNG BUỘC FOREIGN KEY)

-- Bảng Danh sách dự án KHASERVICE
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_du_an VARCHAR(50) NOT NULL COMMENT 'Mã dự án nội bộ (VD: DA01, DA02)',
    ten_du_an VARCHAR(255) NOT NULL COMMENT 'Tên dự án / chung cư',
    dia_chi TEXT COMMENT 'Địa chỉ dự án',
    loai_du_an VARCHAR(100) COMMENT 'Loại dự án (Chung cư, Văn phòng...)',
    ghi_chu TEXT COMMENT 'Ghi chú thêm'
) COMMENT='Danh sách dự án KHASERVICE';

-- Bảng Nhà cung cấp thiết bị
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_npp VARCHAR(255) NOT NULL COMMENT 'Tên nhà phân phối / nhà thầu',
    nguoi_lien_he VARCHAR(255) COMMENT 'Tên người liên hệ',
    dien_thoai VARCHAR(50) COMMENT 'Số điện thoại',
    email VARCHAR(255) COMMENT 'Email liên hệ',
    ghi_chu TEXT COMMENT 'Ghi chú'
) COMMENT='Nhà cung cấp thiết bị';

-- Bảng Danh sách thiết bị & linh kiện
CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_tai_san VARCHAR(100) NOT NULL UNIQUE
        COMMENT 'Mã tài sản nội bộ (VD: KHAS-DA01-PC-001)',
    ten_thiet_bi VARCHAR(255) NOT NULL
        COMMENT 'Tên thiết bị (PC lễ tân, UPS barrier...)',
    nhom_thiet_bi VARCHAR(50) NOT NULL
        COMMENT 'Nhóm thiết bị: Văn phòng / Bãi xe',
    loai_thiet_bi VARCHAR(100) NOT NULL
        COMMENT 'Loại thiết bị: PC, UPS, Camera, Barrier, Đầu đọc thẻ...',
    model VARCHAR(255)
        COMMENT 'Model thiết bị',
    serial VARCHAR(255)
        COMMENT 'Serial number của thiết bị',
    project_id INT
        COMMENT 'Thiết bị thuộc dự án nào',
    supplier_id INT
        COMMENT 'Nhà cung cấp thiết bị',
    ngay_mua DATE
        COMMENT 'Ngày mua thiết bị',
    gia_mua DECIMAL(15,2)
        COMMENT 'Giá mua tại thời điểm mua (VNĐ)',
    bao_hanh_den DATE
        COMMENT 'Ngày hết hạn bảo hành',
    trang_thai VARCHAR(50) DEFAULT 'Đang sử dụng'
        COMMENT 'Trạng thái: Đang sử dụng / Hỏng / Thanh lý',
    ghi_chu TEXT
        COMMENT 'Ghi chú khác',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Ngày tạo hồ sơ thiết bị'
) COMMENT='Danh sách thiết bị & linh kiện';

-- Bảng File đính kèm thiết bị
CREATE TABLE device_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL
        COMMENT 'Thiết bị liên quan',
    loai_file VARCHAR(50) NOT NULL
        COMMENT 'Loại file: HoaDon / BienBan / HinhAnh',
    file_path VARCHAR(255) NOT NULL
        COMMENT 'Đường dẫn file lưu trên server',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Thời gian upload'
) COMMENT='File đính kèm thiết bị';

-- Bảng Lịch sử sửa chữa thiết bị
CREATE TABLE maintenance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL
        COMMENT 'Thiết bị bị sự cố',
    ngay_su_co DATE NOT NULL
        COMMENT 'Ngày xảy ra sự cố',
    noi_dung TEXT
        COMMENT 'Mô tả sự cố do dự án báo',
    hu_hong TEXT
        COMMENT 'Xác định hư hỏng sau kiểm tra',
    xu_ly TEXT
        COMMENT 'Hướng xử lý / sửa chữa',
    chi_phi DECIMAL(15,2)
        COMMENT 'Chi phí sửa chữa (nếu có)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Ngày ghi nhận biên bản'
) COMMENT='Lịch sử sửa chữa thiết bị';

-- Bảng Người dùng hệ thống
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL COMMENT 'Tên đăng nhập',
    password VARCHAR(255) NOT NULL COMMENT 'Mật khẩu (hash)',
    role VARCHAR(50) COMMENT 'Vai trò: admin / it / xem',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) COMMENT='Người dùng hệ thống';


-- 3. DỮ LIỆU THỬ NGHIỆM (TEST DATA)

-- Dữ liệu cho Bảng projects (Tổng: 10 bản ghi)
INSERT INTO projects (id, ma_du_an, ten_du_an, dia_chi, loai_du_an, ghi_chu) VALUES
(1, 'DA01', 'Chung cư Sóng Vàng', '345 Đường Lạc Long Quân, Quận 11, TP.HCM', 'Chung cư', 'Dự án thí điểm hệ thống bãi xe thông minh v1.0'),
(2, 'DA02', 'Tòa nhà Văn phòng Thiên Niên', '234 Đường Nguyễn Văn Cừ, Quận 5, TP.HCM', 'Văn phòng', 'Chỉ lắp đặt hệ thống IT nội bộ văn phòng'),
(3, 'DA03', 'Khu dân cư EcoPark', 'Thị trấn Văn Giang, Hưng Yên', 'Khu dân cư', 'Hệ thống an ninh và bãi xe mở rộng'),
(4, 'DA04', 'Nhà kho Logistics Thăng Long', 'Đường 5, Huyện Đông Anh, Hà Nội', 'Nhà kho', 'Chủ yếu lắp đặt hệ thống mạng và Camera'),
(5, 'DA05', 'Resort Xanh Cát', 'Thị trấn Lăng Cô, Thừa Thiên Huế', 'Khu nghỉ dưỡng', 'Hệ thống Wi-Fi diện rộng và Điện thoại IP'),
(6, 'DA06', 'Khu phức hợp Marina Bay', 'Quận 7, TP.HCM', 'Khu phức hợp', 'Hệ thống bãi xe và IT văn phòng cao cấp'),
(7, 'DA07', 'Chung cư Hồng Phát', 'Đường Phạm Văn Đồng, Thủ Đức, TP.HCM', 'Chung cư', 'Thiết bị cũ, cần nâng cấp dần'),
(8, 'DA08', 'Văn phòng Chi nhánh Đà Nẵng', 'Quận Hải Châu, Đà Nẵng', 'Văn phòng', 'Chỉ thiết bị văn phòng cơ bản'),
(9, 'DA09', 'Nhà máy Sản xuất Z', 'Khu công nghiệp VSIP II, Bình Dương', 'Nhà máy', 'Mạng lưới công nghiệp và máy tính sản xuất'),
(10, 'DA10', 'Dự án thí nghiệm R&D', 'Phòng Lab Trụ sở chính', 'Phòng nghiên cứu', 'Thiết bị Server và Storage chuyên dụng');

-- Dữ liệu cho Bảng suppliers (Tổng: 8 bản ghi)
INSERT INTO suppliers (id, ten_npp, nguoi_lien_he, dien_thoai, email, ghi_chu) VALUES
(1, 'Công ty CP Thiết bị An ninh A', 'Nguyễn Văn Hùng', '0901234567', 'hung.nv@anninha.com', 'Chuyên cung cấp Barrier và Đầu đọc thẻ'),
(2, 'Công ty TNHH Máy tính B', 'Lê Thị Nga', '0918765432', 'nga.lt@maytinhb.vn', 'Cung cấp PC, Laptop, UPS văn phòng'),
(3, 'Nhà thầu Giải pháp Camera C', 'Phạm Quốc Việt', '0987654321', 'viet.pq@camera-c.com', 'Lắp đặt và bảo trì hệ thống Camera giám sát'),
(4, 'Công ty Cung cấp Giải pháp Mạng D', 'Trần Văn Mạnh', '0945123789', 'manh.tv@network-d.com', 'Chuyên Router, Switch, AP'),
(5, 'Công ty TNHH Thiết bị Bãi xe H', 'Vũ Thị Thanh', '0978112233', 'thanh.vt@parking-h.com', 'Lắp đặt và bảo trì bãi xe, barrier'),
(6, 'Nhà Phân phối Server F', 'Đỗ Trung Kiên', '0932456789', 'kien.dt@server-f.com', 'Cung cấp Server, Storage'),
(7, 'Cửa hàng Sửa chữa G', 'Mai Văn Tấn', '0912345987', 'tan.mv@suachua-g.com', 'Đơn vị chuyên sửa chữa linh kiện và thiết bị cũ'),
(8, 'Đại lý Thiết bị Văn phòng I', 'Phan Văn Quý', '0983445566', 'quy.pv@office-i.vn', 'Cung cấp máy in, máy chiếu');


-- Dữ liệu cho Bảng devices (Tổng: 50 bản ghi)
INSERT INTO devices (id, ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, model, serial, project_id, supplier_id, ngay_mua, gia_mua, bao_hanh_den, trang_thai, ghi_chu) VALUES
(1, 'KHAS-DA01-BR-001', 'Barrier Lối Vào Chính', 'Bãi xe', 'Barrier', 'ZKTeco BGT220', 'SNBR220001', 1, 1, '2023-01-15', 18500000.00, '2025-01-15', 'Đang sử dụng', NULL),
(2, 'KHAS-DA01-UPS-001', 'UPS Barrier Lối Vào', 'Bãi xe', 'UPS', 'APC 1000VA', 'SNUPS1001', 1, 2, '2023-01-15', 3200000.00, '2024-01-15', 'Đang sử dụng', NULL),
(3, 'KHAS-DA02-PC-001', 'PC Kế toán', 'Văn phòng', 'PC', 'Dell OptiPlex 3000', 'SNDLPC3001', 2, 2, '2023-03-20', 12000000.00, '2026-03-20', 'Đang sử dụng', NULL),
(4, 'KHAS-DA02-SW-001', 'Switch Tầng 5', 'Văn phòng', 'Switch', 'Cisco 2960', 'SNCS296001', 2, 2, '2023-03-20', 8500000.00, '2024-03-20', 'Đang sử dụng', NULL),
(5, 'KHAS-DA03-CAM-005', 'Camera Lối Ra Hầm B1', 'Bãi xe', 'Camera', 'Hikvision DS-2CD2T87G2-L', 'SNHV87G205', 3, 3, '2023-11-01', 4800000.00, '2026-11-01', 'Đang sử dụng', NULL),
(6, 'KHAS-DA04-CAM-001', 'Camera Kho Lạnh', 'Bãi xe', 'Camera', 'Dahua IP67', 'SNDH67A01', 4, 3, '2024-02-10', 5500000.00, '2027-02-10', 'Đang sử dụng', NULL),
(7, 'KHAS-DA04-AP-001', 'Access Point Tầng 1', 'Văn phòng', 'Access Point', 'TP-Link EAP620', 'SNTEAP6201', 4, 4, '2024-02-10', 2800000.00, '2026-02-10', 'Đang sử dụng', NULL),
(8, 'KHAS-DA05-AP-010', 'Access Point Khu Bể Bơi', 'Văn phòng', 'Access Point', 'Ubiquiti U6-LR', 'SNU6LR010', 5, 4, '2023-10-01', 4500000.00, '2026-10-01', 'Đang sử dụng', NULL),
(9, 'KHAS-DA05-IP-020', 'Điện thoại IP Lễ tân', 'Văn phòng', 'Điện thoại IP', 'Yealink T46S', 'SNYET46S020', 5, 2, '2023-10-01', 3100000.00, '2024-10-01', 'Đang sử dụng', NULL),
(10, 'KHAS-DA05-SW-005', 'Switch Core Lối Đi', 'Văn phòng', 'Switch', 'Cisco Catalyst 3850', 'SNCS385005', 5, 4, '2023-10-01', 25000000.00, '2026-10-01', 'Đang sử dụng', NULL),
(11, 'KHAS-DA06-PC-005', 'PC Giám đốc', 'Văn phòng', 'PC', 'MacBook Pro M3', 'SNMBPM3005', 6, 2, '2024-04-15', 45000000.00, '2027-04-15', 'Đang sử dụng', 'Máy tính cá nhân của Ban Giám đốc'),
(12, 'KHAS-DA06-BR-002', 'Barrier VIP', 'Bãi xe', 'Barrier', 'Came G2500', 'SNCAMEG202', 6, 5, '2024-04-15', 22000000.00, '2026-04-15', 'Đang sử dụng', NULL),
(13, 'KHAS-DA07-PC-015', 'PC Bảo vệ Ca đêm', 'Văn phòng', 'PC', 'HP Compaq DC7900', 'SNHP790015', 7, 7, '2018-08-01', 5000000.00, '2019-08-01', 'Hỏng', 'Màn hình bị sọc, đã báo thanh lý'),
(14, 'KHAS-DA07-DR-001', 'Đầu đọc thẻ Thang máy', 'Bãi xe', 'Đầu đọc thẻ', 'Hikvision DS-K2604', 'SNHV260401', 7, 1, '2020-03-01', 1500000.00, '2021-03-01', 'Thanh lý', 'Đã thay thế bằng thiết bị mới'),
(15, 'KHAS-DA10-SRV-001', 'Server Chính (Ứng dụng)', 'Văn phòng', 'Server', 'Dell PowerEdge R760', 'SNDLPE7601', 10, 6, '2024-06-01', 95000000.00, '2029-06-01', 'Đang sử dụng', 'Máy chủ ảo hóa chính'),
(16, 'KHAS-DA01-DR-002', 'Đầu đọc thẻ Lối Ra', 'Bãi xe', 'Đầu đọc thẻ', 'ZK KR600E', 'SNZK600E02', 1, 1, '2023-01-15', 1800000.00, '2025-01-15', 'Đang sử dụng', NULL),
(17, 'KHAS-DA01-CAM-003', 'Camera Bãi xe Tầng 1', 'Bãi xe', 'Camera', 'Hikvision 4MP', 'SNHV4MP003', 1, 3, '2023-01-15', 3500000.00, '2026-01-15', 'Đang sử dụng', NULL),
(18, 'KHAS-DA02-NB-003', 'Laptop Marketing', 'Văn phòng', 'Laptop', 'Lenovo ThinkPad X1', 'SNLTX1003', 2, 2, '2023-08-01', 18000000.00, '2026-08-01', 'Đang sử dụng', NULL),
(19, 'KHAS-DA02-PR-001', 'Máy in Văn phòng', 'Văn phòng', 'Máy in', 'HP LaserJet Pro', 'SNHPLJ001', 2, 8, '2024-01-01', 4500000.00, '2025-01-01', 'Đang sử dụng', NULL),
(20, 'KHAS-DA03-BR-003', 'Barrier Lối Ra Phụ', 'Bãi xe', 'Barrier', 'FAAC B680H', 'SNFAACB603', 3, 5, '2023-11-01', 25000000.00, '2025-11-01', 'Đang sử dụng', NULL),
(21, 'KHAS-DA04-CAM-002', 'Camera Bốc Xếp', 'Bãi xe', 'Camera', 'Dahua IP67', 'SNDH67A02', 4, 3, '2024-02-10', 5500000.00, '2027-02-10', 'Đang sử dụng', NULL),
(22, 'KHAS-DA04-SW-002', 'Switch Tầng 2', 'Văn phòng', 'Switch', 'TP-Link SG108E', 'SNTSG108E2', 4, 4, '2024-02-10', 1200000.00, '2026-02-10', 'Đang sử dụng', NULL),
(23, 'KHAS-DA05-IP-021', 'Điện thoại IP Quản lý', 'Văn phòng', 'Điện thoại IP', 'Yealink VP59', 'SNYEV59021', 5, 2, '2023-10-01', 5800000.00, '2024-10-01', 'Đang sử dụng', NULL),
(24, 'KHAS-DA05-AP-011', 'Access Point Khu Vườn', 'Văn phòng', 'Access Point', 'Ubiquiti U6-Mesh', 'SNU6MESH011', 5, 4, '2023-10-01', 3500000.00, '2026-10-01', 'Đang sử dụng', NULL),
(25, 'KHAS-DA06-SW-003', 'Switch Tầng Hầm', 'Bãi xe', 'Switch', 'Cisco C9300', 'SNCSC93003', 6, 4, '2024-04-15', 35000000.00, '2027-04-15', 'Đang sử dụng', NULL),
(26, 'KHAS-DA06-PC-006', 'PC Lễ tân Chính', 'Văn phòng', 'PC', 'HP EliteDesk', 'SNHPE006', 6, 2, '2024-04-15', 15000000.00, '2027-04-15', 'Đang sử dụng', NULL),
(27, 'KHAS-DA07-NB-001', 'Laptop Cũ Nhân viên', 'Văn phòng', 'Laptop', 'Acer Aspire 5', 'SNACAS5001', 7, 7, '2019-01-01', 10000000.00, '2020-01-01', 'Hỏng', 'Không lên nguồn, chờ thanh lý'),
(28, 'KHAS-DA08-PC-001', 'PC Văn phòng DN', 'Văn phòng', 'PC', 'Dell OptiPlex 3050', 'SNDL305001', 8, 2, '2022-05-01', 11000000.00, '2025-05-01', 'Đang sử dụng', NULL),
(29, 'KHAS-DA08-PR-002', 'Máy in DN', 'Văn phòng', 'Máy in', 'Canon 2900', 'SNCN290002', 8, 8, '2022-05-01', 3000000.00, '2023-05-01', 'Đang sử dụng', NULL),
(30, 'KHAS-DA09-SW-005', 'Switch Công nghiệp', 'Văn phòng', 'Switch', 'Siemens Scalance', 'SNSCSCA005', 9, 4, '2023-07-01', 15000000.00, '2026-07-01', 'Đang sử dụng', NULL),
(31, 'KHAS-DA09-PC-010', 'Máy tính Sản xuất', 'Văn phòng', 'PC', 'HP ProDesk', 'SNHPPD010', 9, 2, '2023-07-01', 10500000.00, '2026-07-01', 'Đang sử dụng', NULL),
(32, 'KHAS-DA10-SRV-002', 'Server Backup', 'Văn phòng', 'Server', 'HPE ProLiant DL380', 'SNHPDL3802', 10, 6, '2024-06-01', 75000000.00, '2029-06-01', 'Đang sử dụng', NULL),
(33, 'KHAS-DA10-NAS-001', 'Thiết bị lưu trữ NAS', 'Văn phòng', 'Storage', 'Synology DS1821+', 'SNSY182101', 10, 6, '2024-06-01', 30000000.00, '2027-06-01', 'Đang sử dụng', NULL),
(34, 'KHAS-DA01-UPS-002', 'UPS Cho Camera Tầng 2', 'Bãi xe', 'UPS', 'Santak Blazer 2000', 'SNSTB20002', 1, 2, '2023-01-15', 5500000.00, '2024-01-15', 'Đang sử dụng', NULL),
(35, 'KHAS-DA01-BR-003', 'Barrier Lối Xe Máy', 'Bãi xe', 'Barrier', 'ZKTeco BGT220', 'SNBR220003', 1, 1, '2023-01-15', 18500000.00, '2025-01-15', 'Đang sử dụng', NULL),
(36, 'KHAS-DA02-NB-004', 'Laptop Nhân sự', 'Văn phòng', 'Laptop', 'Dell XPS 13', 'SNDLXPS004', 2, 2, '2024-02-01', 25000000.00, '2027-02-01', 'Đang sử dụng', NULL),
(37, 'KHAS-DA02-PC-004', 'PC Thiết kế', 'Văn phòng', 'PC', 'HP Z2 Mini G9', 'SNHPZ2G904', 2, 2, '2024-02-01', 32000000.00, '2027-02-01', 'Đang sử dụng', NULL),
(38, 'KHAS-DA03-CAM-006', 'Camera Cổng ra vào', 'Bãi xe', 'Camera', 'Dahua 4K', 'SNDH4K006', 3, 3, '2023-11-01', 6000000.00, '2026-11-01', 'Đang sử dụng', NULL),
(39, 'KHAS-DA03-UPS-003', 'UPS Cho Hệ thống Server', 'Văn phòng', 'UPS', 'Eaton 5P', 'SNET5P003', 3, 2, '2023-11-01', 12000000.00, '2024-11-01', 'Đang sử dụng', NULL),
(40, 'KHAS-DA05-IP-022', 'Điện thoại IP Phòng Khách', 'Văn phòng', 'Điện thoại IP', 'Fanvil X4U', 'SNFNVX4U022', 5, 2, '2023-10-01', 2500000.00, '2024-10-01', 'Đang sử dụng', NULL),
(41, 'KHAS-DA06-PC-007', 'PC Kỹ thuật', 'Văn phòng', 'PC', 'Dell Precision', 'SNDLPREC007', 6, 2, '2024-04-15', 28000000.00, '2027-04-15', 'Đang sử dụng', NULL),
(42, 'KHAS-DA06-DR-004', 'Đầu đọc thẻ Tầng 1', 'Bãi xe', 'Đầu đọc thẻ', 'HID iClass SE', 'SNHICLASS04', 6, 5, '2024-04-15', 4000000.00, '2026-04-15', 'Đang sử dụng', NULL),
(43, 'KHAS-DA07-PC-016', 'PC Cũ Trạm y tế', 'Văn phòng', 'PC', 'Lenovo ThinkCentre', 'SNTCTL16', 7, 7, '2018-08-01', 4500000.00, '2019-08-01', 'Hỏng', 'Đã tháo linh kiện, chờ thanh lý'),
(44, 'KHAS-DA08-NB-005', 'Laptop Giám sát', 'Văn phòng', 'Laptop', 'HP ProBook', 'SNHPPB005', 8, 2, '2022-05-01', 15000000.00, '2025-05-01', 'Đang sử dụng', NULL),
(45, 'KHAS-DA09-AP-006', 'Access Point Khu vực 2', 'Văn phòng', 'Access Point', 'TP-Link EAP225', 'SNTEAP2256', 9, 4, '2023-07-01', 1800000.00, '2025-07-01', 'Đang sử dụng', NULL),
(46, 'KHAS-DA09-PC-011', 'Máy tính Kiểm định', 'Văn phòng', 'PC', 'Dell OptiPlex 7070', 'SNDL707011', 9, 2, '2023-07-01', 13000000.00, '2026-07-01', 'Đang sử dụng', NULL),
(47, 'KHAS-DA10-UPS-004', 'UPS Server Rack', 'Văn phòng', 'UPS', 'APC Symmetra', 'SNAPCSYM04', 10, 2, '2024-06-01', 50000000.00, '2027-06-01', 'Đang sử dụng', NULL),
(48, 'KHAS-DA01-CAM-004', 'Camera Lối Ra', 'Bãi xe', 'Camera', 'Hikvision 4MP', 'SNHV4MP004', 1, 3, '2023-01-15', 3500000.00, '2026-01-15', 'Đang sử dụng', NULL),
(49, 'KHAS-DA02-SW-002', 'Switch Tầng 6', 'Văn phòng', 'Switch', 'Cisco 2960', 'SNCS296002', 2, 2, '2023-03-20', 8500000.00, '2024-03-20', 'Đang sử dụng', NULL),
(50, 'KHAS-DA03-DR-005', 'Đầu đọc thẻ Xe máy', 'Bãi xe', 'Đầu đọc thẻ', 'ZK KR600E', 'SNZK600E05', 3, 1, '2023-11-01', 1800000.00, '2025-11-01', 'Đang sử dụng', NULL);


-- Dữ liệu cho Bảng maintenance_logs (Tổng: 42 bản ghi)
INSERT INTO maintenance_logs (id, device_id, ngay_su_co, noi_dung, hu_hong, xu_ly, chi_phi) VALUES
(1, 1, '2024-10-05', 'Barrier đóng/mở chậm, đôi khi không nâng lên hết. Dự án báo lỗi từ 3 ngày trước.', 'Motor bị kẹt, cần bôi trơn và thay thế lò xo cũ.', 'Tiến hành vệ sinh motor, bôi trơn và thay mới lò xo. Kiểm tra cảm biến vòng từ.', 550000.00),
(2, 3, '2024-11-10', 'Máy tính tự khởi động lại liên tục khi sử dụng phần mềm kế toán. Nhiệt độ máy cao.', 'Lỗi RAM (Bad Sector), keo tản nhiệt CPU bị khô.', 'Thay thế thanh RAM 8GB (DDR4) và bôi keo tản nhiệt mới cho CPU. Kiểm tra lại hệ thống làm mát.', 950000.00),
(3, 1, '2024-04-01', 'Barrier hoạt động chậm lại, có tiếng kêu lớn khi nâng cần.', 'Thiếu dầu bôi trơn và cần siết lại một số ốc cố định.', 'Tiến hành bôi trơn toàn bộ hệ thống cơ, siết lại ốc. Vệ sinh cảm biến.', 0.00),
(4, 15, '2024-11-20', 'Server không truy cập được mạng nội bộ trong 10 phút.', 'Lỗi cấu hình IP trên Switch Core. Cáp mạng bị lỏng.', 'Cấu hình lại IP tĩnh, bấm lại đầu cáp mạng.', 0.00),
(5, 18, '2024-10-15', 'Laptop bị tràn bộ nhớ, máy chạy rất chậm.', 'Quá nhiều phần mềm khởi động cùng hệ thống. Ổ cứng gần đầy.', 'Vô hiệu hóa các ứng dụng khởi động không cần thiết. Dọn dẹp ổ đĩa.', 0.00),
(6, 13, '2024-11-25', 'PC Bảo vệ (cũ) không lên hình, quạt vẫn quay.', 'Card màn hình rời bị hỏng. Thiết bị đã hết bảo hành.', 'Đã thay thế bằng một card màn hình cũ còn tốt. Chi phí mua lại card cũ.', 400000.00),
(7, 35, '2024-12-05', 'Barrier (xe máy) không đọc được thẻ, đèn báo lỗi.', 'Đầu đọc thẻ bị hư hỏng do nước mưa.', 'Thay thế Đầu đọc thẻ mới (chi phí thấp).', 1500000.00),
(8, 36, '2024-12-10', 'Laptop bị virus và mất dữ liệu quan trọng.', 'Người dùng mở file đính kèm từ email lạ.', 'Quét và diệt virus, phục hồi dữ liệu từ bản sao lưu gần nhất.', 0.00),
(9, 3, '2024-12-15', 'PC Kế toán bị lỗi phông chữ khi in hóa đơn.', 'Lỗi driver máy in.', 'Cài đặt lại driver máy in mới nhất cho hệ điều hành.', 0.00),
(10, 38, '2024-12-16', 'Camera bị mờ, không xem được ban đêm.', 'Bề mặt kính bị bám bụi và mạng nhện.', 'Vệ sinh toàn bộ ống kính camera.', 0.00),
(11, 4, '2024-11-01', 'Switch tầng 5 bị ngắt kết nối tạm thời.', 'Nguồn điện chập chờn.', 'Ổn định lại nguồn điện.', 0.00),
(12, 5, '2024-11-05', 'Camera mờ ở một góc.', 'Điều chỉnh lại góc quay.', 'Xoay và cố định lại góc camera.', 0.00),
(13, 6, '2024-10-20', 'AP tầng 1 bị quá tải người dùng.', 'Cấu hình lại giới hạn băng thông.', 'Tăng cường băng thông cho AP.', 0.00),
(14, 8, '2024-10-25', 'AP khu bể bơi kết nối không ổn định.', 'Cáp mạng bị ăn mòn.', 'Thay thế đoạn cáp mạng ngoài trời.', 300000.00),
(15, 9, '2024-10-28', 'Điện thoại IP Lễ tân không gọi được số ngoại mạng.', 'Lỗi cấu hình SIP.', 'Cấu hình lại tài khoản SIP.', 0.00),
(16, 10, '2024-11-03', 'Switch core có báo lỗi port.', 'Port bị bám bụi.', 'Vệ sinh và thử nghiệm lại port.', 0.00),
(17, 32, '2024-11-15', 'Server backup báo dung lượng lưu trữ thấp.', 'Xóa các bản sao lưu cũ không cần thiết.', 'Dọn dẹp và tối ưu hóa dung lượng.', 0.00),
(18, 19, '2024-12-01', 'Máy in không kéo giấy.', 'Kẹt giấy ở khay 2.', 'Lấy giấy kẹt ra và vệ sinh khay giấy.', 0.00),
(19, 12, '2024-12-05', 'Barrier phụ không tự động hạ cần.', 'Cảm biến vòng từ bị hỏng.', 'Thay thế cảm biến vòng từ.', 800000.00),
(20, 11, '2024-12-08', 'PC Giám đốc bị treo đột ngột.', 'Nhiệt độ CPU quá cao.', 'Bổ sung quạt tản nhiệt phụ.', 250000.00),
(21, 12, '2024-12-10', 'Barrier VIP có tiếng kêu lạ.', 'Bôi trơn cơ cấu thủy lực.', 'Thực hiện bôi trơn định kỳ.', 0.00),
(22, 14, '2024-12-12', 'Đầu đọc thẻ thang máy không sáng đèn.', 'Cáp kết nối bị đứt.', 'Thay thế cáp kết nối.', 100000.00),
(23, 28, '2024-12-14', 'PC Văn phòng DN bị lỗi màn hình xanh.', 'Update driver Windows.', 'Cài đặt lại driver card màn hình.', 0.00),
(24, 29, '2024-12-15', 'Máy in Văn phòng DN hết mực.', 'Thay thế hộp mực.', 'Thay hộp mực mới (đã có sẵn).', 0.00),
(25, 30, '2024-12-16', 'Switch Công nghiệp bị lỗi giao tiếp.', 'Kiểm tra lại cấu hình cổng Profinet.', 'Điều chỉnh cấu hình theo tài liệu.', 0.00),
(26, 2, '2024-11-02', 'UPS Barrier lối vào báo pin yếu.', 'Chạy test battery, pin cần thay thế.', 'Đặt mua pin thay thế.', 0.00),
(27, 38, '2024-11-06', 'Camera cổng ra vào không có hình.', 'Cáp mạng bị chuột cắn.', 'Kéo lại cáp mạng mới.', 450000.00),
(28, 39, '2024-11-09', 'UPS Server báo quá tải.', 'Một số thiết bị không cần thiết cắm vào UPS.', 'Tháo bớt thiết bị không ưu tiên.', 0.00),
(29, 40, '2024-11-12', 'Điện thoại IP phòng khách có tiếng ồn.', 'Kiểm tra đường truyền Internet.', 'Nâng cấp đường truyền Internet.', 0.00),
(30, 41, '2024-11-18', 'PC Kỹ thuật mất kết nối mạng.', 'Lỏng cáp mạng RJ45.', 'Bấm lại đầu cáp mạng.', 0.00),
(31, 42, '2024-11-21', 'Đầu đọc thẻ tầng 1 bị lỗi đọc sai.', 'Vệ sinh bề mặt đọc thẻ.', 'Vệ sinh và kiểm tra lại firmware.', 0.00),
(32, 26, '2024-11-23', 'PC Hành chính bị lỗi font chữ.', 'Lỗi hệ điều hành.', 'Cài đặt lại font chữ chuẩn.', 0.00),
(33, 27, '2024-11-27', 'Laptop Lễ tân cũ bị nóng.', 'Làm sạch bụi bẩn ở quạt tản nhiệt.', 'Vệ sinh bên trong máy.', 0.00),
(34, 43, '2024-12-02', 'PC Cũ Trạm y tế không nhận USB.', 'Lỗi driver cổng USB.', 'Cài đặt lại driver chipset.', 0.00),
(35, 30, '2024-12-06', 'Switch Công nghiệp 2 bị đèn báo đỏ.', 'Quá nhiệt độ môi trường.', 'Lắp thêm quạt làm mát cho tủ rack.', 500000.00),
(36, 31, '2024-12-09', 'Máy tính Kho vật tư chậm.', 'Nhiều file rác.', 'Dọn dẹp ổ đĩa.', 0.00),
(37, 15, '2024-12-11', 'Server Database bị truy cập chậm.', 'Tối ưu hóa query database.', 'Chạy lệnh tối ưu hóa MySQL/SQL.', 0.00),
(38, 48, '2024-12-13', 'Camera Lối Ra bị mất tín hiệu.', 'Đường truyền bị nhiễu.', 'Kiểm tra lại đường dây tín hiệu.', 0.00),
(39, 49, '2024-12-17', 'Switch Tầng 6 bị treo.', 'Khởi động lại (reboot) switch.', 'Khởi động lại thủ công.', 0.00),
(40, 50, '2024-12-18', 'Đầu đọc thẻ xe máy bị lỗi phần mềm.', 'Cài đặt lại phần mềm quản lý.', 'Cập nhật firmware đầu đọc thẻ.', 0.00),
(41, 7, '2024-12-19', 'AP Khu vực đóng gói yếu sóng.', 'Điều chỉnh công suất phát sóng.', 'Tăng công suất phát AP.', 0.00),
(42, 40, '2024-12-20', 'Điện thoại IP Bar không nghe rõ.', 'Kiểm tra đường dây analog.', 'Thay thế dây nối cáp.', 50000.00);


-- Dữ liệu cho Bảng device_files (Tổng: 4 bản ghi)
INSERT INTO device_files (id, device_id, loai_file, file_path) VALUES
(1, 1, 'BienBan', '/files/DA01/BR-001/BB-LapDat-20230115.pdf'),
(2, 1, 'HinhAnh', '/files/DA01/BR-001/HinhAnh_BR.jpg'),
(3, 3, 'HoaDon', '/files/DA02/PC-001/HD-PC-20230320.pdf'),
(4, 5, 'HinhAnh', '/files/DA03/CAM-005/HinhAnh_CAM.png');


-- Dữ liệu cho Bảng users (Tổng: 3 bản ghi)
INSERT INTO users (id, username, password, role) VALUES
(1, 'admin', 'password_hash_admin', 'admin'),
(2, 'it_kh', 'password_hash_it', 'it'),
(3, 'xem_thietbi', 'password_hash_view', 'xem');

-- Bật lại kiểm tra khóa ngoại
SET FOREIGN_KEY_CHECKS = 1;