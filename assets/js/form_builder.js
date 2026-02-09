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
        const placeholder = questionContainer.querySelector('p');
        if (placeholder) placeholder.remove();
        
        questionCounter++;
        const newQuestion = createQuestionElement(questionCounter);
        questionContainer.appendChild(newQuestion);
        
        // Scroll to new question if added via quick btn
        newQuestion.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    if (addQuestionBtn) addQuestionBtn.addEventListener('click', addNewQuestion);
    if (quickAddBtn) quickAddBtn.addEventListener('click', addNewQuestion);

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
    document.documentElement.style.setProperty('--primary-color', color);
    // Force specific elements that might use gradients or complex styles
    const titleCard = document.querySelector('.form-title-card');
    if (titleCard) {
        titleCard.style.borderTop = `10px solid ${color}`;
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
    if (data.questions && data.questions.length > 0) {
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
                    if(btn) btn.style.color = 'var(--primary-color)';
                }, 100);
            }
            
            questionBlock.querySelector(`input[name*="[title]"]`).value = q_data.title;
            questionBlock.querySelector(`select[name*="[type]"]`).value = q_data.type;
            questionBlock.querySelector(`input[name*="[required]"]`).checked = q_data.is_required;

            updateQuestionOptionsUI(q_data.type, questionBlock.querySelector('.question-options-container'), `q_${questionCounter}`);

            if (q_data.type === 'linear_scale' && q_data.options.length >= 4) {
                questionBlock.querySelector('.scale-min').value = q_data.options[0].text;
                questionBlock.querySelector('.scale-max').value = q_data.options[1].text;
                questionBlock.querySelector('.scale-min-label').value = q_data.options[2].text;
                questionBlock.querySelector('.scale-max-label').value = q_data.options[3].text;
            } else if (q_data.options && q_data.options.length > 0) {
                const container = questionBlock.querySelector('.question-options-container');
                const gridTypes = ['multiple_choice_grid', 'checkbox_grid'];
                
                if (gridTypes.includes(q_data.type)) {
                    const rowsList = container.querySelector('.rows-list');
                    const colsList = container.querySelector('.cols-list');
                    rowsList.innerHTML = '';
                    colsList.innerHTML = '';
                    
                    q_data.options.forEach((opt, idx) => {
                        if (opt.type === 'row') {
                            const el = createOptionElement(`q_${questionCounter}`, idx + 1, 'row');
                            el.querySelector('input').value = opt.text;
                            rowsList.appendChild(el);
                        } else if (opt.type === 'column') {
                            const el = createOptionElement(`q_${questionCounter}`, idx + 1, 'column');
                            el.querySelector('input').value = opt.text;
                            colsList.appendChild(el);
                        }
                    });
                } else {
                    const optionsList = container.querySelector('.options-list');
                    if (optionsList) {
                        optionsList.innerHTML = '';
                        q_data.options.forEach((opt, index) => {
                            const optionElement = createOptionElement(`q_${questionCounter}`, index + 1);
                            optionElement.querySelector('input[type="text"]').value = opt.text;
                            optionsList.appendChild(optionElement);
                        });
                    }
                }
            }
            
            questionContainer.appendChild(questionBlock);
        });
    } else {
         questionContainer.innerHTML = '<p class="text-muted">Chưa có câu hỏi nào. Nhấn "Thêm Câu hỏi" để bắt đầu.</p>';
    }
}

async function handleFormSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const submitButton = document.querySelector(`button[form="${form.id}"]`);
    const originalButtonText = submitButton.innerHTML;
    
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

    document.querySelectorAll('.question-block').forEach(block => {
        const question = {
            id: block.getAttribute('data-db-id') || null,
            title: block.querySelector(`input[name*="[title]"]`).value,
            type: block.querySelector(`select[name*="[type]"]`).value,
            is_required: block.querySelector(`input[name*="[required]"]`).checked,
            logic_config: block.querySelector('.logic-data') ? block.querySelector('.logic-data').value : null,
            options: []
        };

        if (question.type === 'linear_scale') {
            question.options = [
                { text: block.querySelector('.scale-min').value, type: 'choice' },
                { text: block.querySelector('.scale-max').value, type: 'choice' },
                { text: block.querySelector('.scale-min-label').value, type: 'choice' },
                { text: block.querySelector('.scale-max-label').value, type: 'choice' }
            ];
        } else {
            block.querySelectorAll('.option-item').forEach(optionEl => {
                const optionText = optionEl.querySelector('input[type="text"]').value;
                const optionType = optionEl.dataset.type || 'choice';
                if (optionText) {
                    question.options.push({ text: optionText, type: optionType });
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
            showToast(result.message, 'success');
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
    questionWrapper.className = 'question-block card';
    questionWrapper.setAttribute('data-question-id', questionId);

    questionWrapper.innerHTML = `
        <div class="card-body-custom">
            <div class="question-header">
                <input type="text" name="questions[${questionId}][title]" class="form-control" placeholder="Nhập câu hỏi của bạn ở đây...">
                <select name="questions[${questionId}][type]" class="form-control question-type-select">
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
            <div class="question-options-container"></div>
            <div class="question-footer">
                <label class="switch">
                    <input type="checkbox" name="questions[${questionId}][required]">
                    <span class="slider round"></span>
                </label>
                <span>Bắt buộc</span>
                <button type="button" class="btn-logic" title="Thiết lập điều kiện hiển thị"><i class="fas fa-brain"></i></button>
                <button type="button" class="btn-delete-question" title="Xóa câu hỏi"><i class="fas fa-trash-alt"></i></button>
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
        if (document.querySelectorAll('.question-block').length === 0) {
            document.getElementById('question-container').innerHTML = '<p class="text-muted">Chưa có câu hỏi nào. Nhấn "Thêm Câu hỏi" để bắt đầu.</p>';
        }
    });

    return questionWrapper;
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
        <div class="scale-config" style="background: #f8fafc; padding: 15px; border-radius: 8px;">
            <div style="display: flex; gap: 15px; margin-bottom: 15px; align-items: center;">
                <select class="form-control scale-min" style="width: 80px;"><option value="0">0</option><option value="1" selected>1</option></select>
                <span>đến</span>
                <select class="form-control scale-max" style="width: 80px;"><option value="5" selected>5</option><option value="10">10</option></select>
            </div>
            <div class="scale-labels">
                <input type="text" class="form-control scale-min-label" placeholder="Nhãn thấp nhất (VD: Rất kém)" style="margin-bottom: 10px;">
                <input type="text" class="form-control scale-max-label" placeholder="Nhãn cao nhất (VD: Rất tốt)">
            </div>
        </div>
    `;
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
    optionWrapper.className = 'option-item';
    optionWrapper.dataset.type = type;
    const icon = type === 'row' ? 'fa-grip-lines' : (type === 'column' ? 'fa-ellipsis-v' : 'fa-grip-vertical');
    optionWrapper.innerHTML = `
        <i class="fas ${icon} drag-handle"></i>
        <input type="text" class="form-control" placeholder="${placeholder || (type === 'choice' ? 'Lựa chọn ' + optionIndex : 'Nhãn...')}">
        <button type="button" class="btn-delete-option"><i class="fas fa-times"></i></button>
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
