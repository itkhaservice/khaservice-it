<?php
// modules/proposals/edit.php
$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['error'] = "Thiếu ID đề xuất.";
    header("Location: index.php?page=proposals/list");
    exit;
}

// Fetch Main Proposal
$stmt = $pdo->prepare("SELECT * FROM internal_proposals WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$prop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prop) {
    $_SESSION['error'] = "Không tìm thấy đề xuất.";
    header("Location: index.php?page=proposals/list");
    exit;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $proposal_number = $_POST['proposal_number'];
        $proposal_date = $_POST['proposal_date'];
        $title = $_POST['title'];
        $recipient = $_POST['recipient'];
        $content_summary = $_POST['content_summary'];
        $vat_percentage = (int)$_POST['vat_percentage'];
        $amount_in_words = $_POST['amount_in_words'];
        $notes = $_POST['notes'];
        $proposer_id = $_POST['proposer_id'];
        $department_head_id = $_POST['department_head_id'];
        $status = $_POST['status'];

        // Update Main Proposal
        $stmt_up = $pdo->prepare("UPDATE internal_proposals SET 
            proposal_number = ?, proposal_date = ?, title = ?, recipient = ?, 
            content_summary = ?, vat_percentage = ?, amount_in_words = ?, 
            notes = ?, proposer_id = ?, department_head_id = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?");
        $stmt_up->execute([$proposal_number, $proposal_date, $title, $recipient, $content_summary, $vat_percentage, $amount_in_words, $notes, $proposer_id, $department_head_id, $status, $id]);

        // Refresh Items: Delete old ones and insert new ones
        $pdo->prepare("DELETE FROM proposal_items WHERE proposal_id = ?")->execute([$id]);
        
        $total_before_vat = 0;
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            $stmt_item = $pdo->prepare("INSERT INTO proposal_items (proposal_id, item_name, unit, quantity, unit_price, total_price, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($_POST['items'] as $index => $item) {
                if (empty($item['name'])) continue;
                $qty = (float)$item['qty'];
                $price = (float)$item['price'];
                $line_total = $qty * $price;
                $total_before_vat += $line_total;
                $stmt_item->execute([$id, $item['name'], $item['unit'], $qty, $price, $line_total, $index]);
            }
        }

        // Update Totals
        $total_after_vat = $total_before_vat * (1 + ($vat_percentage / 100));
        $pdo->prepare("UPDATE internal_proposals SET total_amount_before_vat = ?, total_amount_after_vat = ? WHERE id = ?")
            ->execute([$total_before_vat, $total_after_vat, $id]);

        $pdo->commit();
        $_SESSION['success'] = "Cập nhật đề xuất thành công!";
        header("Location: index.php?page=proposals/list");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
}

// Fetch Items
$stmt_items = $pdo->prepare("SELECT * FROM proposal_items WHERE proposal_id = ? ORDER BY sort_order ASC");
$stmt_items->execute([$id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

$it_users = $pdo->query("SELECT id, fullname FROM users WHERE role IN ('admin', 'IT') ORDER BY fullname ASC")->fetchAll();
?>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Chỉnh sửa Đề xuất</h2>
    <a href="index.php?page=proposals/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>

<form action="" method="POST" id="proposal-form">
    <div class="form-container" style="margin: 0 auto;">
        <div class="card">
            <h3 style="margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Thông tin chung</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Tiêu đề / Căn cứ <span class="required">*</span></label>
                    <input type="text" name="title" required value="<?php echo htmlspecialchars($prop['title']); ?>">
                </div>
                <div class="form-group">
                    <label>Số đề xuất</label>
                    <input type="text" name="proposal_number" value="<?php echo htmlspecialchars($prop['proposal_number']); ?>">
                </div>
                <div class="form-group">
                    <label>Ngày lập <span class="required">*</span></label>
                    <input type="date" name="proposal_date" required value="<?php echo $prop['proposal_date']; ?>">
                </div>
                <div class="form-group">
                    <label>Kính gửi</label>
                    <input type="text" name="recipient" value="<?php echo htmlspecialchars($prop['recipient']); ?>">
                </div>
                <div class="form-group full-width">
                    <label>Nội dung tóm tắt</label>
                    <textarea name="content_summary" rows="2"><?php echo htmlspecialchars($prop['content_summary']); ?></textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Chi tiết hạng mục</h3>
            <div class="table-container" style="border: none;">
                <table class="content-table" id="items-table">
                    <thead>
                        <tr>
                            <th width="50" class="text-center">STT</th>
                            <th>Nội dung</th>
                            <th width="100">ĐVT</th>
                            <th width="100">Số lượng</th>
                            <th width="150">Đơn giá</th>
                            <th width="150">Thành tiền</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                        <?php foreach($items as $index => $item): ?>
                        <tr>
                            <td class="stt-col text-center"><?php echo $index + 1; ?></td>
                            <td><input type="text" name="items[<?php echo $index; ?>][name]" class="item-input" required value="<?php echo htmlspecialchars($item['item_name']); ?>"></td>
                            <td><input type="text" name="items[<?php echo $index; ?>][unit]" class="item-input text-center" value="<?php echo htmlspecialchars($item['unit']); ?>"></td>
                            <td><input type="number" name="items[<?php echo $index; ?>][qty]" class="item-input text-center qty-input" value="<?php echo (float)$item['quantity']; ?>" step="any" oninput="calculateRow(this)"></td>
                            <td><input type="number" name="items[<?php echo $index; ?>][price]" class="item-input text-right price-input" value="<?php echo (float)$item['unit_price']; ?>" step="any" oninput="calculateRow(this)"></td>
                            <td class="text-right font-bold line-total"><?php echo number_format($item['total_price'], 0, ',', '.'); ?></td>
                            <td class="text-center"><button type="button" class="btn-remove-row" onclick="removeRow(this)"><i class="fas fa-times"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="addRow()"><i class="fas fa-plus"></i> Thêm dòng</button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 20px; gap: 40px;">
                <div style="width: 300px;">
                    <div class="form-group" style="flex-direction: row; align-items: center; justify-content: space-between;">
                        <label>Tổng cộng:</label>
                        <span id="subtotal-display" class="font-bold"><?php echo number_format($prop['total_amount_before_vat'], 0, ',', '.'); ?> ₫</span>
                    </div>
                    <div class="form-group" style="flex-direction: row; align-items: center; justify-content: space-between; margin-top: 10px;">
                        <label>Thuế VAT (%):</label>
                        <input type="number" name="vat_percentage" id="vat_percentage" value="<?php echo $prop['vat_percentage']; ?>" style="width: 80px; text-align: right;" oninput="calculateAll()">
                    </div>
                    <div class="form-group" style="flex-direction: row; align-items: center; justify-content: space-between; margin-top: 10px; border-top: 1px solid #eee; padding-top: 10px;">
                        <label style="font-size: 1.1rem; color: var(--primary-color);">THÀNH TIỀN:</label>
                        <span id="total-display" class="font-bold" style="font-size: 1.1rem; color: var(--primary-color);"><?php echo number_format($prop['total_amount_after_vat'], 0, ',', '.'); ?> ₫</span>
                    </div>
                </div>
            </div>

            <div class="form-group full-width" style="margin-top: 20px;">
                <label>Số tiền bằng chữ</label>
                <input type="text" name="amount_in_words" id="amount_in_words" value="<?php echo htmlspecialchars($prop['amount_in_words']); ?>">
            </div>
        </div>

        <div class="card">
            <div class="form-grid">
                <div class="form-group">
                    <label>Người đề xuất</label>
                    <select name="proposer_id">
                        <?php foreach($it_users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $u['id'] == $prop['proposer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['fullname']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Trưởng bộ phận phê duyệt</label>
                    <select name="department_head_id">
                        <option value="">-- Chọn trưởng bộ phận --</option>
                        <?php foreach($it_users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $u['id'] == $prop['department_head_id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['fullname']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Ghi chú</label>
                    <textarea name="notes" rows="2"><?php echo htmlspecialchars($prop['notes']); ?></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <select name="status" class="form-select-sm" style="width: 150px; margin-right: 10px;">
                    <option value="Draft" <?= $prop['status'] == 'Draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="Pending" <?= $prop['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Approved" <?= $prop['status'] == 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Rejected" <?= $prop['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Thay đổi</button>
            </div>
        </div>
    </div>
</form>

<script>
function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(num);
}

// HÀM ĐỌC SỐ TIỀN SANG TIẾNG VIỆT
function docSoTien(soTien) {
    if (soTien == 0) return '';
    if (soTien < 0) return 'Số tiền không hợp lệ';
    
    const ChuSo = ["không", "một", "hai", "ba", "bốn", "năm", "sáu", "bảy", "tám", "chín"];
    const Tien = ["", " nghìn", " triệu", " tỷ", " nghìn tỷ", " triệu tỷ"];

    function docBlock(so) {
        let tram, chuc, donvi;
        let ketqua = "";
        tram = Math.floor(so / 100);
        chuc = Math.floor((so % 100) / 10);
        donvi = so % 10;
        if (tram == 0 && chuc == 0 && donvi == 0) return "";
        if (tram != 0) {
            ketqua += ChuSo[tram] + " trăm ";
            if ((chuc == 0) && (donvi != 0)) ketqua += "lẻ ";
        }
        if ((chuc != 0) && (chuc != 1)) {
            ketqua += ChuSo[chuc] + " mươi ";
            if ((chuc == 0) && (donvi != 0)) ketqua = ketqua + "lẻ ";
        }
        if (chuc == 1) ketqua += "mười ";
        switch (donvi) {
            case 1:
                if ((chuc != 0) && (chuc != 1)) ketqua += "mốt ";
                else ketqua += ChuSo[donvi] + " ";
                break;
            case 5:
                if (chuc == 0) ketqua += ChuSo[donvi] + " ";
                else ketqua += "lăm ";
                break;
            default:
                if (donvi != 0) ketqua += ChuSo[donvi] + " ";
                break;
        }
        return ketqua;
    }

    let ketqua = "";
    let i = 0;
    let so = 0;
    let mangSo = [];
    
    let tempSo = Math.floor(soTien);
    while (tempSo > 0) {
        mangSo.push(tempSo % 1000);
        tempSo = Math.floor(tempSo / 1000);
    }

    for (i = mangSo.length - 1; i >= 0; i--) {
        so = mangSo[i];
        ketqua += docBlock(so) + Tien[i] + " ";
    }
    
    ketqua = ketqua.trim();
    if (ketqua.length > 0) {
        ketqua = ketqua.charAt(0).toUpperCase() + ketqua.slice(1) + " đồng.";
    }
    return ketqua;
}

function addRow() {
    const tbody = document.getElementById('items-body');
    const index = tbody.children.length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="stt-col text-center">${index + 1}</td>
        <td><input type="text" name="items[${index}][name]" class="item-input" required placeholder="Tên hàng hóa, dịch vụ..."></td>
        <td><input type="text" name="items[${index}][unit]" class="item-input text-center"></td>
        <td><input type="number" name="items[${index}][qty]" class="item-input text-center qty-input" value="1" step="any" oninput="calculateRow(this)"></td>
        <td><input type="number" name="items[${index}][price]" class="item-input text-right price-input" value="0" step="any" oninput="calculateRow(this)"></td>
        <td class="text-right font-bold line-total">0</td>
        <td class="text-center"><button type="button" class="btn-remove-row" onclick="removeRow(this)"><i class="fas fa-times"></i></button></td>
    `;
    tbody.appendChild(tr);
}

function removeRow(btn) {
    btn.closest('tr').remove();
    const rows = document.querySelectorAll('#items-body tr');
    rows.forEach((row, idx) => {
        row.querySelector('.stt-col').textContent = idx + 1;
    });
    calculateAll();
}

function calculateRow(input) {
    const tr = input.closest('tr');
    const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
    const price = parseFloat(tr.querySelector('.price-input').value) || 0;
    const total = qty * price;
    tr.querySelector('.line-total').textContent = formatNumber(total);
    calculateAll();
}

function calculateAll() {
    let subtotal = 0;
    document.querySelectorAll('.qty-input').forEach((input, i) => {
        const tr = input.closest('tr');
        const qty = parseFloat(input.value) || 0;
        const price = parseFloat(tr.querySelector('.price-input').value) || 0;
        subtotal += qty * price;
    });

    const vat = parseFloat(document.getElementById('vat_percentage').value) || 0;
    const total = Math.round(subtotal * (1 + (vat / 100)));

    document.getElementById('subtotal-display').textContent = formatNumber(subtotal) + ' ₫';
    document.getElementById('total-display').textContent = formatNumber(total) + ' ₫';
    
    document.getElementById('amount_in_words').value = docSoTien(total);
}
</script>

<style>
/* TABLE STYLING */
#items-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 10px; }
#items-table th { background: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 12px 10px; border-bottom: 2px solid #e2e8f0; }
#items-table td { padding: 8px 5px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

.item-input { 
    width: 100%; 
    border: 1px solid transparent; 
    background: transparent; 
    padding: 8px 10px; 
    border-radius: 6px; 
    font-size: 0.95rem;
    transition: all 0.2s;
}
.item-input:hover { background: #f1f5f9; border-color: #cbd5e1; }
.item-input:focus { background: #fff; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(36, 162, 92, 0.1); outline: none; }

.btn-remove-row {
    width: 28px; height: 28px; border-radius: 50%; border: none; background: #fee2e2; color: #ef4444; 
    cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; margin: 0 auto;
}
.btn-remove-row:hover { background: #fecaca; color: #dc2626; transform: scale(1.1); }

.line-total { font-size: 0.95rem; color: #334155; padding-right: 10px !important; }
</style>
