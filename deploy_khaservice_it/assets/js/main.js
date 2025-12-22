// assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {
    // 1. SPINNER LOGIC
    window.showSpinner = () => {
        const spinnerOverlay = document.getElementById('spinner-overlay');
        if (spinnerOverlay) spinnerOverlay.classList.add('show');
    };

    window.hideSpinner = () => {
        const spinnerOverlay = document.getElementById('spinner-overlay');
        if (spinnerOverlay) spinnerOverlay.classList.remove('show');
    };

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => showSpinner());
    });

    hideSpinner();

    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', () => {
            if (button.type !== 'submit' && !button.form) showSpinner();
        });
    });

    // 2. HAMBURGER MENU TOGGLE
    const hamburgerButton = document.getElementById('hamburger-menu');
    const mobileMenu = document.getElementById('mobile-menu');

    if (hamburgerButton && mobileMenu) {
        hamburgerButton.addEventListener('click', (e) => {
            e.stopPropagation();
            hamburgerButton.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (mobileMenu.classList.contains('active') && !mobileMenu.contains(e.target) && e.target !== hamburgerButton) {
                hamburgerButton.classList.remove('active');
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Close menu when clicking a link
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                hamburgerButton.classList.remove('active');
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
    }

    // 3. TOAST MESSAGES
    const messageContainer = document.getElementById('message-container');
    if (messageContainer) {
        const messageBoxes = messageContainer.querySelectorAll('.message-box');
        messageBoxes.forEach(messageBox => {
            let timer;
            function hideMessage() {
                messageBox.style.opacity = '0';
                messageBox.style.transform = 'translateX(120%)';
                setTimeout(() => { if (messageBox.parentNode) messageBox.parentNode.removeChild(messageBox); }, 500); 
            }
            function startHideTimer() { timer = setTimeout(hideMessage, 4000); }
            startHideTimer();
            messageBox.addEventListener('mouseenter', () => clearTimeout(timer));
            messageBox.addEventListener('mouseleave', startHideTimer);
            messageBox.addEventListener('click', hideMessage);
        });
    }

    // 4. CUSTOM CONFIRM MODAL
    const customConfirmModal = document.getElementById('customConfirmModal');
    const modalTitleDisplay = document.getElementById('modalTitleDisplay');
    const modalMessage = document.getElementById('modalMessage');
    const confirmBtn = document.getElementById('confirmBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    let confirmCallback = null;

    window.showCustomConfirm = (message, title = 'Xác nhận?', callback) => {
        if (!customConfirmModal) return;
        if (modalTitleDisplay) modalTitleDisplay.textContent = title;
        if (modalMessage) modalMessage.textContent = message;
        confirmCallback = callback;
        customConfirmModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    const hideCustomConfirm = () => {
        if (!customConfirmModal) return;
        customConfirmModal.classList.remove('show');
        document.body.style.overflow = '';
        confirmCallback = null;
    };

    if (cancelBtn) cancelBtn.addEventListener('click', hideCustomConfirm);
    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            if (confirmCallback) confirmCallback();
            hideCustomConfirm();
        });
    }
    if (customConfirmModal) {
        customConfirmModal.addEventListener('click', (e) => { if (e.target === customConfirmModal) hideCustomConfirm(); });
    }

    // 5. DELETE ACTIONS (Batch & Individual)
    document.querySelectorAll('#delete-selected-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const form = btn.closest('form');
            if (!form) return;
            const checkboxes = form.querySelectorAll('.row-checkbox:checked');
            if (checkboxes.length === 0) { alert('Vui lòng chọn ít nhất một mục.'); return; }

            showCustomConfirm(`Bạn có chắc chắn muốn chuyển ${checkboxes.length} mục đã chọn vào thùng rác?`, 'Xác nhận xóa nhiều', () => {
                const actionUrl = btn.dataset.action;
                if (actionUrl) form.action = actionUrl;
                form.submit();
            });
        });
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.delete-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            let url = btn.getAttribute('data-url') || btn.dataset.url;
            if (!url) {
                const href = btn.getAttribute('href');
                if (href && href !== '#' && href !== 'javascript:void(0);') url = href;
            }
            if (url) {
                showCustomConfirm('Bạn có chắc chắn muốn thực hiện hành động này không?', 'Xác nhận', () => { window.location.href = url; });
            }
        }
    });

    // 6. QUICK SEARCH
    const quickSearchInput = document.getElementById('quick-search-input');
    const quickSearchResultsDiv = document.getElementById('quick-search-results');
    let searchTimeout;

    if (quickSearchInput && quickSearchResultsDiv) {
        quickSearchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const query = quickSearchInput.value.trim();
            if (query.length < 2) {
                quickSearchResultsDiv.innerHTML = '';
                quickSearchResultsDiv.style.display = 'none';
                return;
            }
            searchTimeout = setTimeout(() => {
                fetch(`api/quick_search.php?q=${encodeURIComponent(query)}`)
                    .then(r => r.json()).then(data => {
                        quickSearchResultsDiv.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(item => {
                                const a = document.createElement('a');
                                a.href = `index.php?page=devices/view&id=${item.id}`;
                                a.classList.add('search-result-item');
                                a.innerHTML = `<span class="ma-tai-san">${item.ma_tai_san}</span> - <span class="ten-thiet-bi">${item.ten_thiet_bi}</span>`;
                                quickSearchResultsDiv.appendChild(a);
                            });
                            quickSearchResultsDiv.style.display = 'block';
                        } else {
                            quickSearchResultsDiv.innerHTML = '<div class="search-result-item no-results">Không tìm thấy.</div>';
                            quickSearchResultsDiv.style.display = 'block';
                        }
                    });
            }, 300);
        });
        document.addEventListener('click', (event) => {
            if (!quickSearchInput.contains(event.target) && !quickSearchResultsDiv.contains(event.target)) quickSearchResultsDiv.style.display = 'none';
        });
    }
});
