// File: assets/js/form_builder.js
// This file will contain all the JavaScript logic for the form builder UI.

// Make this a global variable to be accessed by populate function
let questionCounter = 0;

document.addEventListener('DOMContentLoaded', () => {
    const addQuestionBtn = document.getElementById('add-question-btn');
    const mainForm = document.getElementById('create-form') || document.getElementById('edit-form');

    if (addQuestionBtn) {
        addQuestionBtn.addEventListener('click', () => {
            const questionContainer = document.getElementById('question-container');
            const placeholder = questionContainer.querySelector('p');
            if (placeholder) placeholder.remove();
            
            questionCounter++;
            const newQuestion = createQuestionElement(questionCounter);
            questionContainer.appendChild(newQuestion);
        });
    }

    if (mainForm) {
        mainForm.addEventListener('submit', handleFormSubmit);
    }

    // Check if we are on the edit page and have data
    if (typeof existingFormData !== 'undefined') {
        populateForm(existingFormData);
    }
});

function populateForm(data) {
    const questionContainer = document.getElementById('question-container');
    if (data.questions && data.questions.length > 0) {
        questionContainer.innerHTML = ''; // Clear placeholder
        data.questions.forEach(q_data => {
            questionCounter++;
            const questionBlock = createQuestionElement(questionCounter);
            
            // Store DB ID if it exists
            if (q_data.id) {
                questionBlock.setAttribute('data-db-id', q_data.id);
            }
            
            // Populate question data
            questionBlock.querySelector(`input[name*="[title]"]`).value = q_data.title;
            questionBlock.querySelector(`select[name*="[type]"]`).value = q_data.type;
            questionBlock.querySelector(`input[name*="[required]"]`).checked = q_data.is_required;

            // Trigger change to render options UI
            const typeSelect = questionBlock.querySelector('.question-type-select');
            typeSelect.dispatchEvent(new Event('change'));

            // Populate options if they exist
            if (q_data.options && q_data.options.length > 0) {
                const optionsContainer = questionBlock.querySelector('.question-options-container');
                const optionsList = optionsContainer.querySelector('.options-list');
                optionsList.innerHTML = ''; // Clear the default empty option

                q_data.options.forEach((opt_text, index) => {
                    const optionElement = createOptionElement(`q_${questionCounter}`, index + 1);
                    optionElement.querySelector('input[type="text"]').value = opt_text;
                    optionsList.appendChild(optionElement);
                });
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
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';

    const data = {
        id: formData.get('form_id'), // Will be null on create, valued on edit
        title: formData.get('form_title'),
        description: formData.get('form_description'),
        status: formData.get('form_status'),
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
            options: []
        };

        block.querySelectorAll('.option-item').forEach(optionEl => {
            const optionText = optionEl.querySelector('input[type="text"]').value;
            if (optionText) {
                question.options.push(optionText);
            }
        });
        data.questions.push(question);
    });

    // Determine API endpoint (create vs update)
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
            alert(result.message);
            window.location.href = result.redirect_url;
        } else {
            throw new Error(result.message || 'Đã xảy ra lỗi không xác định.');
        }
    } catch (error) {
        playAudio('error');
        alert('Lỗi khi lưu biểu mẫu: ' + error.message);
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
                    <option value="text">Trả lời ngắn</option>
                    <option value="textarea">Đoạn văn</option>
                    <option value="multiple_choice" selected>Trắc nghiệm</option>
                    <option value="checkboxes">Hộp kiểm</option>
                    <option value="dropdown">Menu thả xuống</option>
                    <option value="date">Ngày</option>
                </select>
            </div>
            <div class="question-options-container"></div>
            <div class="question-footer">
                <label class="switch">
                    <input type="checkbox" name="questions[${questionId}][required]">
                    <span class="slider round"></span>
                </label>
                <span>Bắt buộc</span>
                <button type="button" class="btn-delete-question"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>
    `;

    const typeSelect = questionWrapper.querySelector('.question-type-select');
    const optionsContainer = questionWrapper.querySelector('.question-options-container');

    updateQuestionOptionsUI(typeSelect.value, optionsContainer, questionId);

    typeSelect.addEventListener('change', (e) => {
        updateQuestionOptionsUI(e.target.value, optionsContainer, questionId);
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

    if (optionTypes.includes(type)) {
        const optionList = document.createElement('div');
        optionList.className = 'options-list';
        
        optionList.appendChild(createOptionElement(questionId, 1));

        const addOptionBtn = document.createElement('button');
        addOptionBtn.type = 'button';
        addOptionBtn.className = 'btn btn-secondary btn-sm';
        addOptionBtn.innerHTML = '<i class="fas fa-plus"></i> Thêm lựa chọn';
        
        addOptionBtn.addEventListener('click', () => {
            const newOptionIndex = container.querySelectorAll('.option-item').length + 1;
            optionList.appendChild(createOptionElement(questionId, newOptionIndex));
        });

        container.appendChild(optionList);
        container.appendChild(addOptionBtn);
    }
}

function createOptionElement(questionId, optionIndex) {
    const optionWrapper = document.createElement('div');
    optionWrapper.className = 'option-item';

    optionWrapper.innerHTML = `
        <i class="fas fa-grip-vertical drag-handle"></i>
        <input type="text" class="form-control" placeholder="Lựa chọn ${optionIndex}">
        <button type="button" class="btn-delete-option"><i class="fas fa-times"></i></button>
    `;

    const deleteBtn = optionWrapper.querySelector('.btn-delete-option');
    deleteBtn.addEventListener('click', () => {
        optionWrapper.remove();
    });

    return optionWrapper;
}
