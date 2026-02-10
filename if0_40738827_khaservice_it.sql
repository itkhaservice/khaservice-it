-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql100.infinityfree.com
-- Generation Time: Feb 10, 2026 at 01:23 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_40738827_khaservice_it`
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
-- Table structure for table `car_system_configs`
--

CREATE TABLE `car_system_configs` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `server_ip` varchar(255) DEFAULT NULL,
  `db_name` varchar(255) DEFAULT NULL,
  `folder_path` varchar(500) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `car_system_configs`
--

INSERT INTO `car_system_configs` (`id`, `project_id`, `server_ip`, `db_name`, `folder_path`, `username`, `password`, `created_at`, `updated_at`) VALUES
(1, 14, '192.168.100.81', 'GIUXE', '\\\\192.168.100.81\\hinh', 'sa', '123ABC', '2026-01-19 06:35:51', '2026-01-19 06:35:51'),
(2, 13, '192.168.100.168', 'GIUXE', '\\\\192.168.100.168\\hinh', 'sa', '123ABC', '2026-01-19 06:36:44', '2026-01-19 06:36:44'),
(3, 19, '192.168.1.10', 'GIUXE', '\\\\192.168.1.10\\hinh moi', 'sa', '123ABC', '2026-01-20 02:14:15', '2026-01-20 02:14:15'),
(4, 32, '192.168.1.96', 'baixe1', '\\\\192.168.1.96\\hinh1', 'sa', '123ABC', '2026-01-21 02:04:23', '2026-01-21 02:04:23'),
(5, 26, 'DESKTOP-USP86RA', 'GIUXE', '\\\\192.168.1.102\\hinh moi', 'sa', '123ABC', '2026-02-06 07:05:07', '2026-02-06 07:05:07'),
(6, 35, '192.168.1.101', 'GIUXE', '\\\\192.168.1.101\\Hinh', 'admin', '123ABC', '2026-02-07 02:02:01', '2026-02-07 02:02:01');

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `ma_tai_san` varchar(100) NOT NULL COMMENT 'M?? t??i s???n n???i b??? (VD: KHAS-DA01-PC-001)',
  `ten_thiet_bi` varchar(255) NOT NULL COMMENT 'T??n thi???t b??? (PC l??? t??n, UPS barrier...)',
  `nhom_thiet_bi` varchar(50) NOT NULL COMMENT 'Nh??m thi???t b???: V??n ph??ng / B??i xe',
  `loai_thiet_bi` varchar(100) NOT NULL COMMENT 'Lo???i thi???t b???: PC, UPS, Camera, Barrier, ?????u ?????c th???...',
  `model` varchar(255) DEFAULT NULL COMMENT 'Model thi???t b???',
  `serial` varchar(255) DEFAULT NULL COMMENT 'Serial number c???a thi???t b???',
  `project_id` int(11) DEFAULT NULL COMMENT 'Thi???t b??? thu???c d??? ??n n??o',
  `parent_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL COMMENT 'Nh?? cung c???p thi???t b???',
  `ngay_mua` date DEFAULT NULL COMMENT 'Ng??y mua thi???t b???',
  `gia_mua` decimal(15,2) DEFAULT NULL COMMENT 'Gi?? mua t???i th???i ??i???m mua (VN??)',
  `bao_hanh_den` date DEFAULT NULL COMMENT 'Ng??y h???t h???n b???o h??nh',
  `trang_thai` varchar(50) DEFAULT '??ang s??? d???ng' COMMENT 'Tr???ng th??i: ??ang s??? d???ng / H???ng / Thanh l??',
  `ghi_chu` text DEFAULT NULL COMMENT 'Ghi ch?? kh??c',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Ng??y t???o h??? s?? thi???t b???',
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh s??ch thi???t b??? & linh ki???n';

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`id`, `ma_tai_san`, `ten_thiet_bi`, `nhom_thiet_bi`, `loai_thiet_bi`, `model`, `serial`, `project_id`, `parent_id`, `supplier_id`, `ngay_mua`, `gia_mua`, `bao_hanh_den`, `trang_thai`, `ghi_chu`, `created_at`, `deleted_at`) VALUES
(1, 'KHAS-DA-DEMO-VP-PC-001', 'Máy tính Dell Kế toán', 'Văn phòng', 'Máy tính', 'OptiPlex 3050', '', 38, NULL, NULL, NULL, NULL, NULL, 'Tốt', '', '2025-12-23 06:47:18', '2026-01-19 07:06:10'),
(2, 'KHAS-DA-DEMO-VP-LK-001', 'RAM 8GB DDR4', 'Văn phòng', 'Linh kiện', 'Kingston', NULL, 38, 1, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', '2026-01-19 07:04:53'),
(3, 'KHAS-DA-DEMO-VP-LK-002', 'SSD 250GB', 'Văn phòng', 'Linh kiện', 'Samsung EVO', NULL, 38, 1, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', '2026-01-19 07:04:53'),
(4, 'KHAS-DA-DEMO-VP-LK-003', 'PSU 450W', 'Văn phòng', 'Linh kiện', 'Cooler Master', NULL, 38, 1, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', '2026-01-19 07:04:53'),
(5, 'KHAS-DA-DEMO-VP-LK-004', 'Mainboard H110', 'Văn phòng', 'Linh kiện', 'ASUS', NULL, 38, 1, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', '2026-01-19 07:04:53'),
(6, 'KHAS-DA-DEMO-VP-LK-005', 'Màn hình 24 inch', 'Văn phòng', 'Linh kiện', 'Dell UltraSharp', NULL, 38, 1, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', '2026-01-19 07:04:53'),
(7, 'KHAS-DA-DEMO-VP-PC-002', 'Máy tính HP Hành chính', 'Văn phòng', 'Máy tính', 'ProDesk 400', NULL, 38, NULL, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', NULL),
(8, 'KHAS-DA-DEMO-VP-LK-010', 'RAM 8GB DDR4', 'Văn phòng', 'Linh kiện', 'G.Skill', NULL, 38, 7, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', NULL),
(9, 'KHAS-DA-DEMO-VP-LK-011', 'SSD 250GB', 'Văn phòng', 'Linh kiện', 'WD Green', NULL, 38, 7, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', NULL),
(10, 'KHAS-DA-DEMO-VP-LK-012', 'PSU 450W', 'Văn phòng', 'Linh kiện', 'Acbel', NULL, 38, 7, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', '2026-01-19 07:07:11'),
(11, 'KHAS-DA-DEMO-VP-LK-013', 'Mainboard H110', 'Văn phòng', 'Linh kiện', 'Gigabyte', NULL, 38, 7, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', '2026-01-19 07:06:46'),
(12, 'KHAS-DA-DEMO-VP-LK-014', 'Màn hình 24 inch', 'Văn phòng', 'Linh kiện', 'Samsung Curved', NULL, 38, 7, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', '2026-01-19 07:06:53'),
(13, 'KHAS-DA-DEMO-BX-HT-001', 'Hệ thống kiểm soát xe Cổng A', 'Bãi xe', 'Hệ thống', NULL, NULL, 38, NULL, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', NULL),
(14, 'KHAS-DA-DEMO-BX-PC-001', 'Máy tính xử lý LPR', 'Bãi xe', 'Máy tính', 'Advantech IPC', NULL, 38, 13, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', NULL),
(15, 'KHAS-DA-DEMO-BX-LK-001', 'Card đồ họa AI', 'Bãi xe', 'Linh kiện', 'NVIDIA RTX 3060', NULL, 38, 14, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', NULL),
(16, 'KHAS-DA-DEMO-BX-CAM-001', 'Camera LPR Cổng A-1', 'Bãi xe', 'Camera', 'Hikvision 2MP', NULL, 38, 13, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', NULL),
(17, 'KHAS-DA-DEMO-BX-CAM-002', 'Camera LPR Cổng A-2', 'Bãi xe', 'Camera', 'Hikvision 2MP', NULL, 38, 13, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', NULL),
(18, 'KHAS-DA-DEMO-BX-CAM-003', 'Camera LPR Cổng A-3', 'Bãi xe', 'Camera', 'Hikvision 2MP', NULL, 38, 13, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', NULL),
(19, 'KHAS-DA-DEMO-BX-CAM-004', 'Camera LPR Cổng A-4', 'Bãi xe', 'Camera', 'Hikvision 2MP', NULL, 38, 13, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', NULL),
(20, 'KHAS-DA-DEMO-BX-BR-001', 'Barrier MAG-1', 'Bãi xe', 'Barrier', 'MAG BR630', NULL, 38, 13, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', NULL),
(21, 'KHAS-DA-DEMO-BX-BR-002', 'Barrier MAG-2', 'Bãi xe', 'Barrier', 'MAG BR630', NULL, 38, 13, NULL, NULL, NULL, NULL, 'Đang sử dụng', NULL, '2025-12-23 06:47:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `device_files`
--

CREATE TABLE `device_files` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL COMMENT 'Thi???t b??? li??n quan',
  `loai_file` varchar(50) NOT NULL COMMENT 'Lo???i file: HoaDon / BienBan / HinhAnh',
  `file_path` varchar(255) NOT NULL COMMENT '???????ng d???n file l??u tr??n server',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Th???i gian upload'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='File ????nh k??m thi???t b???';

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_files`
--

CREATE TABLE `maintenance_files` (
  `id` int(11) NOT NULL,
  `maintenance_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `loai_file` varchar(50) DEFAULT 'Khác',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_logs`
--

CREATE TABLE `maintenance_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `device_id` int(11) DEFAULT NULL COMMENT 'ID thiet bi (NULL neu nhap tay)',
  `custom_device_name` varchar(255) DEFAULT NULL,
  `usage_time_manual` varchar(100) DEFAULT NULL,
  `ngay_su_co` date NOT NULL COMMENT 'Ng??y x???y ra s??? c???',
  `ngay_lap_phieu` date DEFAULT NULL,
  `noi_dung` text DEFAULT NULL COMMENT 'M?? t??? s??? c??? do d??? ??n b??o',
  `hu_hong` text DEFAULT NULL COMMENT 'X??c ?????nh h?? h???ng sau ki???m tra',
  `xu_ly` text DEFAULT NULL COMMENT 'H?????ng x??? l?? / s???a ch???a',
  `chi_phi` decimal(15,2) DEFAULT NULL COMMENT 'Chi ph?? s???a ch???a (n???u c??)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Ng??y ghi nh???n bi??n b???n',
  `client_name` varchar(255) DEFAULT NULL COMMENT '?????i di???n kh??ch h??ng',
  `client_phone` varchar(50) DEFAULT NULL COMMENT 'S??T kh??ch h??ng',
  `arrival_time` datetime DEFAULT NULL COMMENT 'Th???i ??i???m c?? m???t',
  `completion_time` datetime DEFAULT NULL COMMENT 'Th???i ??i???m ho??n th??nh',
  `work_type` varchar(255) DEFAULT 'B???o tr?? / S???a ch???a' COMMENT 'Lo???i c??ng vi???c',
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='L???ch s??? s???a ch???a thi???t b???';

--
-- Dumping data for table `maintenance_logs`
--

INSERT INTO `maintenance_logs` (`id`, `user_id`, `project_id`, `device_id`, `custom_device_name`, `usage_time_manual`, `ngay_su_co`, `ngay_lap_phieu`, `noi_dung`, `hu_hong`, `xu_ly`, `chi_phi`, `created_at`, `client_name`, `client_phone`, `arrival_time`, `completion_time`, `work_type`, `deleted_at`) VALUES
(1, 5, 38, 13, NULL, NULL, '2025-12-23', '2025-12-23', 'Kiểm tra tác phong nhân viên và tình trạng vận hành thiết bị tại cổng chính.', 'Tác phong chỉnh tề, thiết bị bám bụi nhẹ.', 'Đã nhắc nhở vệ sinh thiết bị, lau chùi camera.', NULL, '2025-12-23 06:54:20', 'Ban quản lý Khahomex', '', '2025-12-23 08:00:00', '2025-12-23 12:00:00', 'Kiểm tra định kỳ', '2026-01-20 00:44:57'),
(2, 4, 38, 15, NULL, NULL, '2025-12-23', '2025-12-23', 'Máy tính bãi xe hay bị treo khi xử lý biển số.', 'Card đồ họa AI bị quá nhiệt, quạt không quay.', 'Vệ sinh quạt card đồ họa, tra keo tản nhiệt mới. Đã hoạt động ổn định.', NULL, '2025-12-23 06:54:20', 'Kỹ thuật tòa nhà', '0888823058', '2025-12-23 14:00:00', '2025-12-23 16:00:00', 'Kiểm tra bảo trì hệ thống xe', '2026-01-20 00:44:57'),
(3, 4, 14, NULL, NULL, NULL, '2026-01-18', '2026-01-19', NULL, '', 'Toàn bộ phương tiện được kiểm tra đều có dữ liệu trên hệ thống xe', '0.00', '2026-01-19 03:59:07', 'Nguyễn Ngọc Thảo', 'Trưởng Ban quản lý', '2026-01-19 05:30:00', '2026-01-19 13:00:00', 'Kiểm tra thực tế bãi giữ xe', NULL),
(4, 4, 13, NULL, NULL, NULL, '2026-01-18', '2026-01-19', NULL, '', 'Toàn bộ phương tiện được kiểm đều có dữ liệu trên hệ thống xe', '0.00', '2026-01-20 00:45:59', 'Võ Nam Sơn ', 'Trưởng Ban quản lý', '2026-01-19 05:30:00', '2026-01-19 15:00:00', 'Kiểm tra thực tế bãi giữ xe', NULL),
(5, 4, 32, NULL, NULL, NULL, '2026-01-20', '2026-01-20', NULL, '', 'Toàn bộ phương tiện được kiểm tra đều có dữ liệu trên hệ thống xe.', '0.00', '2026-01-21 03:22:41', 'HỒ THỊ XUÂN HÀ', 'Trưởng Ban quản lý', '2026-01-21 08:00:00', '2026-01-21 12:00:00', 'Kiểm tra thực tế bãi giữ xe', NULL),
(6, 4, 11, NULL, NULL, NULL, '2026-01-30', '2026-01-30', NULL, '', 'Ghi nhận 2 xe máy đỗ trong khu vực hầm xe, không có dữ liệu trên hệ thống. Biển kiểm soát lần lượt là: 59F1-65192 và 51F9-4977', '0.00', '2026-01-30 08:58:51', 'KHÚC THỊ LÂM', 'KẾ TOÁN', '2026-01-30 08:00:00', '2026-01-30 17:00:00', 'Kiểm tra thực tế bãi giữ xe', NULL),
(7, 4, 4, NULL, NULL, NULL, '2026-02-02', '2026-02-02', NULL, 'Kiểm tra trực tiếp thông tin của các phương tiện đỗ trong khu vực hầm xe', 'Toàn bộ phương tiện được kiểm tra đều có dữ liệu trên hệ thống xe', '0.00', '2026-02-02 07:27:21', 'Nhữ Mạnh Đức', 'Trưởng Ban quản lý', '2026-02-02 08:00:00', '2026-02-02 14:30:00', 'Kiểm tra thực tế bãi giữ xe', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `ma_du_an` varchar(50) NOT NULL COMMENT 'M?? d??? ??n n???i b??? (VD: DA01, DA02)',
  `ten_du_an` varchar(255) NOT NULL COMMENT 'T??n d??? ??n / chung c??',
  `dia_chi_duong` varchar(255) DEFAULT NULL,
  `dia_chi_phuong_xa` varchar(100) DEFAULT NULL,
  `dia_chi_tinh_tp` varchar(100) DEFAULT NULL,
  `dia_chi` text DEFAULT NULL COMMENT '?????a ch??? d??? ??n',
  `loai_du_an` varchar(100) DEFAULT NULL COMMENT 'Lo???i d??? ??n (Chung c??, V??n ph??ng...)',
  `ghi_chu` text DEFAULT NULL COMMENT 'Ghi ch?? th??m',
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh s??ch d??? ??n KHASERVICE';

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `ma_du_an`, `ten_du_an`, `dia_chi_duong`, `dia_chi_phuong_xa`, `dia_chi_tinh_tp`, `dia_chi`, `loai_du_an`, `ghi_chu`, `deleted_at`) VALUES
(1, 'DA4SRS', '4S RIVERSIDE GARDEN', '75/15 Đường số 17 Khu Phố 3', 'Phường Hiệp Bình', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(2, 'DACTVPRM', 'CANTAVIL PREMIER', 'Số 1 Song Hành', 'Phường Bình Trưng', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(3, 'DACTZ', 'CITIZEN.TS', 'Đường số 9A Khu dân cư Trung Sơn', 'Phường Bình Đông', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(4, 'DACTP', 'CITRINE APARTMENT', '127 Tăng Nhơn Phú', 'Phường Phước Long', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(5, 'DACPSQ', 'COPAC SQUARE', '12 Tôn Đản', 'Phường Xóm Chiếu', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(6, 'DAFLRAD', 'FLORA ANH ĐÀO', '619 Đỗ Xuân Hợp', 'Phường Phước Long', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(7, 'DAFLRKKO', 'FLORA KIKYO', 'Tổ 9 Khu Phố 2', 'Phường Phú Thuận', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(8, 'DAHAGL2', 'HOÀNG ANH GIA LAI 2', '769-783 Trần Xuân Soạn', 'Phường Tân Hưng', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(9, 'DAHML2', 'HOMYLAND 2', '307 Đường Nguyễn Duy Trinh', 'Phường Bình Trưng', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(10, 'DAHRZ', 'HORIZON', '214 Trần Quang Khải', 'Phường Tân Định', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(11, 'DAHP1', 'HƯNG PHÁT', '928 Lê Văn Lương', 'Xã Nhà Bè', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(12, 'DAHP2', 'HƯNG PHÁT SILVER STAR', '156A Nguyễn Hữu Thọ', 'Xã Nhà Bè', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(13, 'DAKH1', 'KHÁNH HỘI 1', '360C Bến Vân Đồn', 'Phường Vĩnh Hội', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(14, 'DAKH2', 'KHÁNH HỘI 2', '360A Bến Vân Đồn', 'Phường Vĩnh Hội', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(15, 'DAKH3', 'KHÁNH HỘI 3', '360G Bến Vân Đồn', 'Phường Vĩnh Hội', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(16, 'DALPMHBR', 'LAN PHƯƠNG MHBR', '104 đường Hồ Văn Tư', 'Phường Trường Thọ', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(17, 'DAR7AK', 'LÔ R7 AN KHÁNH', '23 Lưu Đình Lễ', 'Phường An Khánh', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(18, 'DANL2', 'NHẤT LAN II', 'Đường 54A', 'Phường Tân Tạo', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(19, 'DAORE', 'ORIENT APARTMENT', '331 Bến Vân Đồn', 'Phường Vĩnh Hội', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(20, 'DAPGP', 'PHỐ GIA PHÚC', '94 Tô Vĩnh Diện', 'Phường Thủ Đức', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(21, 'DAPGA', 'PHÚ GIA', 'Khu dân cư Phú Gia', 'Xã Nhà Bè', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(22, 'DASGMT', 'SAI GON METRO PARK', 'Đường số 1', 'Phường Thủ Đức', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(23, 'DASSRRS', 'SAMSORA RIVERSIDE', '207A Quốc lộ 1A Khu phố Quyết Thắng', 'Phường Dĩ An', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(24, 'DASCRII', 'SCREC II', 'Đường số 4 Khu Đô thị mới', 'Phường Bình Trưng', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(25, 'DASHA', 'SEN HỒNG A', 'Khu phố Bình Đường 3', 'Phường Dĩ An', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(26, 'DASHBC', 'SEN HỒNG BC', 'Khu phố Bình Đường 3', 'Phường Dĩ An', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(27, 'DASDTW', 'SÔNG ĐÀ', '14B Kỳ Đồng', 'Phường Nhiêu Lộc', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(28, 'DASVTP', 'TAM PHÚ', '1A-1B Đường Cây Keo', 'Phường Tam Bình', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(29, 'DATDHPL', 'TDH - PHƯỚC LONG', 'Đường 672', 'Phường Phước Long', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(30, 'DATS', 'THE STAR', '1123 Quốc Lộ 1A', 'Phường Tân Tạo', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(31, 'DAUFAP', 'THE USEFUL APARTMENT', '654/06 Lạc Long Quân', 'Phường Tân Hòa', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(32, 'DATPCT', 'TOPAZ CITY KHỐI B', '39 Cao Lỗ', 'Phường Chánh Hưng', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(33, 'DATPEP1', 'TOPAZ ELITE PHOENIX 1', '547-549 Tạ Quang Bửu', 'Phường Chánh Hưng', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(34, 'DATPEP2', 'TOPAZ ELITE PHOENIX 2', '37 Cao Lỗ', 'Phường Chánh Hưng', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(35, 'DATPH2', 'TOPAZ HOME 2 - BLOCK B', '215 Đường số 138', 'Phường Tăng Nhơn Phú', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(36, 'DAVDA', 'VẠN ĐÔ', '348 Bến Vân Đồn', 'Phường Vĩnh Hội', 'TP.HCM', NULL, 'Chung cư', NULL, NULL),
(37, 'VPC', 'VĂN PHÒNG CÔNG TY', '360C Bến Vân Đồn', 'Phường Vĩnh Hội', 'TP.HCM', NULL, 'Văn phòng', NULL, NULL),
(38, 'DA-DEMO', 'Tòa nhà Khahomex (Dự án Mẫu)', '360C Bến Vân Đồn', 'Phường Vĩnh Hội', 'TP.HCM', NULL, 'Văn phòng', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `ten_dich_vu` varchar(255) NOT NULL,
  `loai_dich_vu` varchar(100) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `ngay_dang_ky` date DEFAULT NULL,
  `ngay_het_han` date NOT NULL,
  `chi_phi_gia_han` decimal(15,2) DEFAULT 0.00,
  `nhac_truoc_ngay` int(11) DEFAULT 30,
  `ghi_chu` text DEFAULT NULL,
  `ngay_nhan_de_nghi` date DEFAULT NULL,
  `trang_thai` varchar(50) DEFAULT 'ðang ho?t d?ng',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings_device_groups`
--

CREATE TABLE `settings_device_groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `group_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings_device_groups`
--

INSERT INTO `settings_device_groups` (`id`, `group_name`, `group_code`) VALUES
(1, 'Tòa nhà', 'TN'),
(2, 'Văn phòng', 'VP'),
(3, 'Hệ thống xe', 'HTX'),
(5, 'Bãi xe', 'BX');

-- --------------------------------------------------------

--
-- Table structure for table `settings_device_statuses`
--

CREATE TABLE `settings_device_statuses` (
  `id` int(11) NOT NULL,
  `status_name` varchar(100) NOT NULL,
  `color_class` varchar(50) DEFAULT 'status-default',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings_device_statuses`
--

INSERT INTO `settings_device_statuses` (`id`, `status_name`, `color_class`, `created_at`) VALUES
(5, 'Tốt', 'status-active', '2025-12-22 06:38:16'),
(6, 'Cảnh báo', 'status-warning', '2025-12-22 06:38:16'),
(7, 'Hỏng', 'status-error', '2025-12-22 06:38:16'),
(8, 'Thanh lý', 'status-default', '2025-12-22 06:38:16');

-- --------------------------------------------------------

--
-- Table structure for table `settings_device_types`
--

CREATE TABLE `settings_device_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `type_code` varchar(20) DEFAULT NULL,
  `group_name` varchar(100) DEFAULT 'Văn phòng',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings_device_types`
--

INSERT INTO `settings_device_types` (`id`, `type_name`, `type_code`, `group_name`, `created_at`) VALUES
(33, 'Máy in', 'MI', 'Văn phòng', '2025-12-23 05:47:56'),
(39, 'Máy tính kỹ thuật', 'KST', 'Văn phòng', '2025-12-23 05:48:59'),
(40, 'Máy tính kế toán', 'KT', 'Văn phòng', '2025-12-23 05:49:06'),
(41, 'Máy tính trưởng ban', 'TB', 'Văn phòng', '2025-12-23 05:49:14'),
(43, 'Camera', 'CAM', 'Bãi xe', '2025-12-23 05:49:32'),
(44, 'Đầu ghi', 'DG', 'Hệ thống xe', '2025-12-23 05:49:37'),
(45, 'Bộ chia mạng', 'SW', 'Hệ thống xe', '2025-12-23 05:49:44'),
(46, 'Barrier', 'BR', 'Bãi xe', '2025-12-23 05:49:51'),
(47, 'Đầu đọc thẻ', 'DD', 'Hệ thống xe', '2025-12-23 05:49:57'),
(48, 'Máy tính', 'PC', 'Bãi xe', '2025-12-23 05:50:12'),
(49, 'Máy tính lối vào', 'MTV', 'Hệ thống xe', '2025-12-23 05:50:20'),
(51, 'Máy tính lối ra', 'MTR', 'Hệ thống xe', '2025-12-23 05:51:01'),
(52, 'Cam tòa nhà', 'CAMTN', 'Tòa nhà', '2025-12-23 06:13:32'),
(54, 'Linh kiện', 'LK', 'Bãi xe', '2025-12-23 06:36:23'),
(55, 'Hệ thống', 'HT', 'Bãi xe', '2025-12-23 06:36:23');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `ten_npp` varchar(255) NOT NULL COMMENT 'T??n nh?? ph??n ph???i / nh?? th???u',
  `nguoi_lien_he` varchar(255) DEFAULT NULL COMMENT 'T??n ng?????i li??n h???',
  `dien_thoai` varchar(50) DEFAULT NULL COMMENT 'S??? ??i???n tho???i',
  `email` varchar(255) DEFAULT NULL COMMENT 'Email li??n h???',
  `ghi_chu` text DEFAULT NULL COMMENT 'Ghi ch??',
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Nh?? cung c???p thi???t b???';

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL COMMENT 'T??n ????ng nh???p',
  `fullname` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL COMMENT 'M???t kh???u (hash)',
  `role` varchar(50) DEFAULT NULL COMMENT 'Vai tr??: admin / it / xem',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Ng?????i d??ng h??? th???ng';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `fullname`, `password`, `role`, `created_at`, `deleted_at`) VALUES
(1, 'admin', 'admin', '$2y$10$xNwpeGTu1mo7fNSGGPXRn.j3NIAwyU4A86T8BDrzGFJmXwWW55IzS', 'admin', '2025-12-17 05:00:27', NULL),
(2, 'it_kh', 'it_kh', '$2y$10$xNwpeGTu1mo7fNSGGPXRn.j3NIAwyU4A86T8BDrzGFJmXwWW55IzS', 'it', '2025-12-17 05:00:27', NULL),
(3, 'xem_thietbi', 'xem_thietbi', '$2y$10$xNwpeGTu1mo7fNSGGPXRn.j3NIAwyU4A86T8BDrzGFJmXwWW55IzS', 'xem', '2025-12-17 05:00:27', NULL),
(4, 'cmthang', 'Cao Minh Thắng', '$2y$10$KXfkwUhQsPckc.PnDABgY.FDkQwHgndMl/8vyPi9CyQJlHn.y5OE2', 'it', '2025-12-22 03:02:47', NULL),
(5, 'nttrung', 'Nguyễn Tất Trung', '$2y$10$oU862C4l3EBFMm/MTcUe2O5rNWLw03SMZp5YZ/e0O.pd0KbxZhKH6', 'it', '2025-12-22 03:02:58', NULL);

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
-- Indexes for table `car_system_configs`
--
ALTER TABLE `car_system_configs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_tai_san` (`ma_tai_san`),
  ADD KEY `idx_device_parent` (`parent_id`);

--
-- Indexes for table `device_files`
--
ALTER TABLE `device_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance_files`
--
ALTER TABLE `maintenance_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maintenance_id` (`maintenance_id`);

--
-- Indexes for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_project` (`project_id`),
  ADD KEY `fk_log_user` (`user_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_services_supplier` (`supplier_id`),
  ADD KEY `fk_services_project` (`project_id`);

--
-- Indexes for table `settings_device_groups`
--
ALTER TABLE `settings_device_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_name` (`group_name`);

--
-- Indexes for table `settings_device_statuses`
--
ALTER TABLE `settings_device_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `settings_device_types`
--
ALTER TABLE `settings_device_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `car_system_configs`
--
ALTER TABLE `car_system_configs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `device_files`
--
ALTER TABLE `device_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance_files`
--
ALTER TABLE `maintenance_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings_device_groups`
--
ALTER TABLE `settings_device_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `settings_device_statuses`
--
ALTER TABLE `settings_device_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `settings_device_types`
--
ALTER TABLE `settings_device_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `auth_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `car_system_configs`
--
ALTER TABLE `car_system_configs`
  ADD CONSTRAINT `car_system_configs_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_files`
--
ALTER TABLE `maintenance_files`
  ADD CONSTRAINT `maintenance_files_ibfk_1` FOREIGN KEY (`maintenance_id`) REFERENCES `maintenance_logs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD CONSTRAINT `fk_log_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_services_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_services_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
