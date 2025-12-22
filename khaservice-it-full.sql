-- KHASERVICE IT FULL DATABASE DUMP --
-- Generated at: 2025-12-22 07:40:36 --

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `auth_tokens`;
CREATE TABLE `auth_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `auth_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



DROP TABLE IF EXISTS `device_files`;
CREATE TABLE `device_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(11) NOT NULL COMMENT 'Thi???t b??? li??n quan',
  `loai_file` varchar(50) NOT NULL COMMENT 'Lo???i file: HoaDon / BienBan / HinhAnh',
  `file_path` varchar(255) NOT NULL COMMENT '???????ng d???n file l??u tr??n server',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Th???i gian upload',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='File ????nh k??m thi???t b???';



DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_tai_san` (`ma_tai_san`),
  KEY `idx_device_parent` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh s??ch thi???t b??? & linh ki???n';

INSERT INTO `devices` VALUES('1','c','c','Văn phòng','','','','1',NULL,NULL,NULL,NULL,NULL,'Tốt','','2025-12-22 12:44:11',NULL);
INSERT INTO `devices` VALUES('2','KHA-CPU-001','CPU Dell Optiplex 3050','Văn phòng','CPU','Optiplex 3050','','38',NULL,'2',NULL,NULL,NULL,'Hỏng','','2025-12-22 13:34:13',NULL);
INSERT INTO `devices` VALUES('3','KHA-RAM-001','Thanh RAM DDR4 8GB','Linh kiện','RAM','Kingston 8GB',NULL,'38','2','2',NULL,NULL,NULL,'Tốt',NULL,'2025-12-22 13:34:13',NULL);
INSERT INTO `devices` VALUES('4','KHA-SSD-001','Ổ cứng SSD 256GB','Linh kiện','SSD / HDD','Samsung EVO',NULL,'38','2','2',NULL,NULL,NULL,'Tốt',NULL,'2025-12-22 13:34:13',NULL);
INSERT INTO `devices` VALUES('5','KHA-BAR-001','Cổng Barrier Tự động','Bãi xe','Barrier','Baisheng',NULL,'38',NULL,'2',NULL,NULL,NULL,'Tốt',NULL,'2025-12-22 13:34:13',NULL);
INSERT INTO `devices` VALUES('6','KHA-VT-001','Cảm biến Vòng từ','Bãi xe','Vòng từ','PD-132',NULL,'38','5','2',NULL,NULL,NULL,'Tốt',NULL,'2025-12-22 13:34:13',NULL);


DROP TABLE IF EXISTS `maintenance_files`;
CREATE TABLE `maintenance_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `maintenance_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `loai_file` varchar(50) DEFAULT 'Khác',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `maintenance_id` (`maintenance_id`),
  CONSTRAINT `maintenance_files_ibfk_1` FOREIGN KEY (`maintenance_id`) REFERENCES `maintenance_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



DROP TABLE IF EXISTS `maintenance_logs`;
CREATE TABLE `maintenance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `device_id` int(11) DEFAULT NULL COMMENT 'ID thiet bi (NULL neu nhap tay)',
  `custom_device_name` varchar(255) DEFAULT NULL,
  `usage_time_manual` varchar(100) DEFAULT NULL,
  `ngay_su_co` date NOT NULL COMMENT 'Ng??y x???y ra s??? c???',
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
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_log_project` (`project_id`),
  KEY `fk_log_user` (`user_id`),
  CONSTRAINT `fk_log_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='L???ch s??? s???a ch???a thi???t b???';

INSERT INTO `maintenance_logs` VALUES('11','4','15',NULL,NULL,NULL,'2025-12-22',NULL,'','Toàn bộ phương tiện được kiểm tra đều có dữ liệu trên hệ thống xe','0.00','2025-12-22 10:46:40','Trưởng BQL','0912374068','2025-12-22 05:15:00','2025-12-22 12:00:00','Kiểm tra thực tế bãi giữ xe',NULL);
INSERT INTO `maintenance_logs` VALUES('13','1','15',NULL,NULL,NULL,'2025-12-22',NULL,'','','0.00','2025-12-22 13:02:16','','',NULL,NULL,'Bảo trì / Sửa chữa',NULL);
INSERT INTO `maintenance_logs` VALUES('14','1','38','2',NULL,NULL,'2025-12-22','Vệ sinh định kỳ trạm máy tính','Bụi bẩn nhiều','Vệ sinh thổi bụi, tra keo tản nhiệt',NULL,'2025-12-22 13:34:13',NULL,NULL,NULL,NULL,'Bảo trì định kỳ',NULL);
INSERT INTO `maintenance_logs` VALUES('15','1','38','4',NULL,NULL,'2025-12-22','Máy tính chạy chậm, treo xanh','Ổ cứng SSD cũ bị bad sector','Thay thế ổ cứng SSD mới 256GB',NULL,'2025-12-22 13:34:13',NULL,NULL,NULL,NULL,'Sửa chữa đột xuất',NULL);


DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_du_an` varchar(50) NOT NULL COMMENT 'M?? d??? ??n n???i b??? (VD: DA01, DA02)',
  `ten_du_an` varchar(255) NOT NULL COMMENT 'T??n d??? ??n / chung c??',
  `dia_chi_duong` varchar(255) DEFAULT NULL,
  `dia_chi_phuong_xa` varchar(100) DEFAULT NULL,
  `dia_chi_tinh_tp` varchar(100) DEFAULT NULL,
  `dia_chi` text DEFAULT NULL COMMENT '?????a ch??? d??? ??n',
  `loai_du_an` varchar(100) DEFAULT NULL COMMENT 'Lo???i d??? ??n (Chung c??, V??n ph??ng...)',
  `ghi_chu` text DEFAULT NULL COMMENT 'Ghi ch?? th??m',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh s??ch d??? ??n KHASERVICE';

INSERT INTO `projects` VALUES('1','DA4SRS','CHUNG CƯ 4S RIVERSIDE GARDEN','75/15 Đường số 17 Khu Phố 3','Phường Hiệp Bình','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('2','DACTVPRM','CHUNG CƯ CANTAVIL PREMIER','Số 1 Song Hành','Phường Bình Trưng','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('3','DACTZ','CHUNG CƯ CITIZEN.TS','Đường số 9A Khu dân cư Trung Sơn','Phường Bình Đông','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('4','DACTP','CHUNG CƯ CITRINE APARTMENT','127 Tăng Nhơn Phú','Phường Phước Long','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('5','DACPSQ','CHUNG CƯ COPAC SQUARE','12 Tôn Đản','Phường Xóm Chiếu','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('6','DAFLRAD','CHUNG CƯ FLORA ANH ĐÀO','619 Đỗ Xuân Hợp','Phường Phước Long','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('7','DAFLRKKO','CHUNG CƯ FLORA KIKYO','Tổ 9 Khu Phố 2','Phường Phú Thuận','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('8','DAHAGL2','CHUNG CƯ HOÀNG ANH GIA LAI 2','769-783 Trần Xuân Soạn','Phường Tân Hưng','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('9','DAHML2','CHUNG CƯ HOMYLAND 2','307 Đường Nguyễn Duy Trinh','Phường Bình Trưng','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('10','DAHRZ','CHUNG CƯ HORIZON','214 Trần Quang Khải','Phường Tân Định','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('11','DAHP1','CHUNG CƯ HƯNG PHÁT','928 Lê Văn Lương','Xã Nhà Bè','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('12','DAHP2','CHUNG CƯ HƯNG PHÁT SILVER STAR','156A Nguyễn Hữu Thọ','Xã Nhà Bè','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('13','DAKH1','CHUNG CƯ KHÁNH HỘI 1','360C Bến Vân Đồn','Phường Vĩnh Hội','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('14','DAKH2','CHUNG CƯ KHÁNH HỘI 2','360A Bến Vân Đồn','Phường Vĩnh Hội','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('15','DAKH3','CHUNG CƯ KHÁNH HỘI 3','360G Bến Vân Đồn','Phường Vĩnh Hội','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('16','DALPMHBR','CHUNG CƯ LAN PHƯƠNG MHBR','104 đường Hồ Văn Tư','Phường Trường Thọ','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('17','DAR7AK','CHUNG CƯ LÔ R7 AN KHÁNH','23 Lưu Đình Lễ','Phường An Khánh','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('18','DANL2','CHUNG CƯ NHẤT LAN II','Đường 54A','Phường Tân Tạo','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('19','DAORE','CHUNG CƯ ORIENT APARTMENT','331 Bến Vân Đồn','Phường Vĩnh Hội','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('20','DAPGP','CHUNG CƯ PHỐ GIA PHÚC','94 Tô Vĩnh Diện','Phường Thủ Đức','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('21','DAPGA','CHUNG CƯ PHÚ GIA','Khu dân cư Phú Gia','Xã Nhà Bè','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('22','DASGMT','CHUNG CƯ SAI GON METRO PARK','Đường số 1','Phường Thủ Đức','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('23','DASSRRS','CHUNG CƯ SAMSORA RIVERSIDE','207A Quốc lộ 1A Khu phố Quyết Thắng','Phường Dĩ An','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('24','DASCRII','CHUNG CƯ SCREC II','Đường số 4 Khu Đô thị mới','Phường Bình Trưng','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('25','DASHA','CHUNG CƯ SEN HỒNG A','Khu phố Bình Đường 3','Phường Dĩ An','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('26','DASHBC','CHUNG CƯ SEN HỒNG BC','Khu phố Bình Đường 3','Phường Dĩ An','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('27','DASDTW','CHUNG CƯ SÔNG ĐÀ','14B Kỳ Đồng','Phường Nhiêu Lộc','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('28','DASVTP','CHUNG CƯ TAM PHÚ','1A-1B Đường Cây Keo','Phường Tam Bình','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('29','DATDHPL','CHUNG CƯ TDH - PHƯỚC LONG','Đường 672','Phường Phước Long','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('30','DATS','CHUNG CƯ THE STAR','1123 Quốc Lộ 1A','Phường Tân Tạo','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('31','DAUFAP','CHUNG CƯ THE USEFUL APARTMENT','654/06 Lạc Long Quân','Phường Tân Hòa','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('32','DATPCT','CHUNG CƯ TOPAZ CITY KHỐI B','39 Cao Lỗ','Phường Chánh Hưng','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('33','DATPEP1','CHUNG CƯ TOPAZ ELITE PHOENIX 1','547-549 Tạ Quang Bửu','Phường Chánh Hưng','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('34','DATPEP2','CHUNG CƯ TOPAZ ELITE PHOENIX 2','37 Cao Lỗ','Phường Chánh Hưng','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('35','DATPH2','CHUNG CƯ TOPAZ HOME 2 - BLOCK B','215 Đường số 138','Phường Tăng Nhơn Phú','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('36','DAVDA','CHUNG CƯ VẠN ĐÔ','348 Bến Vân Đồn','Phường Vĩnh Hội','TP.HCM',NULL,'Chung cư',NULL,NULL);
INSERT INTO `projects` VALUES('37','VPC','VĂN PHÒNG CÔNG TY','360C Bến Vân Đồn','Phường Vĩnh Hội','TP.HCM',NULL,'Văn phòng',NULL,NULL);
INSERT INTO `projects` VALUES('38','DA-DEMO','Tòa nhà Khahomex (Dự án Mẫu)',NULL,NULL,NULL,NULL,'Văn phòng',NULL,NULL);


DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_services_supplier` (`supplier_id`),
  KEY `fk_services_project` (`project_id`),
  CONSTRAINT `fk_services_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_services_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `services` VALUES('1','c','c',NULL,NULL,NULL,'2026-01-03','0.00','30','',NULL,'ðang ho?t d?ng','2025-12-22 12:44:38',NULL);


DROP TABLE IF EXISTS `settings_device_statuses`;
CREATE TABLE `settings_device_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_name` varchar(100) NOT NULL,
  `color_class` varchar(50) DEFAULT 'status-default',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `status_name` (`status_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `settings_device_statuses` VALUES('5','Tốt','status-active','2025-12-22 13:38:16');
INSERT INTO `settings_device_statuses` VALUES('6','Cảnh báo','status-warning','2025-12-22 13:38:16');
INSERT INTO `settings_device_statuses` VALUES('7','Hỏng','status-error','2025-12-22 13:38:16');
INSERT INTO `settings_device_statuses` VALUES('8','Thanh lý','status-default','2025-12-22 13:38:16');


DROP TABLE IF EXISTS `settings_device_types`;
CREATE TABLE `settings_device_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL,
  `group_name` varchar(100) DEFAULT 'Văn phòng',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_name` (`type_name`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `settings_device_types` VALUES('1','CPU','Văn phòng','2025-12-22 13:28:39');
INSERT INTO `settings_device_types` VALUES('2','Màn hình','Văn phòng','2025-12-22 13:28:39');
INSERT INTO `settings_device_types` VALUES('3','Máy in','Văn phòng','2025-12-22 13:28:39');
INSERT INTO `settings_device_types` VALUES('4','Đầu đọc thẻ','Bãi xe','2025-12-22 13:28:39');
INSERT INTO `settings_device_types` VALUES('5','Barrier','Bãi xe','2025-12-22 13:28:39');
INSERT INTO `settings_device_types` VALUES('6','Vòng từ','Bãi xe','2025-12-22 13:28:39');
INSERT INTO `settings_device_types` VALUES('7','Bộ quang điện','Bãi xe','2025-12-22 13:28:39');
INSERT INTO `settings_device_types` VALUES('8','Camera','An ninh / Camera','2025-12-22 13:28:39');
INSERT INTO `settings_device_types` VALUES('9','Đầu ghi','An ninh / Camera','2025-12-22 13:28:39');
INSERT INTO `settings_device_types` VALUES('10','Switch','Hạ tầng mạng','2025-12-22 13:28:39');
INSERT INTO `settings_device_types` VALUES('11','Dây mạng','Hạ tầng mạng','2025-12-22 13:28:39');
INSERT INTO `settings_device_types` VALUES('12','UPS lưu điện','Hạ tầng mạng','2025-12-22 13:28:39');
INSERT INTO `settings_device_types` VALUES('13','RAM','Linh kiện','2025-12-22 13:28:39');
INSERT INTO `settings_device_types` VALUES('14','SSD / HDD','Linh kiện','2025-12-22 13:28:39');
INSERT INTO `settings_device_types` VALUES('15','Card màn hình','Linh kiện','2025-12-22 13:28:39');


DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ten_npp` varchar(255) NOT NULL COMMENT 'T??n nh?? ph??n ph???i / nh?? th???u',
  `nguoi_lien_he` varchar(255) DEFAULT NULL COMMENT 'T??n ng?????i li??n h???',
  `dien_thoai` varchar(50) DEFAULT NULL COMMENT 'S??? ??i???n tho???i',
  `email` varchar(255) DEFAULT NULL COMMENT 'Email li??n h???',
  `ghi_chu` text DEFAULT NULL COMMENT 'Ghi ch??',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Nh?? cung c???p thi???t b???';

INSERT INTO `suppliers` VALUES('1','ad','dá','áda','sda@gmaik','ád',NULL);
INSERT INTO `suppliers` VALUES('2','Phong Vũ IT','Nguyễn Văn A','0901234567',NULL,NULL,NULL);


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL COMMENT 'T??n ????ng nh???p',
  `fullname` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL COMMENT 'M???t kh???u (hash)',
  `role` varchar(50) DEFAULT NULL COMMENT 'Vai tr??: admin / it / xem',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Ng?????i d??ng h??? th???ng';

INSERT INTO `users` VALUES('1','admin','admin','$2y$10$xNwpeGTu1mo7fNSGGPXRn.j3NIAwyU4A86T8BDrzGFJmXwWW55IzS','admin','2025-12-17 12:00:27',NULL);
INSERT INTO `users` VALUES('2','it_kh','it_kh','$2y$10$xNwpeGTu1mo7fNSGGPXRn.j3NIAwyU4A86T8BDrzGFJmXwWW55IzS','it','2025-12-17 12:00:27',NULL);
INSERT INTO `users` VALUES('3','xem_thietbi','xem_thietbi','$2y$10$xNwpeGTu1mo7fNSGGPXRn.j3NIAwyU4A86T8BDrzGFJmXwWW55IzS','xem','2025-12-17 12:00:27',NULL);
INSERT INTO `users` VALUES('4','cmthang','Cao Minh Thắng','$2y$10$KXfkwUhQsPckc.PnDABgY.FDkQwHgndMl/8vyPi9CyQJlHn.y5OE2','it','2025-12-22 10:02:47',NULL);
INSERT INTO `users` VALUES('5','nttrung','Nguyễn Tất Trung','$2y$10$oU862C4l3EBFMm/MTcUe2O5rNWLw03SMZp5YZ/e0O.pd0KbxZhKH6','it','2025-12-22 10:02:58',NULL);


SET FOREIGN_KEY_CHECKS=1;