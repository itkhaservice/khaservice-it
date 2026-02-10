// File: assets/js/form_builder.js
// JavaScript logic for advanced form builder UI.

let questionCounter = 0;

document.addEventListener('DOMContentLoaded', () => {
    const addQuestionBtn = document.getElementById('add-question-btn');
    const quickAddBtn = document.querySelector('.quick-add-btn');
    const mainForm = document.getElementById('create-form') || document.getElementById('edit-form');
    const colorInput = document.getElementById('theme_color');

    // Toast Container
    if (!document.getElementById('toast-container')) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const addNewQuestion = () => {
        const questionContainer = document.getElementById('question-container');
        // remove the whole placeholder wrapper (not just the inner <p>)
        const placeholderWrapper = questionContainer.querySelector('.text-center');
        if (placeholderWrapper) placeholderWrapper.remove();
        
        questionCounter++;
        const newQuestion = createQuestionElement(questionCounter);
        questionContainer.appendChild(newQuestion);
        
        // Scroll to new question if added via quick btn
        newQuestion.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    if (addQuestionBtn) addQuestionBtn.addEventListener('click', addNewQuestion);
    if (quickAddBtn) quickAddBtn.addEventListener('click', duplicatePreviousQuestion);

    if (mainForm) {
        mainForm.addEventListener('submit', handleFormSubmit);
    }

    // Theme Color Real-time Preview
    if (colorInput) {
        colorInput.addEventListener('input', (e) => {
            updateThemePreview(e.target.value);
        });
        // Initial run
        updateThemePreview(colorInput.value);
    }

    if (typeof existingFormData !== 'undefined') {
        populateForm(existingFormData);
    }
});

function updateThemePreview(color) {
    // Apply color ONLY to form builder container, not to global root
    const formContainer = document.querySelector('.form-module-container');
    if (formContainer) {
        formContainer.style.setProperty('--f-primary', color, 'important');
        // Also update variations
        formContainer.style.setProperty('--f-primary-dark', `${color}dd`, 'important');
    }
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let icon = 'fa-check-circle';
    if (type === 'error') icon = 'fa-exclamation-circle';
    if (type === 'info') icon = 'fa-info-circle';

    toast.innerHTML = `
        <i class="fas ${icon}"></i>
        <div class="toast-message">${message}</div>
    `;

    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);

    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function populateForm(data) {
    const questionContainer = document.getElementById('question-container');
    if (!questionContainer) return;

    if (data.questions && data.questions.length > 0) {
        // Xóa hoàn toàn placeholder hoặc nội dung cũ
        questionContainer.innerHTML = ''; 
        
        data.questions.forEach(q_data => {
            questionCounter++;
            const questionBlock = createQuestionElement(questionCounter);
            
            if (q_data.id) {
                questionBlock.setAttribute('data-db-id', q_data.id);
            }
            
            if (q_data.logic_config) {
                const logicInput = document.createElement('input');
                logicInput.type = 'hidden';
                logicInput.className = 'logic-data';
                logicInput.value = q_data.logic_config;
                questionBlock.appendChild(logicInput);
                
                setTimeout(() => {
                    const btn = questionBlock.querySelector('.btn-logic');
                    if(btn) btn.style.color = 'var(--f-primary)';
                }, 100);
            }
            
            // Điền tiêu đề và loại câu hỏi
            const titleInput = questionBlock.querySelector('.q-input-title');
            const typeSelect = questionBlock.querySelector('.q-select-type');
            const reqCheckbox = questionBlock.querySelector('input[type="checkbox"]');

            if (titleInput) titleInput.value = q_data.title;
            if (typeSelect) typeSelect.value = q_data.type;
            if (reqCheckbox) reqCheckbox.checked = q_data.is_required;

            const optionsContainer = questionBlock.querySelector('.q-options-area');
            updateQuestionOptionsUI(q_data.type, optionsContainer, `q_${questionCounter}`);

            if (q_data.type === 'linear_scale' && q_data.options.length >= 4) {
                const minS = optionsContainer.querySelector('.scale-min');
                const maxS = optionsContainer.querySelector('.scale-max');
                const minL = optionsContainer.querySelector('.scale-min-label');
                const maxL = optionsContainer.querySelector('.scale-max-label');

                if (minS) minS.value = q_data.options[0].text;
                if (maxS) maxS.value = q_data.options[1].text;
                if (minL) minL.value = q_data.options[2].text;
                if (maxL) maxL.value = q_data.options[3].text;
                
                // Trigger update labels
                if (minS) minS.dispatchEvent(new Event('change'));
            } else if (q_data.options && q_data.options.length > 0) {
                const gridTypes = ['multiple_choice_grid', 'checkbox_grid'];
                
                if (gridTypes.includes(q_data.type)) {
                    const rowsList = optionsContainer.querySelector('.rows-list');
                    const colsList = optionsContainer.querySelector('.cols-list');
                    if (rowsList) rowsList.innerHTML = '';
                    if (colsList) colsList.innerHTML = '';
                    
                    q_data.options.forEach((opt, idx) => {
                        if (opt.type === 'row') {
                            const el = createOptionElement(`q_${questionCounter}`, idx + 1, 'row');
                            el.querySelector('input').value = opt.text;
                            if (rowsList) rowsList.appendChild(el);
                        } else if (opt.type === 'column') {
                            const el = createOptionElement(`q_${questionCounter}`, idx + 1, 'column');
                            el.querySelector('input').value = opt.text;
                            if (colsList) colsList.appendChild(el);
                        }
                    });
                } else {
                    const optionsList = optionsContainer.querySelector('.options-list');
                    if (optionsList) {
                        optionsList.innerHTML = '';
                        q_data.options.forEach((opt, index) => {
                            const optionElement = createOptionElement(`q_${questionCounter}`, index + 1);
                            const optInput = optionElement.querySelector('.q-opt-input');
                            if (optInput) optInput.value = opt.text;
                            optionsList.appendChild(optionElement);
                        });
                    }
                }
            }
            
            questionContainer.appendChild(questionBlock);
        });
    } else {
         checkEmptyContainer();
    }
}

async function handleFormSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const submitButton = document.querySelector(`button[form="${form.id}"]`);
    const originalButtonText = submitButton.innerHTML;
    // Front-end validation: title required. For questions, allow saving draft without questions but prevent publishing without questions.
    const titleVal = (formData.get('form_title') || '').toString().trim();
    const questionCount = document.querySelectorAll('.question-block-item').length;
    const statusVal = (formData.get('form_status') || 'draft').toString();
    if (!titleVal) {
        showToast('Tiêu đề biểu mẫu là bắt buộc.', 'error');
        const titleInput = document.getElementById('form_title'); if (titleInput) titleInput.focus();
        return;
    }
    // If publishing and no questions -> block
    if (questionCount === 0 && statusVal === 'published') {
        showToast('Không thể xuất bản biểu mẫu khi chưa có câu hỏi. Vui lòng thêm ít nhất một câu hỏi.', 'error');
        return;
    }
    // If saving draft with no questions -> allow but warn after success
    const warnNoQuestions = questionCount === 0 && statusVal !== 'published';

    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';

    const data = {
        id: formData.get('form_id'),
        title: formData.get('form_title'),
        description: formData.get('form_description'),
        status: formData.get('form_status'),
        expires_at: formData.get('expires_at'),
        response_limit: formData.get('response_limit'),
        theme_color: formData.get('theme_color'),
        thank_you_message: formData.get('thank_you_message'),
        questions: []
    };

    document.querySelectorAll('.question-block-item').forEach(block => {
        const titleInput = block.querySelector('.q-input-title');
        const typeSelect = block.querySelector('.q-select-type');
        const reqCheckbox = block.querySelector('input[type="checkbox"]');

        if (!titleInput) return; // Bỏ qua nếu không tìm thấy tiêu đề

        const question = {
            id: block.getAttribute('data-db-id') || null,
            title: titleInput.value,
            type: typeSelect ? typeSelect.value : 'text',
            is_required: reqCheckbox ? reqCheckbox.checked : false,
            logic_config: block.querySelector('.logic-data') ? block.querySelector('.logic-data').value : null,
            options: []
        };

        const optionsContainer = block.querySelector('.q-options-area');

        if (question.type === 'linear_scale' && optionsContainer) {
            const minS = optionsContainer.querySelector('.scale-min');
            const maxS = optionsContainer.querySelector('.scale-max');
            const minL = optionsContainer.querySelector('.scale-min-label');
            const maxL = optionsContainer.querySelector('.scale-max-label');

            question.options = [
                { text: minS ? minS.value : '1', type: 'choice' },
                { text: maxS ? maxS.value : '5', type: 'choice' },
                { text: minL ? minL.value : '', type: 'choice' },
                { text: maxL ? maxL.value : '', type: 'choice' }
            ];
        } else if (optionsContainer) {
            optionsContainer.querySelectorAll('.option-item').forEach(optionEl => {
                const optionInput = optionEl.querySelector('.q-opt-input');
                const optionType = optionEl.dataset.type || 'choice';
                if (optionInput && optionInput.value) {
                    question.options.push({ text: optionInput.value, type: optionType });
                }
            });
        }
        data.questions.push(question);
    });

    const apiAction = data.id ? 'update_form' : 'save_form';
    const apiUrl = finalBaseUrl + `public/api/forms_api.php?action=${apiAction}`;

    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            playAudio('success');
            // If we saved a draft without questions, show an informational toast
            if (warnNoQuestions) {
                showToast('Đã lưu biểu mẫu dưới dạng bản nháp. Lưu ý: biểu mẫu chưa có câu hỏi.', 'info');
            } else {
                showToast(result.message, 'success');
            }
            setTimeout(() => {
                window.location.href = result.redirect_url;
            }, 1000);
        } else {
            throw new Error(result.message || 'Lỗi không xác định.');
        }
    } catch (error) {
        playAudio('error');
        showToast(error.message, 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    }
}

function createQuestionElement(index) {
    const questionId = `q_${index}`;
    const questionWrapper = document.createElement('div');
    questionWrapper.className = 'question-block-item';
    questionWrapper.setAttribute('data-question-id', questionId);

    questionWrapper.innerHTML = `
        <div class="q-main-row">
            <input type="text" name="questions[${questionId}][title]" class="q-input-title" placeholder="Nhập câu hỏi của bạn tại đây...">
            <select name="questions[${questionId}][type]" class="q-select-type question-type-select">
                <optgroup label="Cơ bản">
                    <option value="text">Trả lời ngắn</option>
                    <option value="textarea">Đoạn văn</option>
                    <option value="multiple_choice" selected>Trắc nghiệm</option>
                    <option value="checkboxes">Hộp kiểm</option>
                    <option value="dropdown">Menu thả xuống</option>
                </optgroup>
                <optgroup label="Thời gian & Số liệu">
                    <option value="date">Ngày</option>
                    <option value="time">Giờ</option>
                    <option value="datetime">Ngày & Giờ</option>
                    <option value="number">Số</option>
                </optgroup>
                <optgroup label="Nâng cao">
                    <option value="file">Tải tệp lên</option>
                    <option value="linear_scale">Thang đo tuyến tính</option>
                    <option value="multiple_choice_grid">Lưới trắc nghiệm</option>
                    <option value="checkbox_grid">Lưới hộp kiểm</option>
                </optgroup>
            </select>
        </div>
        <div class="question-options-container q-options-area"></div>
        <div class="q-footer-actions">
            <div style="display: flex; align-items: center; gap: 8px;">
                <label class="switch">
                    <input type="checkbox" name="questions[${questionId}][required]">
                    <span class="slider round"></span>
                </label>
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--f-text-light);">BẮT BUỘC</span>
            </div>
            <div style="display: flex; gap: 5px;">
                <button type="button" class="btn-icon-sm btn-logic" title="Điều kiện hiển thị"><i class="fas fa-brain"></i></button>
                <button type="button" class="btn-icon-sm btn-delete-question" title="Xóa câu hỏi"><i class="fas fa-trash-alt" style="color: #ef4444;"></i></button>
            </div>
        </div>
    `;

    const typeSelect = questionWrapper.querySelector('.question-type-select');
    const optionsContainer = questionWrapper.querySelector('.question-options-container');

    updateQuestionOptionsUI(typeSelect.value, optionsContainer, questionId);
    typeSelect.addEventListener('change', (e) => updateQuestionOptionsUI(e.target.value, optionsContainer, questionId));

    questionWrapper.querySelector('.btn-logic').addEventListener('click', () => {
        setupQuestionLogic(questionWrapper);
    });

    const deleteBtn = questionWrapper.querySelector('.btn-delete-question');
    deleteBtn.addEventListener('click', () => {
        questionWrapper.remove();
        checkEmptyContainer();
    });

    return questionWrapper;
}

function duplicatePreviousQuestion(){
    const questionContainer = document.getElementById('question-container');
    const existing = questionContainer.querySelectorAll('.question-block-item');
    if (!existing || existing.length === 0) {
        addNewQuestion();
        return;
    }
    const last = existing[existing.length - 1];
    questionCounter++;
    const newQ = createQuestionElement(questionCounter);

    // Copy title
    const lastTitle = last.querySelector('.q-input-title');
    const newTitle = newQ.querySelector('.q-input-title');
    if (lastTitle && newTitle) newTitle.value = lastTitle.value;

    // Copy required
    const lastReq = last.querySelector('input[type="checkbox"]');
    const newReq = newQ.querySelector('input[type="checkbox"]');
    if (lastReq && newReq) newReq.checked = lastReq.checked;

    // Copy logic-data if any
    const lastLogic = last.querySelector('.logic-data');
    if (lastLogic) {
        const hid = document.createElement('input');
        hid.type = 'hidden';
        hid.className = 'logic-data';
        hid.value = lastLogic.value;
        newQ.appendChild(hid);
        // visually mark logic button
        const btn = newQ.querySelector('.btn-logic');
        if (btn) btn.style.color = 'var(--f-primary)';
    }

    // Copy type and options
    const lastType = last.querySelector('.q-select-type');
    const newType = newQ.querySelector('.q-select-type');
    const newOptionsArea = newQ.querySelector('.q-options-area');
    const lastOptionsArea = last.querySelector('.q-options-area');
    if (lastType && newType && lastOptionsArea && newOptionsArea) {
        newType.value = lastType.value;
        // render proper options UI for this type
        updateQuestionOptionsUI(newType.value, newOptionsArea, `q_${questionCounter}`);

        // copy different option structures
        // standard options
        const lastOptionsList = lastOptionsArea.querySelectorAll('.option-item');
        if (lastOptionsList && lastOptionsList.length > 0) {
            const newOptionsList = newOptionsArea.querySelector('.options-list');
            if (newOptionsList) {
                newOptionsList.innerHTML = '';
                lastOptionsList.forEach((opt, idx) => {
                    const el = createOptionElement(`q_${questionCounter}`, idx + 1, opt.dataset.type || 'choice');
                    const input = el.querySelector('.q-opt-input');
                    const srcInput = opt.querySelector('.q-opt-input');
                    if (input && srcInput) input.value = srcInput.value;
                    newOptionsList.appendChild(el);
                });
            }
        }

        // grid types
        const lastRows = lastOptionsArea.querySelectorAll('.rows-list .option-item');
        const lastCols = lastOptionsArea.querySelectorAll('.cols-list .option-item');
        if ((lastRows && lastRows.length>0) || (lastCols && lastCols.length>0)){
            const rowsList = newOptionsArea.querySelector('.rows-list');
            const colsList = newOptionsArea.querySelector('.cols-list');
            if (rowsList) rowsList.innerHTML = '';
            if (colsList) colsList.innerHTML = '';
            lastRows.forEach((r, idx) => {
                const el = createOptionElement(`q_${questionCounter}`, idx+1, 'row');
                const inp = el.querySelector('input'); if (inp) inp.value = r.querySelector('input').value;
                rowsList.appendChild(el);
            });
            lastCols.forEach((c, idx) => {
                const el = createOptionElement(`q_${questionCounter}`, idx+1, 'column');
                const inp = el.querySelector('input'); if (inp) inp.value = c.querySelector('input').value;
                colsList.appendChild(el);
            });
        }

        // linear scale
        const lastMin = lastOptionsArea.querySelector('.scale-min');
        const lastMax = lastOptionsArea.querySelector('.scale-max');
        const lastMinLabel = lastOptionsArea.querySelector('.scale-min-label');
        const lastMaxLabel = lastOptionsArea.querySelector('.scale-max-label');
        if (lastMin && lastMax) {
            const newMin = newOptionsArea.querySelector('.scale-min');
            const newMax = newOptionsArea.querySelector('.scale-max');
            const newMinLabel = newOptionsArea.querySelector('.scale-min-label');
            const newMaxLabel = newOptionsArea.querySelector('.scale-max-label');
            if (newMin) newMin.value = lastMin.value;
            if (newMax) newMax.value = lastMax.value;
            if (newMinLabel) newMinLabel.value = lastMinLabel ? lastMinLabel.value : '';
            if (newMaxLabel) newMaxLabel.value = lastMaxLabel ? lastMaxLabel.value : '';
            // trigger updateIndices if present
            const evt = new Event('change'); if (newMin) newMin.dispatchEvent(evt); if (newMax) newMax.dispatchEvent(evt);
        }
    }

    questionContainer.appendChild(newQ);
    newQ.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function checkEmptyContainer() {
    if (document.querySelectorAll('.question-block-item').length === 0) {
        document.getElementById('question-container').innerHTML = `
            <div class="text-center" style="padding: 50px; border: 2.5px dashed var(--f-border); border-radius: 12px; background: #fff;">
                <i class="fas fa-clipboard-question" style="font-size: 3rem; color: var(--f-border); margin-bottom: 15px; display: block;"></i>
                <p class="text-muted" style="font-weight: 600;">Danh sách câu hỏi đang trống. Hãy thêm câu hỏi đầu tiên!</p>
            </div>
        `;
    }
}

function updateQuestionOptionsUI(type, container, questionId) {
    container.innerHTML = '';
    const optionTypes = ['multiple_choice', 'checkboxes', 'dropdown'];
    const gridTypes = ['multiple_choice_grid', 'checkbox_grid'];

    if (optionTypes.includes(type)) {
        renderStandardOptions(container, questionId);
    } else if (gridTypes.includes(type)) {
        renderGridOptions(container, questionId);
    } else if (type === 'linear_scale') {
        renderScaleOptions(container, questionId);
    } else if (type === 'file') {
        container.innerHTML = '<p class="text-muted" style="padding: 10px; background: #f8fafc; border-radius: 4px;"><i class="fas fa-info-circle"></i> Người trả lời sẽ được yêu cầu tải tệp lên. Tệp được lưu trong thư mục uploads/forms/.</p>';
    }
}

function renderStandardOptions(container, questionId) {
    const optionList = document.createElement('div');
    optionList.className = 'options-list';
    optionList.appendChild(createOptionElement(questionId, 1));
    const addOptionBtn = document.createElement('button');
    addOptionBtn.type = 'button';
    addOptionBtn.className = 'btn btn-secondary btn-sm';
    addOptionBtn.innerHTML = '<i class="fas fa-plus"></i> Thêm lựa chọn';
    addOptionBtn.addEventListener('click', () => {
        const idx = optionList.querySelectorAll('.option-item').length + 1;
        optionList.appendChild(createOptionElement(questionId, idx));
    });
    container.appendChild(optionList);
    container.appendChild(addOptionBtn);
}

function renderScaleOptions(container, questionId) {
    container.innerHTML = `
        <div class="scale-config">
            <div class="scale-range-row">
                <select class="scale-min">
                    <option value="0">0</option>
                    <option value="1" selected>1</option>
                </select>
                <span>đến</span>
                <select class="scale-max">
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                </select>
            </div>
            <div class="scale-labels">
                <div class="scale-label-item">
                    <span class="label-index">1</span>
                    <input type="text" class="scale-min-label" placeholder="Nhãn thấp nhất (Tùy chọn)">
                </div>
                <div class="scale-label-item">
                    <span class="label-index">5</span>
                    <input type="text" class="scale-max-label" placeholder="Nhãn cao nhất (Tùy chọn)">
                </div>
            </div>
        </div>
    `;

    // Lắng nghe sự kiện đổi số để cập nhật nhãn index (1 - 5, 0 - 10...)
    const minSelect = container.querySelector('.scale-min');
    const maxSelect = container.querySelector('.scale-max');
    const labelIndices = container.querySelectorAll('.label-index');

    const updateIndices = () => {
        labelIndices[0].textContent = minSelect.value;
        labelIndices[1].textContent = maxSelect.value;
        container.querySelector('.scale-max-label').placeholder = `Nhãn cao nhất (Tại mốc ${maxSelect.value})`;
        container.querySelector('.scale-min-label').placeholder = `Nhãn thấp nhất (Tại mốc ${minSelect.value})`;
    };

    minSelect.addEventListener('change', updateIndices);
    maxSelect.addEventListener('change', updateIndices);
    updateIndices();
}

function renderGridOptions(container, questionId) {
    container.innerHTML = `
        <div class="grid-config" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label style="font-weight: 600; font-size: 0.85rem; color: #64748b; margin-bottom: 8px; display: block;">HÀNG (CÂU HỎI)</label>
                <div class="rows-list options-list"></div>
                <button type="button" class="btn btn-sm btn-link add-row-btn"><i class="fas fa-plus"></i> Thêm hàng</button>
            </div>
            <div>
                <label style="font-weight: 600; font-size: 0.85rem; color: #64748b; margin-bottom: 8px; display: block;">CỘT (LỰA CHỌN)</label>
                <div class="cols-list options-list"></div>
                <button type="button" class="btn btn-sm btn-link add-col-btn"><i class="fas fa-plus"></i> Thêm cột</button>
            </div>
        </div>
    `;
    const rowsList = container.querySelector('.rows-list');
    const colsList = container.querySelector('.cols-list');
    rowsList.appendChild(createOptionElement(questionId, 1, 'row', 'Hàng 1'));
    colsList.appendChild(createOptionElement(questionId, 1, 'column', 'Cột 1'));
    container.querySelector('.add-row-btn').addEventListener('click', () => rowsList.appendChild(createOptionElement(questionId, rowsList.children.length + 1, 'row')));
    container.querySelector('.add-col-btn').addEventListener('click', () => colsList.appendChild(createOptionElement(questionId, colsList.children.length + 1, 'column')));
}

function createOptionElement(questionId, optionIndex, type = 'choice', placeholder = '') {
    const optionWrapper = document.createElement('div');
    optionWrapper.className = 'q-opt-row option-item';
    optionWrapper.dataset.type = type;
    const icon = type === 'row' ? 'fa-grip-lines' : (type === 'column' ? 'fa-ellipsis-v' : 'fa-grip-vertical');
    optionWrapper.innerHTML = `
        <i class="fas ${icon}" style="color: #cbd5e1; font-size: 0.7rem; cursor: move;"></i>
        <input type="text" class="q-opt-input" placeholder="${placeholder || (type === 'choice' ? 'Lựa chọn ' + optionIndex : 'Nhãn...')}">
        <button type="button" class="btn-delete-option" style="background:none; border:none; color:#94a3b8; cursor:pointer; padding:2px;"><i class="fas fa-times"></i></button>
    `;
    optionWrapper.querySelector('.btn-delete-option').addEventListener('click', () => optionWrapper.remove());
    return optionWrapper;
}

function setupQuestionLogic(block) {
    const currentId = block.dataset.questionId;
    const allBlocks = document.querySelectorAll('.question-block');
    let prevQuestions = [];
    
    allBlocks.forEach(b => {
        if (b.dataset.questionId === currentId) return;
        const bIndex = Array.from(allBlocks).indexOf(b);
        const currentIndex = Array.from(allBlocks).indexOf(block);
        
        if (bIndex < currentIndex) {
            const title = b.querySelector('input[name*="[title]"]').value || "Câu hỏi không tiêu đề";
            prevQuestions.push({ id: b.dataset.questionId, title: title });
        }
    });

    if (prevQuestions.length === 0) {
        showToast("Cần có ít nhất một câu hỏi phía trên để thiết lập điều kiện.", "info");
        return;
    }

    let existingLogic = block.querySelector('.logic-data') ? JSON.parse(block.querySelector('.logic-data').value) : null;

    let modalHtml = `
        <div id="logic-modal" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:flex; justify-content:center; align-items:center; z-index:10000;">
            <div style="background:white; padding:30px; border-radius:12px; width:500px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
                <h3 style="margin-top:0;"><i class="fas fa-brain"></i> Điều kiện hiển thị</h3>
                <p style="color:#64748b; font-size:0.9rem;">Chỉ hiển thị câu hỏi này nếu:</p>
                
                <div class="form-group" style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Chọn câu hỏi:</label>
                    <select id="logic-dep-q" class="form-control">
                        <option value="">-- Chọn câu hỏi --</option>
                        ${prevQuestions.map(q => `<option value="${q.id}" ${existingLogic && existingLogic.dependsOn === q.id ? 'selected' : ''}>${q.title}</option>`).join('')}
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Giá trị phải là:</label>
                    <input type="text" id="logic-dep-val" class="form-control" placeholder="Nhập giá trị chính xác" value="${existingLogic ? existingLogic.value : ''}">
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('logic-modal').remove()">Hủy</button>
                    ${existingLogic ? `<button type="button" class="btn btn-danger" id="clear-logic">Xóa điều kiện</button>` : ''}
                    <button type="button" class="btn btn-primary" id="save-logic">Lưu thiết lập</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    document.getElementById('save-logic').addEventListener('click', () => {
        const depQ = document.getElementById('logic-dep-q').value;
        const depVal = document.getElementById('logic-dep-val').value;

        if (!depQ || !depVal) {
            showToast("Vui lòng nhập đầy đủ thông tin.", "error");
            return;
        }

        const logicConfig = JSON.stringify({ dependsOn: depQ, value: depVal });
        let input = block.querySelector('.logic-data');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.className = 'logic-data';
            block.appendChild(input);
        }
        input.value = logicConfig;
        block.querySelector('.btn-logic').style.color = 'var(--primary-color)';
        document.getElementById('logic-modal').remove();
        showToast("Đã lưu thiết lập điều kiện.");
    });

    if (document.getElementById('clear-logic')) {
        document.getElementById('clear-logic').addEventListener('click', () => {
            const input = block.querySelector('.logic-data');
            if (input) input.remove();
            block.querySelector('.btn-logic').style.color = '';
            document.getElementById('logic-modal').remove();
            showToast("Đã xóa điều kiện.");
        });
    }
}
