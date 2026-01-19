<?php
// Check permissions
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'it')) {
    set_message("Bạn không có quyền chỉnh sửa!", "error");
    echo '<script>window.location.href = "index.php?page=car_systems/list";</script>';
    exit;
}

$id = $_GET['id'] ?? 0;
if (!$id) {
    echo '<script>window.location.href = "index.php?page=car_systems/list";</script>';
    exit;
}

// Fetch Existing Data
try {
    $stmt = $pdo->prepare("SELECT * FROM car_system_configs WHERE id = ?");
    $stmt->execute([$id]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        set_message("Không tìm thấy cấu hình này.", "error");
        echo '<script>window.location.href = "index.php?page=car_systems/list";</script>';
        exit;
    }
} catch (PDOException $e) {
    set_message("Lỗi tải dữ liệu.", "error");
    echo '<script>window.location.href = "index.php?page=car_systems/list";</script>';
    exit;
}

// Fetch Projects
try {
    $projects = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $projects = [];
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'] ?? '';
    $server_ip = $_POST['server_ip'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    $folder_path = $_POST['folder_path'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $errors = [];
    if (empty($project_id)) $errors[] = "Vui lòng chọn dự án.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE car_system_configs SET project_id=?, server_ip=?, db_name=?, folder_path=?, username=?, password=? WHERE id=?");
            $stmt->execute([$project_id, $server_ip, $db_name, $folder_path, $username, $password, $id]);
            
            set_message("Cập nhật thành công!", "success");
            echo '<script>window.location.href = "index.php?page=car_systems/list";</script>';
            exit;
        } catch (PDOException $e) {
            $errors[] = "Lỗi cập nhật: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        foreach ($errors as $error) set_message($error, "error");
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Chỉnh sửa Cấu hình</h2>
    <div class="header-actions">
        <a href="index.php?page=car_systems/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="form-edit" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật</button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form id="form-edit" method="POST" action="" class="form-grid">
            <div class="form-group full-width">
                <label for="project_id" class="form-label">Dự án <span class="required">*</span></label>
                <div class="searchable-select-container">
                    <input type="text" id="project_search" class="form-control" placeholder="Gõ tên dự án để tìm kiếm..." value="<?php 
                        foreach($projects as $p) {
                            if($p['id'] == $config['project_id']) { echo htmlspecialchars($p['ten_du_an']); break; }
                        }
                    ?>" autocomplete="off" required>
                    <button type="button" class="btn-clear" id="btn-clear-project" title="Xóa chọn" style="display:block;"><i class="fas fa-times"></i></button>
                    <input type="hidden" name="project_id" id="project_id" value="<?= htmlspecialchars($config['project_id']) ?>">
                    <div id="project_dropdown" class="searchable-dropdown"></div>
                </div>
            </div>

            <script>
                // PROJECT DATA FROM PHP
                let localProjects = <?= json_encode($projects) ?>;
                let activeIndex = -1;

                function renderProjectDropdown(filter = '') {
                    const dropdown = document.getElementById('project_dropdown');
                    const filtered = localProjects.filter(p => p.ten_du_an.toLowerCase().includes(filter));
                    
                    if (filtered.length > 0) {
                        dropdown.innerHTML = filtered.map(p => 
                            `<div class="dropdown-item" onclick="selectProject(${p.id}, '${p.ten_du_an.replace(/'/g, "\\'")}')">
                                <span class="item-title">${p.ten_du_an}</span>
                            </div>`
                        ).join('');
                    } else {
                        dropdown.innerHTML = '<div class="no-results">Không tìm thấy dự án</div>';
                    }
                    
                    dropdown.style.display = 'block';
                    activeIndex = -1;
                }

                function selectProject(id, name) {
                    document.getElementById('project_search').value = name;
                    document.getElementById('project_id').value = id;
                    document.getElementById('project_dropdown').style.display = 'none';
                    document.getElementById('btn-clear-project').style.display = 'block';
                }

                document.addEventListener('DOMContentLoaded', function() {
                    const ps = document.getElementById('project_search');
                    const pd = document.getElementById('project_dropdown');
                    const pi = document.getElementById('project_id');
                    const cl = document.getElementById('btn-clear-project');

                    ps.addEventListener('input', function() { 
                        renderProjectDropdown(this.value.toLowerCase().trim()); 
                        cl.style.display = this.value.length > 0 ? 'block' : 'none';
                        if(!this.value) pi.value = '';
                    });

                    ps.addEventListener('focus', function() { 
                        renderProjectDropdown(this.value.toLowerCase().trim()); 
                    });

                    cl.addEventListener('click', () => { 
                        ps.value = ''; 
                        pi.value = ''; 
                        cl.style.display = 'none'; 
                        pd.style.display = 'none'; 
                    });

                    // Handle keyboard navigation
                    ps.addEventListener('keydown', function(e) {
                        const items = pd.querySelectorAll('.dropdown-item');
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            activeIndex = Math.min(activeIndex + 1, items.length - 1);
                            updateActiveItem(items);
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            activeIndex = Math.max(activeIndex - 1, -1);
                            updateActiveItem(items);
                        } else if (e.key === 'Enter') {
                            if (activeIndex > -1 && items[activeIndex]) {
                                e.preventDefault();
                                items[activeIndex].click();
                            }
                        }
                    });

                    function updateActiveItem(items) {
                        items.forEach((item, index) => {
                            item.classList.toggle('active', index === activeIndex);
                            if (index === activeIndex) item.scrollIntoView({ block: 'nearest' });
                        });
                    }

                    document.addEventListener('click', (e) => { 
                        if (!ps.contains(e.target) && !pd.contains(e.target) && e.target !== cl) pd.style.display = 'none';
                    });
                });
            </script>

            <style>
                .searchable-select-container { position: relative; width: 100%; }
                .searchable-select-container .form-control { 
                    padding-right: 35px; 
                    width: 100%; /* Ensure input takes full width */
                }
                
                .btn-clear {
                    position: absolute;
                    right: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: none;
                    border: none;
                    color: #94a3b8;
                    cursor: pointer;
                    padding: 5px;
                    display: none;
                    transition: color 0.2s;
                    z-index: 5;
                }
                .searchable-select-container:hover .btn-clear { display: block; }
                .btn-clear:hover { color: #ef4444; }

                .searchable-dropdown { 
                    position: absolute; top: 100%; left: 0; right: 0; 
                    background: white; border: 1px solid #cbd5e1; border-radius: 8px; 
                    margin-top: 5px; max-height: 250px; overflow-y: auto; 
                    z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); 
                    display: none; 
                }
                .dropdown-item { 
                    padding: 10px 15px; cursor: pointer; 
                    border-bottom: 1px solid #f1f5f9; text-align: left; 
                    transition: all 0.2s; font-size: 0.9rem;
                }
                .dropdown-item:last-child { border-bottom: none; }
                .dropdown-item:hover, .dropdown-item.active { background: #f8fafc; color: var(--primary-color); }
                .dropdown-item .item-title { display: block; font-weight: 600; }
                
                .no-results { padding: 15px; text-align: center; color: #94a3b8; font-style: italic; font-size: 0.9rem; }
            </style>

            <div class="form-group">
                <label for="server_ip" class="form-label">Máy chủ (IP/Domain)</label>
                <input type="text" name="server_ip" id="server_ip" class="form-control" value="<?= htmlspecialchars($config['server_ip']) ?>">
            </div>

            <div class="form-group">
                <label for="db_name" class="form-label">Tên Cơ sở dữ liệu (Database)</label>
                <input type="text" name="db_name" id="db_name" class="form-control" value="<?= htmlspecialchars($config['db_name']) ?>">
            </div>

            <div class="form-group full-width">
                <label for="folder_path" class="form-label">Đường dẫn thư mục cài đặt</label>
                <input type="text" name="folder_path" id="folder_path" class="form-control" value="<?= htmlspecialchars($config['folder_path']) ?>">
            </div>

            <div class="form-group">
                <label for="username" class="form-label">Tài khoản quản trị</label>
                <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($config['username']) ?>">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Mật khẩu</label>
                <div class="input-group-password" style="width: 100%;">
                    <input type="text" name="password" id="password" class="form-control" style="width: 100%;" value="<?= htmlspecialchars($config['password']) ?>">
                </div>
            </div>
        </form>
    </div>
</div>