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

    // Global toast utility (used across site)
    if (!window.showToast) {
        window.showToast = (message, type = 'success', timeout = 3500) => {
            let container = document.getElementById('global-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'global-toast-container';
                container.style.position = 'fixed';
                container.style.right = '20px';
                container.style.bottom = '20px';
                container.style.zIndex = '99999';
                container.style.display = 'flex';
                container.style.flexDirection = 'column';
                container.style.gap = '10px';
                document.body.appendChild(container);
            }
            const el = document.createElement('div');
            el.textContent = message;
            el.style.minWidth = '200px';
            el.style.maxWidth = '420px';
            el.style.color = '#fff';
            el.style.padding = '10px 14px';
            el.style.borderRadius = '10px';
            el.style.boxShadow = '0 8px 24px rgba(2,6,23,0.35)';
            el.style.opacity = '0';
            el.style.transform = 'translateY(10px)';
            el.style.transition = 'transform .28s cubic-bezier(.2,.8,.2,1),opacity .28s';
            el.style.fontWeight = '700';
            el.style.pointerEvents = 'auto';
            if (type === 'error') el.style.background = 'linear-gradient(90deg,#b91c1c,#ef4444)';
            else el.style.background = 'linear-gradient(90deg,#059669,#10b981)';
            container.appendChild(el);
            requestAnimationFrame(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; });
            setTimeout(() => {
                el.style.opacity = '0'; el.style.transform = 'translateY(10px)';
                el.addEventListener('transitionend', () => el.remove(), { once: true });
            }, timeout);
        };
    }

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

        // Close menu when clicking a link (EXCEPT dropdown toggles)
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', (e) => {
                // Nếu link này dùng để mở dropdown thì không đóng menu
                if (link.getAttribute('onclick') && link.getAttribute('onclick').includes('toggleDropdown')) {
                    return; 
                }

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
        // Prevent layout shift when scrollbar disappears by compensating padding
        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
        if (scrollbarWidth > 0) document.body.style.paddingRight = scrollbarWidth + 'px';
        document.body.style.overflow = 'hidden';
        customConfirmModal.classList.add('show');
    };

    const hideCustomConfirm = () => {
        if (!customConfirmModal) return;
        customConfirmModal.classList.remove('show');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
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
            if (checkboxes.length === 0) { window.showToast('Vui lòng chọn ít nhất một mục.', 'error'); return; }

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
            
            // Lấy URL từ data-url hoặc href
            let url = btn.getAttribute('data-url') || btn.dataset.url || btn.getAttribute('href');
            
            if (url && url !== '#' && url !== 'javascript:void(0);') {
                // Đảm bảo URL có tham số confirm_delete=1 để thực thi xóa ngay khi xác nhận ở modal
                if (!url.includes('confirm_delete=1')) {
                    url += (url.includes('?') ? '&' : '?') + 'confirm_delete=1';
                }

                showCustomConfirm('Bạn có chắc chắn muốn chuyển mục này vào thùng rác không? Hành động này có thể khôi phục lại từ Thùng rác.', 'Xác nhận xóa', () => { 
                    showSpinner();
                    window.location.href = url; 
                });
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
