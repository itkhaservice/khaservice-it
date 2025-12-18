-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 18, 2025 at 04:21 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `khaservice_it`
--

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `ma_tai_san` varchar(100) NOT NULL COMMENT 'Mã tài sản nội bộ (VD: KHAS-DA01-PC-001)',
  `ten_thiet_bi` varchar(255) NOT NULL COMMENT 'Tên thiết bị (PC lễ tân, UPS barrier...)',
  `nhom_thiet_bi` varchar(50) NOT NULL COMMENT 'Nhóm thiết bị: Văn phòng / Bãi xe',
  `loai_thiet_bi` varchar(100) NOT NULL COMMENT 'Loại thiết bị: PC, UPS, Camera, Barrier, Đầu đọc thẻ...',
  `model` varchar(255) DEFAULT NULL COMMENT 'Model thiết bị',
  `serial` varchar(255) DEFAULT NULL COMMENT 'Serial number của thiết bị',
  `project_id` int(11) DEFAULT NULL COMMENT 'Thiết bị thuộc dự án nào',
  `supplier_id` int(11) DEFAULT NULL COMMENT 'Nhà cung cấp thiết bị',
  `ngay_mua` date DEFAULT NULL COMMENT 'Ngày mua thiết bị',
  `gia_mua` decimal(15,2) DEFAULT NULL COMMENT 'Giá mua tại thời điểm mua (VNĐ)',
  `bao_hanh_den` date DEFAULT NULL COMMENT 'Ngày hết hạn bảo hành',
  `trang_thai` varchar(50) DEFAULT 'Đang sử dụng' COMMENT 'Trạng thái: Đang sử dụng / Hỏng / Thanh lý',
  `ghi_chu` text DEFAULT NULL COMMENT 'Ghi chú khác',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Ngày tạo hồ sơ thiết bị'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh sách thiết bị & linh kiện';

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`id`, `ma_tai_san`, `ten_thiet_bi`, `nhom_thiet_bi`, `loai_thiet_bi`, `model`, `serial`, `project_id`, `supplier_id`, `ngay_mua`, `gia_mua`, `bao_hanh_den`, `trang_thai`, `ghi_chu`, `created_at`) VALUES
(4, 'KHAS-DA02-SW-001', 'Switch Tầng 5', 'Văn phòng', 'Switch', 'Cisco 2960', 'SNCS296001', 2, 2, '2023-03-20', 8500000.00, '2024-03-20', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(5, 'KHAS-DA03-CAM-005', 'Camera Lối Ra Hầm B1', 'Bãi xe', 'Camera', 'Hikvision DS-2CD2T87G2-L', 'SNHV87G205', 3, 3, '2023-11-01', 4800000.00, '2026-11-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(6, 'KHAS-DA04-CAM-001', 'Camera Kho Lạnh', 'Bãi xe', 'Camera', 'Dahua IP67', 'SNDH67A01', 4, 3, '2024-02-10', 5500000.00, '2027-02-10', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(7, 'KHAS-DA04-AP-001', 'Access Point Tầng 1', 'Văn phòng', 'Access Point', 'TP-Link EAP620', 'SNTEAP6201', 4, 4, '2024-02-10', 2800000.00, '2026-02-10', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(8, 'KHAS-DA05-AP-010', 'Access Point Khu Bể Bơi', 'Văn phòng', 'Access Point', 'Ubiquiti U6-LR', 'SNU6LR010', 5, 4, '2023-10-01', 4500000.00, '2026-10-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(9, 'KHAS-DA05-IP-020', 'Điện thoại IP Lễ tân', 'Văn phòng', 'Điện thoại IP', 'Yealink T46S', 'SNYET46S020', 5, 2, '2023-10-01', 3100000.00, '2024-10-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(10, 'KHAS-DA05-SW-005', 'Switch Core Lối Đi', 'Văn phòng', 'Switch', 'Cisco Catalyst 3850', 'SNCS385005', 5, 4, '2023-10-01', 25000000.00, '2026-10-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(11, 'KHAS-DA06-PC-005', 'PC Giám đốc', 'Văn phòng', 'PC', 'MacBook Pro M3', 'SNMBPM3005', 6, 2, '2024-04-15', 45000000.00, '2027-04-15', 'Đang sử dụng', 'Máy tính cá nhân của Ban Giám đốc', '2025-12-17 05:00:27'),
(12, 'KHAS-DA06-BR-002', 'Barrier VIP', 'Bãi xe', 'Barrier', 'Came G2500', 'SNCAMEG202', 6, 5, '2024-04-15', 22000000.00, '2026-04-15', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(13, 'KHAS-DA07-PC-015', 'PC Bảo vệ Ca đêm', 'Văn phòng', 'PC', 'HP Compaq DC7900', 'SNHP790015', 7, 7, '2018-08-01', 5000000.00, '2019-08-01', 'Hỏng', 'Màn hình bị sọc, đã báo thanh lý', '2025-12-17 05:00:27'),
(14, 'KHAS-DA07-DR-001', 'Đầu đọc thẻ Thang máy', 'Bãi xe', 'Đầu đọc thẻ', 'Hikvision DS-K2604', 'SNHV260401', 7, 1, '2020-03-01', 1500000.00, '2021-03-01', 'Thanh lý', 'Đã thay thế bằng thiết bị mới', '2025-12-17 05:00:27'),
(15, 'KHAS-DA10-SRV-001', 'Server Chính (Ứng dụng)', 'Văn phòng', 'Server', 'Dell PowerEdge R760', 'SNDLPE7601', 10, 6, '2024-06-01', 95000000.00, '2029-06-01', 'Đang sử dụng', 'Máy chủ ảo hóa chính', '2025-12-17 05:00:27'),
(16, 'KHAS-DA01-DR-002', 'Đầu đọc thẻ Lối Ra', 'Bãi xe', 'Đầu đọc thẻ', 'ZK KR600E', 'SNZK600E02', 1, 1, '2023-01-15', 1800000.00, '2025-01-15', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(17, 'KHAS-DA01-CAM-003', 'Camera Bãi xe Tầng 1', 'Bãi xe', 'Camera', 'Hikvision 4MP', 'SNHV4MP003', 1, 3, '2023-01-15', 3500000.00, '2026-01-15', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(18, 'KHAS-DA02-NB-003', 'Laptop Marketing', 'Văn phòng', 'Laptop', 'Lenovo ThinkPad X1', 'SNLTX1003', 2, 2, '2023-08-01', 18000000.00, '2026-08-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(19, 'KHAS-DA02-PR-001', 'Máy in Văn phòng', 'Văn phòng', 'Máy in', 'HP LaserJet Pro', 'SNHPLJ001', 2, 8, '2024-01-01', 4500000.00, '2025-01-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(20, 'KHAS-DA03-BR-003', 'Barrier Lối Ra Phụ', 'Bãi xe', 'Barrier', 'FAAC B680H', 'SNFAACB603', 3, 5, '2023-11-01', 25000000.00, '2025-11-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(21, 'KHAS-DA04-CAM-002', 'Camera Bốc Xếp', 'Bãi xe', 'Camera', 'Dahua IP67', 'SNDH67A02', 4, 3, '2024-02-10', 5500000.00, '2027-02-10', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(22, 'KHAS-DA04-SW-002', 'Switch Tầng 2', 'Văn phòng', 'Switch', 'TP-Link SG108E', 'SNTSG108E2', 4, 4, '2024-02-10', 1200000.00, '2026-02-10', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(23, 'KHAS-DA05-IP-021', 'Điện thoại IP Quản lý', 'Văn phòng', 'Điện thoại IP', 'Yealink VP59', 'SNYEV59021', 5, 2, '2023-10-01', 5800000.00, '2024-10-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(24, 'KHAS-DA05-AP-011', 'Access Point Khu Vườn', 'Văn phòng', 'Access Point', 'Ubiquiti U6-Mesh', 'SNU6MESH011', 5, 4, '2023-10-01', 3500000.00, '2026-10-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(25, 'KHAS-DA06-SW-003', 'Switch Tầng Hầm', 'Bãi xe', 'Switch', 'Cisco C9300', 'SNCSC93003', 6, 4, '2024-04-15', 35000000.00, '2027-04-15', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(26, 'KHAS-DA06-PC-006', 'PC Lễ tân Chính', 'Văn phòng', 'PC', 'HP EliteDesk', 'SNHPE006', 6, 2, '2024-04-15', 15000000.00, '2027-04-15', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(27, 'KHAS-DA07-NB-001', 'Laptop Cũ Nhân viên', 'Văn phòng', 'Laptop', 'Acer Aspire 5', 'SNACAS5001', 7, 7, '2019-01-01', 10000000.00, '2020-01-01', 'Hỏng', 'Không lên nguồn, chờ thanh lý', '2025-12-17 05:00:27'),
(28, 'KHAS-DA08-PC-001', 'PC Văn phòng DN', 'Văn phòng', 'PC', 'Dell OptiPlex 3050', 'SNDL305001', 8, 2, '2022-05-01', 11000000.00, '2025-05-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(29, 'KHAS-DA08-PR-002', 'Máy in DN', 'Văn phòng', 'Máy in', 'Canon 2900', 'SNCN290002', 8, 8, '2022-05-01', 3000000.00, '2023-05-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(30, 'KHAS-DA09-SW-005', 'Switch Công nghiệp', 'Văn phòng', 'Switch', 'Siemens Scalance', 'SNSCSCA005', 9, 4, '2023-07-01', 15000000.00, '2026-07-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(31, 'KHAS-DA09-PC-010', 'Máy tính Sản xuất', 'Văn phòng', 'PC', 'HP ProDesk', 'SNHPPD010', 9, 2, '2023-07-01', 10500000.00, '2026-07-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(32, 'KHAS-DA10-SRV-002', 'Server Backup', 'Văn phòng', 'Server', 'HPE ProLiant DL380', 'SNHPDL3802', 10, 6, '2024-06-01', 75000000.00, '2029-06-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(33, 'KHAS-DA10-NAS-001', 'Thiết bị lưu trữ NAS', 'Văn phòng', 'Storage', 'Synology DS1821+', 'SNSY182101', 10, 6, '2024-06-01', 30000000.00, '2027-06-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(34, 'KHAS-DA01-UPS-002', 'UPS Cho Camera Tầng 2', 'Bãi xe', 'UPS', 'Santak Blazer 2000', 'SNSTB20002', 1, 2, '2023-01-15', 5500000.00, '2024-01-15', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(35, 'KHAS-DA01-BR-003', 'Barrier Lối Xe Máy', 'Bãi xe', 'Barrier', 'ZKTeco BGT220', 'SNBR220003', 1, 1, '2023-01-15', 18500000.00, '2025-01-15', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(36, 'KHAS-DA02-NB-004', 'Laptop Nhân sự', 'Văn phòng', 'Laptop', 'Dell XPS 13', 'SNDLXPS004', 2, 2, '2024-02-01', 25000000.00, '2027-02-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(37, 'KHAS-DA02-PC-004', 'PC Thiết kế', 'Văn phòng', 'PC', 'HP Z2 Mini G9', 'SNHPZ2G904', 2, 2, '2024-02-01', 32000000.00, '2027-02-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(38, 'KHAS-DA03-CAM-006', 'Camera Cổng ra vào', 'Bãi xe', 'Camera', 'Dahua 4K', 'SNDH4K006', 3, 3, '2023-11-01', 6000000.00, '2026-11-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(39, 'KHAS-DA03-UPS-003', 'UPS Cho Hệ thống Server', 'Văn phòng', 'UPS', 'Eaton 5P', 'SNET5P003', 3, 2, '2023-11-01', 12000000.00, '2024-11-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(40, 'KHAS-DA05-IP-022', 'Điện thoại IP Phòng Khách', 'Văn phòng', 'Điện thoại IP', 'Fanvil X4U', 'SNFNVX4U022', 5, 2, '2023-10-01', 2500000.00, '2024-10-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(41, 'KHAS-DA06-PC-007', 'PC Kỹ thuật', 'Văn phòng', 'PC', 'Dell Precision', 'SNDLPREC007', 6, 2, '2024-04-15', 28000000.00, '2027-04-15', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(42, 'KHAS-DA06-DR-004', 'Đầu đọc thẻ Tầng 1', 'Bãi xe', 'Đầu đọc thẻ', 'HID iClass SE', 'SNHICLASS04', 6, 5, '2024-04-15', 4000000.00, '2026-04-15', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(43, 'KHAS-DA07-PC-016', 'PC Cũ Trạm y tế', 'Văn phòng', 'PC', 'Lenovo ThinkCentre', 'SNTCTL16', 7, 7, '2018-08-01', 4500000.00, '2019-08-01', 'Hỏng', 'Đã tháo linh kiện, chờ thanh lý', '2025-12-17 05:00:27'),
(44, 'KHAS-DA08-NB-005', 'Laptop Giám sát', 'Văn phòng', 'Laptop', 'HP ProBook', 'SNHPPB005', 8, 2, '2022-05-01', 15000000.00, '2025-05-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(45, 'KHAS-DA09-AP-006', 'Access Point Khu vực 2', 'Văn phòng', 'Access Point', 'TP-Link EAP225', 'SNTEAP2256', 9, 4, '2023-07-01', 1800000.00, '2025-07-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(46, 'KHAS-DA09-PC-011', 'Máy tính Kiểm định', 'Văn phòng', 'PC', 'Dell OptiPlex 7070', 'SNDL707011', 9, 2, '2023-07-01', 13000000.00, '2026-07-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(47, 'KHAS-DA10-UPS-004', 'UPS Server Rack', 'Văn phòng', 'UPS', 'APC Symmetra', 'SNAPCSYM04', 10, 2, '2024-06-01', 50000000.00, '2027-06-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(48, 'KHAS-DA01-CAM-004', 'Camera Lối Ra', 'Bãi xe', 'Camera', 'Hikvision 4MP', 'SNHV4MP004', 1, 3, '2023-01-15', 3500000.00, '2026-01-15', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(49, 'KHAS-DA02-SW-002', 'Switch Tầng 6', 'Văn phòng', 'Switch', 'Cisco 2960', 'SNCS296002', 2, 2, '2023-03-20', 8500000.00, '2024-03-20', 'Đang sử dụng', NULL, '2025-12-17 05:00:27'),
(50, 'KHAS-DA03-DR-005', 'Đầu đọc thẻ Xe máy', 'Bãi xe', 'Đầu đọc thẻ', 'ZK KR600E', 'SNZK600E05', 3, 1, '2023-11-01', 1800000.00, '2025-11-01', 'Đang sử dụng', NULL, '2025-12-17 05:00:27');

-- --------------------------------------------------------

--
-- Table structure for table `device_files`
--

CREATE TABLE `device_files` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL COMMENT 'Thiết bị liên quan',
  `loai_file` varchar(50) NOT NULL COMMENT 'Loại file: HoaDon / BienBan / HinhAnh',
  `file_path` varchar(255) NOT NULL COMMENT 'Đường dẫn file lưu trên server',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Thời gian upload'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='File đính kèm thiết bị';

--
-- Dumping data for table `device_files`
--

INSERT INTO `device_files` (`id`, `device_id`, `loai_file`, `file_path`, `uploaded_at`) VALUES
(4, 5, 'HinhAnh', '/files/DA03/CAM-005/HinhAnh_CAM.png', '2025-12-17 05:00:27');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_logs`
--

CREATE TABLE `maintenance_logs` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL COMMENT 'Thiết bị bị sự cố',
  `ngay_su_co` date NOT NULL COMMENT 'Ngày xảy ra sự cố',
  `noi_dung` text DEFAULT NULL COMMENT 'Mô tả sự cố do dự án báo',
  `hu_hong` text DEFAULT NULL COMMENT 'Xác định hư hỏng sau kiểm tra',
  `xu_ly` text DEFAULT NULL COMMENT 'Hướng xử lý / sửa chữa',
  `chi_phi` decimal(15,2) DEFAULT NULL COMMENT 'Chi phí sửa chữa (nếu có)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Ngày ghi nhận biên bản'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lịch sử sửa chữa thiết bị';

--
-- Dumping data for table `maintenance_logs`
--

INSERT INTO `maintenance_logs` (`id`, `device_id`, `ngay_su_co`, `noi_dung`, `hu_hong`, `xu_ly`, `chi_phi`, `created_at`) VALUES
(4, 15, '2024-11-20', 'Server không truy cập được mạng nội bộ trong 10 phút.', 'Lỗi cấu hình IP trên Switch Core. Cáp mạng bị lỏng.', 'Cấu hình lại IP tĩnh, bấm lại đầu cáp mạng.', 0.00, '2025-12-17 05:00:27'),
(5, 18, '2024-10-15', 'Laptop bị tràn bộ nhớ, máy chạy rất chậm.', 'Quá nhiều phần mềm khởi động cùng hệ thống. Ổ cứng gần đầy.', 'Vô hiệu hóa các ứng dụng khởi động không cần thiết. Dọn dẹp ổ đĩa.', 0.00, '2025-12-17 05:00:27'),
(6, 13, '2024-11-25', 'PC Bảo vệ (cũ) không lên hình, quạt vẫn quay.', 'Card màn hình rời bị hỏng. Thiết bị đã hết bảo hành.', 'Đã thay thế bằng một card màn hình cũ còn tốt. Chi phí mua lại card cũ.', 400000.00, '2025-12-17 05:00:27'),
(7, 35, '2024-12-05', 'Barrier (xe máy) không đọc được thẻ, đèn báo lỗi.', 'Đầu đọc thẻ bị hư hỏng do nước mưa.', 'Thay thế Đầu đọc thẻ mới (chi phí thấp).', 1500000.00, '2025-12-17 05:00:27'),
(8, 36, '2024-12-10', 'Laptop bị virus và mất dữ liệu quan trọng.', 'Người dùng mở file đính kèm từ email lạ.', 'Quét và diệt virus, phục hồi dữ liệu từ bản sao lưu gần nhất.', 0.00, '2025-12-17 05:00:27'),
(10, 38, '2024-12-16', 'Camera bị mờ, không xem được ban đêm.', 'Bề mặt kính bị bám bụi và mạng nhện.', 'Vệ sinh toàn bộ ống kính camera.', 0.00, '2025-12-17 05:00:27'),
(11, 4, '2024-11-01', 'Switch tầng 5 bị ngắt kết nối tạm thời.', 'Nguồn điện chập chờn.', 'Ổn định lại nguồn điện.', 0.00, '2025-12-17 05:00:27'),
(12, 5, '2024-11-05', 'Camera mờ ở một góc.', 'Điều chỉnh lại góc quay.', 'Xoay và cố định lại góc camera.', 0.00, '2025-12-17 05:00:27'),
(13, 6, '2024-10-20', 'AP tầng 1 bị quá tải người dùng.', 'Cấu hình lại giới hạn băng thông.', 'Tăng cường băng thông cho AP.', 0.00, '2025-12-17 05:00:27'),
(14, 8, '2024-10-25', 'AP khu bể bơi kết nối không ổn định.', 'Cáp mạng bị ăn mòn.', 'Thay thế đoạn cáp mạng ngoài trời.', 300000.00, '2025-12-17 05:00:27'),
(15, 9, '2024-10-28', 'Điện thoại IP Lễ tân không gọi được số ngoại mạng.', 'Lỗi cấu hình SIP.', 'Cấu hình lại tài khoản SIP.', 0.00, '2025-12-17 05:00:27'),
(16, 10, '2024-11-03', 'Switch core có báo lỗi port.', 'Port bị bám bụi.', 'Vệ sinh và thử nghiệm lại port.', 0.00, '2025-12-17 05:00:27'),
(17, 32, '2024-11-15', 'Server backup báo dung lượng lưu trữ thấp.', 'Xóa các bản sao lưu cũ không cần thiết.', 'Dọn dẹp và tối ưu hóa dung lượng.', 0.00, '2025-12-17 05:00:27'),
(18, 19, '2024-12-01', 'Máy in không kéo giấy.', 'Kẹt giấy ở khay 2.', 'Lấy giấy kẹt ra và vệ sinh khay giấy.', 0.00, '2025-12-17 05:00:27'),
(19, 12, '2024-12-05', 'Barrier phụ không tự động hạ cần.', 'Cảm biến vòng từ bị hỏng.', 'Thay thế cảm biến vòng từ.', 800000.00, '2025-12-17 05:00:27'),
(20, 11, '2024-12-08', 'PC Giám đốc bị treo đột ngột.', 'Nhiệt độ CPU quá cao.', 'Bổ sung quạt tản nhiệt phụ.', 250000.00, '2025-12-17 05:00:27'),
(21, 12, '2024-12-10', 'Barrier VIP có tiếng kêu lạ.', 'Bôi trơn cơ cấu thủy lực.', 'Thực hiện bôi trơn định kỳ.', 0.00, '2025-12-17 05:00:27'),
(22, 14, '2024-12-12', 'Đầu đọc thẻ thang máy không sáng đèn.', 'Cáp kết nối bị đứt.', 'Thay thế cáp kết nối.', 100000.00, '2025-12-17 05:00:27'),
(23, 28, '2024-12-14', 'PC Văn phòng DN bị lỗi màn hình xanh.', 'Update driver Windows.', 'Cài đặt lại driver card màn hình.', 0.00, '2025-12-17 05:00:27'),
(24, 29, '2024-12-15', 'Máy in Văn phòng DN hết mực.', 'Thay thế hộp mực.', 'Thay hộp mực mới (đã có sẵn).', 0.00, '2025-12-17 05:00:27'),
(25, 30, '2024-12-16', 'Switch Công nghiệp bị lỗi giao tiếp.', 'Kiểm tra lại cấu hình cổng Profinet.', 'Điều chỉnh cấu hình theo tài liệu.', 0.00, '2025-12-17 05:00:27'),
(27, 38, '2024-11-06', 'Camera cổng ra vào không có hình.', 'Cáp mạng bị chuột cắn.', 'Kéo lại cáp mạng mới.', 450000.00, '2025-12-17 05:00:27'),
(28, 39, '2024-11-09', 'UPS Server báo quá tải.', 'Một số thiết bị không cần thiết cắm vào UPS.', 'Tháo bớt thiết bị không ưu tiên.', 0.00, '2025-12-17 05:00:27'),
(29, 40, '2024-11-12', 'Điện thoại IP phòng khách có tiếng ồn.', 'Kiểm tra đường truyền Internet.', 'Nâng cấp đường truyền Internet.', 0.00, '2025-12-17 05:00:27'),
(30, 41, '2024-11-18', 'PC Kỹ thuật mất kết nối mạng.', 'Lỏng cáp mạng RJ45.', 'Bấm lại đầu cáp mạng.', 0.00, '2025-12-17 05:00:27'),
(31, 42, '2024-11-21', 'Đầu đọc thẻ tầng 1 bị lỗi đọc sai.', 'Vệ sinh bề mặt đọc thẻ.', 'Vệ sinh và kiểm tra lại firmware.', 0.00, '2025-12-17 05:00:27'),
(32, 26, '2024-11-23', 'PC Hành chính bị lỗi font chữ.', 'Lỗi hệ điều hành.', 'Cài đặt lại font chữ chuẩn.', 0.00, '2025-12-17 05:00:27'),
(33, 27, '2024-11-27', 'Laptop Lễ tân cũ bị nóng.', 'Làm sạch bụi bẩn ở quạt tản nhiệt.', 'Vệ sinh bên trong máy.', 0.00, '2025-12-17 05:00:27'),
(34, 43, '2024-12-02', 'PC Cũ Trạm y tế không nhận USB.', 'Lỗi driver cổng USB.', 'Cài đặt lại driver chipset.', 0.00, '2025-12-17 05:00:27'),
(35, 30, '2024-12-06', 'Switch Công nghiệp 2 bị đèn báo đỏ.', 'Quá nhiệt độ môi trường.', 'Lắp thêm quạt làm mát cho tủ rack.', 500000.00, '2025-12-17 05:00:27'),
(36, 31, '2024-12-09', 'Máy tính Kho vật tư chậm.', 'Nhiều file rác.', 'Dọn dẹp ổ đĩa.', 0.00, '2025-12-17 05:00:27'),
(37, 15, '2024-12-11', 'Server Database bị truy cập chậm.', 'Tối ưu hóa query database.', 'Chạy lệnh tối ưu hóa MySQL/SQL.', 0.00, '2025-12-17 05:00:27'),
(38, 48, '2024-12-13', 'Camera Lối Ra bị mất tín hiệu.', 'Đường truyền bị nhiễu.', 'Kiểm tra lại đường dây tín hiệu.', 0.00, '2025-12-17 05:00:27'),
(39, 49, '2024-12-17', 'Switch Tầng 6 bị treo.', 'Khởi động lại (reboot) switch.', 'Khởi động lại thủ công.', 0.00, '2025-12-17 05:00:27'),
(40, 50, '2024-12-18', 'Đầu đọc thẻ xe máy bị lỗi phần mềm.', 'Cài đặt lại phần mềm quản lý.', 'Cập nhật firmware đầu đọc thẻ.', 0.00, '2025-12-17 05:00:27'),
(41, 7, '2024-12-19', 'AP Khu vực đóng gói yếu sóng.', 'Điều chỉnh công suất phát sóng.', 'Tăng công suất phát AP.', 0.00, '2025-12-17 05:00:27');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `ma_du_an` varchar(50) NOT NULL COMMENT 'Mã dự án nội bộ (VD: DA01, DA02)',
  `ten_du_an` varchar(255) NOT NULL COMMENT 'Tên dự án / chung cư',
  `dia_chi` text DEFAULT NULL COMMENT 'Địa chỉ dự án',
  `loai_du_an` varchar(100) DEFAULT NULL COMMENT 'Loại dự án (Chung cư, Văn phòng...)',
  `ghi_chu` text DEFAULT NULL COMMENT 'Ghi chú thêm'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh sách dự án KHASERVICE';

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `ma_du_an`, `ten_du_an`, `dia_chi`, `loai_du_an`, `ghi_chu`) VALUES
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

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `ten_npp` varchar(255) NOT NULL COMMENT 'Tên nhà phân phối / nhà thầu',
  `nguoi_lien_he` varchar(255) DEFAULT NULL COMMENT 'Tên người liên hệ',
  `dien_thoai` varchar(50) DEFAULT NULL COMMENT 'Số điện thoại',
  `email` varchar(255) DEFAULT NULL COMMENT 'Email liên hệ',
  `ghi_chu` text DEFAULT NULL COMMENT 'Ghi chú'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Nhà cung cấp thiết bị';

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `ten_npp`, `nguoi_lien_he`, `dien_thoai`, `email`, `ghi_chu`) VALUES
(1, 'Công ty CP Thiết bị An ninh A', 'Nguyễn Văn Hùng', '0901234567', 'hung.nv@anninha.com', 'Chuyên cung cấp Barrier và Đầu đọc thẻ'),
(2, 'Công ty TNHH Máy tính B', 'Lê Thị Nga', '0918765432', 'nga.lt@maytinhb.vn', 'Cung cấp PC, Laptop, UPS văn phòng'),
(3, 'Nhà thầu Giải pháp Camera C', 'Phạm Quốc Việt', '0987654321', 'viet.pq@camera-c.com', 'Lắp đặt và bảo trì hệ thống Camera giám sát'),
(4, 'Công ty Cung cấp Giải pháp Mạng D', 'Trần Văn Mạnh', '0945123789', 'manh.tv@network-d.com', 'Chuyên Router, Switch, AP'),
(5, 'Công ty TNHH Thiết bị Bãi xe H', 'Vũ Thị Thanh', '0978112233', 'thanh.vt@parking-h.com', 'Lắp đặt và bảo trì bãi xe, barrier'),
(6, 'Nhà Phân phối Server F', 'Đỗ Trung Kiên', '0932456789', 'kien.dt@server-f.com', 'Cung cấp Server, Storage'),
(7, 'Cửa hàng Sửa chữa G', 'Mai Văn Tấn', '0912345987', 'tan.mv@suachua-g.com', 'Đơn vị chuyên sửa chữa linh kiện và thiết bị cũ'),
(8, 'Đại lý Thiết bị Văn phòng I', 'Phan Văn Quý', '0983445566', 'quy.pv@office-i.vn', 'Cung cấp máy in, máy chiếu');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL COMMENT 'Tên đăng nhập',
  `password` varchar(255) NOT NULL COMMENT 'Mật khẩu (hash)',
  `role` varchar(50) DEFAULT NULL COMMENT 'Vai trò: admin / it / xem',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Người dùng hệ thống';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$xNwpeGTu1mo7fNSGGPXRn.j3NIAwyU4A86T8BDrzGFJmXwWW55IzS', 'admin', '2025-12-17 05:00:27'),
(2, 'it_kh', '$2y$10$xNwpeGTu1mo7fNSGGPXRn.j3NIAwyU4A86T8BDrzGFJmXwWW55IzS', 'it', '2025-12-17 05:00:27'),
(3, 'xem_thietbi', '$2y$10$xNwpeGTu1mo7fNSGGPXRn.j3NIAwyU4A86T8BDrzGFJmXwWW55IzS', 'xem', '2025-12-17 05:00:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_tai_san` (`ma_tai_san`);

--
-- Indexes for table `device_files`
--
ALTER TABLE `device_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `device_files`
--
ALTER TABLE `device_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `auth_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
