<?php
$id = $_GET['id'] ?? null;
if (!$id) { safe_redirect("index.php?page=suppliers/list"); }

// 1. XỬ LÝ POST TẠI CHỖ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['ten_npp'])) {
        set_message('error', 'Tên nhà cung cấp là bắt buộc.');
    } else {
        try {
            $contacts = [];
            if (isset($_POST['contact_names']) && is_array($_POST['contact_names'])) {
                for ($i = 0; $i < count($_POST['contact_names']); $i++) {
                    if (!empty($_POST['contact_names'][$i])) {
                        $contacts[] = [
                            'name' => $_POST['contact_names'][$i],
                            'phone' => $_POST['contact_phones'][$i] ?? '',
                            'role' => $_POST['contact_roles'][$i] ?? ''
                        ];
                    }
                }
            }
            $json_contacts = !empty($contacts) ? json_encode($contacts, JSON_UNESCAPED_UNICODE) : null;
            
            $stmt = $pdo->prepare("UPDATE suppliers SET ten_npp=?, ghi_chu=?, thong_tin_lien_he=? WHERE id=?");
            $stmt->execute([$_POST['ten_npp'], $_POST['ghi_chu'] ?? '', $json_contacts, $id]);
            
            set_message('success', 'Cập nhật nhà cung cấp thành công!');
            safe_redirect("index.php?page=suppliers/list");
        } catch (PDOException $e) {
            set_message('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}

// 2. LẤY DỮ LIỆU HIỂN THỊ
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$s = $stmt->fetch();

if (!$s) { 
    set_message('error', 'Không tìm thấy nhà cung cấp.'); 
    safe_redirect("index.php?page=suppliers/list"); 
}

$contacts_json = isset($s['thong_tin_lien_he']) ? $s['thong_tin_lien_he'] : '';
$contacts = !empty($contacts_json) ? json_decode($contacts_json, true) : [];
?>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Sửa Nhà cung cấp</h2>
    <div class="header-actions">
        <a href="index.php?page=suppliers/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="edit-sup-form" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật</button>
    </div>
</div>

<form action="index.php?page=suppliers/edit&id=<?php echo $id; ?>" method="POST" id="edit-sup-form" class="edit-layout">
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-info-circle"></i> Thông tin Cơ bản</h3>
            </div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label>Tên Nhà cung cấp <span class="required">*</span></label>
                    <input type="text" name="ten_npp" required value="<?php echo htmlspecialchars($s['ten_npp'] ?? ''); ?>" class="input-highlight">
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="ghi_chu" rows="5"><?php echo htmlspecialchars($s['ghi_chu'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="right-panel">
        <div class="card">
            <div class="card-header-custom" style="display: flex; justify-content: space-between; align-items: center;">
                <h3><i class="fas fa-address-book"></i> Thông tin Liên hệ</h3>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addContactRow()"><i class="fas fa-plus"></i> Thêm</button>
            </div>
            <div class="card-body-custom">
                <div id="contacts-container">
                    <?php if (empty($contacts)): ?>
                        <div class="contact-row-item">
                            <div class="form-group">
                                <label>Họ tên</label>
                                <input type="text" name="contact_names[]" placeholder="VD: A Sơn" class="form-control-sm">
                            </div>
                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="text" name="contact_phones[]" placeholder="0123..." class="form-control-sm">
                            </div>
                            <div class="form-group">
                                <label>Chức vụ / Nội dung</label>
                                <input type="text" name="contact_roles[]" placeholder="VD: Bảo trì hệ thống" class="form-control-sm">
                            </div>
                            <button type="button" class="btn-remove-row" onclick="removeContactRow(this)" title="Xóa"><i class="fas fa-times"></i></button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($contacts as $c): ?>
                            <div class="contact-row-item">
                                <div class="form-group">
                                    <label>Họ tên</label>
                                    <input type="text" name="contact_names[]" value="<?php echo htmlspecialchars($c['name'] ?? ''); ?>" class="form-control-sm">
                                </div>
                                <div class="form-group">
                                    <label>Số điện thoại</label>
                                    <input type="text" name="contact_phones[]" value="<?php echo htmlspecialchars($c['phone'] ?? ''); ?>" class="form-control-sm">
                                </div>
                                <div class="form-group">
                                    <label>Chức vụ / Nội dung</label>
                                    <input type="text" name="contact_roles[]" value="<?php echo htmlspecialchars($c['role'] ?? ''); ?>" class="form-control-sm">
                                </div>
                                <button type="button" class="btn-remove-row" onclick="removeContactRow(this)" title="Xóa"><i class="fas fa-times"></i></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.contact-row-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 15px;
    position: relative;
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
}
.btn-remove-row {
    position: absolute;
    top: -10px;
    right: -10px;
    width: 24px;
    height: 24px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.btn-remove-row:hover { background: #dc2626; }

@media (min-width: 1200px) {
    .contact-row-item {
        grid-template-columns: 1fr 1fr 1fr;
    }
}
</style>

<script>
function addContactRow() {
    const container = document.getElementById('contacts-container');
    const div = document.createElement('div');
    div.className = 'contact-row-item';
    div.innerHTML = `
        <div class="form-group">
            <label>Họ tên</label>
            <input type="text" name="contact_names[]" placeholder="VD: A Sơn" class="form-control-sm">
        </div>
        <div class="form-group">
            <label>Số điện thoại</label>
            <input type="text" name="contact_phones[]" placeholder="0123..." class="form-control-sm">
        </div>
        <div class="form-group">
            <label>Chức vụ / Nội dung</label>
            <input type="text" name="contact_roles[]" placeholder="VD: Bảo trì hệ thống" class="form-control-sm">
        </div>
        <button type="button" class="btn-remove-row" onclick="removeContactRow(this)" title="Xóa"><i class="fas fa-times"></i></button>
    `;
    container.appendChild(div);
}

function removeContactRow(btn) {
    const row = btn.closest('.contact-row-item');
    const container = document.getElementById('contacts-container');
    if (container.querySelectorAll('.contact-row-item').length > 1) {
        row.remove();
    } else {
        row.querySelectorAll('input').forEach(input => input.value = '');
    }
}
</script>