// assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {
    // Function to show spinner
    window.showSpinner = () => {
        const spinnerOverlay = document.getElementById('spinner-overlay');
        if (spinnerOverlay) {
            spinnerOverlay.classList.add('show');
        }
    };

    // Function to hide spinner
    window.hideSpinner = () => {
        const spinnerOverlay = document.getElementById('spinner-overlay');
        if (spinnerOverlay) {
            spinnerOverlay.classList.remove('show');
        }
    };

    // Example: Show spinner on form submission
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            showSpinner();
        });
    });

    // Example: Hide spinner on page load (in case it was shown before navigation)
    hideSpinner();

    // Attach spinner to all buttons that trigger actions
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', () => {
            // Only show spinner if the button is not of type 'submit' within a form
            // as form submission already handled above
            if (button.type !== 'submit' && !button.form) {
                showSpinner();
            }
        });
    });

    // Hamburger menu toggle
    const hamburgerButton = document.getElementById('hamburger-menu');
    const mobileMenu = document.getElementById('mobile-menu');

    if (hamburgerButton && mobileMenu) {
        hamburgerButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
            hamburgerButton.classList.toggle('active'); // Optional: Add active class to button for animation
        });
    }
    // Message handling (auto-hide and hover)
    const messageContainer = document.getElementById('message-container');
    if (messageContainer) {
        const messageBoxes = messageContainer.querySelectorAll('.message-box');
        messageBoxes.forEach(messageBox => {
            let timer;
            
            // Function to trigger fade out
            function hideMessage() {
                messageBox.style.animation = 'none'; // Stop slideIn animation
                messageBox.style.transition = 'all 0.5s ease'; // Ensure transition works
                messageBox.style.opacity = '0';
                messageBox.style.transform = 'translateX(120%)'; // Slide out further
                
                // Remove from DOM after animation finishes
                setTimeout(() => {
                    if (messageBox.parentNode) {
                        messageBox.parentNode.removeChild(messageBox);
                    }
                }, 500); 
            }

            function startHideTimer() {
                timer = setTimeout(hideMessage, 4000); // 4 seconds visibility
            }

            function stopHideTimer() {
                clearTimeout(timer);
            }

            // Start timer
            startHideTimer();

            // Hover effects
            messageBox.addEventListener('mouseenter', stopHideTimer);
            messageBox.addEventListener('mouseleave', startHideTimer);
            
            // Click to close immediately
            messageBox.addEventListener('click', hideMessage);
        });
    }

    // Custom Confirmation Modal Logic
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
        customConfirmModal.addEventListener('click', (e) => {
            if (e.target === customConfirmModal) hideCustomConfirm();
        });
    }

    // Handle batch delete buttons
    document.querySelectorAll('#delete-selected-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const form = btn.closest('form');
            if (!form) return;

            const checkboxes = form.querySelectorAll('.row-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Vui lòng chọn ít nhất một mục.');
                return;
            }

            showCustomConfirm(`Bạn có chắc chắn muốn xóa ${checkboxes.length} mục đã chọn?`, 'Xác nhận xóa nhiều', () => {
                const actionUrl = btn.dataset.action;
                if (actionUrl) {
                    form.action = actionUrl;
                }
                form.submit();
            });
        });
    });

    // Handle individual delete buttons/links (Event Delegation)
    document.addEventListener('click', (e) => {
        // Find the closest element with the class 'delete-btn'
        const btn = e.target.closest('.delete-btn');
        
        if (btn) {
            // CRITICAL: Stop default link behavior immediately
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Delete button clicked:', btn);

            // Prioritize data-url (used for JS actions), then fallback to href
            let url = btn.getAttribute('data-url') || btn.dataset.url;
            
            if (!url) {
                const href = btn.getAttribute('href');
                if (href && href !== '#' && href !== 'javascript:void(0);') {
                    url = href;
                }
            }
            
            console.log('Target URL for delete:', url);

            if (url) {
                if (typeof window.showCustomConfirm === 'function') {
                    window.showCustomConfirm(
                        'Bạn có chắc chắn muốn xóa mục này không? Hành động này không thể hoàn tác.', 
                        'Xác nhận xóa', 
                        () => {
                            console.log('Confirmed delete, redirecting to:', url);
                            window.location.href = url;
                        }
                    );
                } else {
                    // Fallback if modal function is missing
                    if (confirm('Bạn có chắc chắn muốn xóa mục này không?')) {
                        window.location.href = url;
                    }
                }
            } else {
                console.warn('Delete button clicked but no URL found.', btn);
            }
        }
    });

    // Quick Search Logic
    const quickSearchInput = document.getElementById('quick-search-input');
    const quickSearchResultsDiv = document.getElementById('quick-search-results');
    let searchTimeout;

    if (quickSearchInput && quickSearchResultsDiv) {
        quickSearchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const query = quickSearchInput.value.trim();

            if (query.length < 2) { // Only search if query is at least 2 characters long
                quickSearchResultsDiv.innerHTML = '';
                quickSearchResultsDiv.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                showSpinner(); // Show spinner while searching
                fetch(`api/quick_search.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        hideSpinner(); // Hide spinner
                        quickSearchResultsDiv.innerHTML = ''; // Clear previous results
                        if (data.error) {
                            quickSearchResultsDiv.innerHTML = `<div class="search-result-item error">${data.error}</div>`;
                            quickSearchResultsDiv.style.display = 'block';
                            return;
                        }

                        if (data.length > 0) {
                            data.forEach(item => {
                                const resultItem = document.createElement('a');
                                resultItem.href = `index.php?page=devices/view&id=${item.id}`;
                                resultItem.classList.add('search-result-item');
                                resultItem.innerHTML = `
                                    <span class="ma-tai-san">${item.ma_tai_san}</span> - 
                                    <span class="ten-thiet-bi">${item.ten_thiet_bi}</span>
                                `;
                                quickSearchResultsDiv.appendChild(resultItem);
                            });
                            quickSearchResultsDiv.style.display = 'block'; // Show results
                        } else {
                            quickSearchResultsDiv.innerHTML = '<div class="search-result-item no-results">Không tìm thấy kết quả.</div>';
                            quickSearchResultsDiv.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        hideSpinner(); // Hide spinner
                        console.error('Quick search fetch error:', error);
                        quickSearchResultsDiv.innerHTML = '<div class="search-result-item error">Lỗi khi tìm kiếm.</div>';
                        quickSearchResultsDiv.style.display = 'block';
                    });
            }, 300); // Debounce time of 300ms
        });

        // Hide results when clicking outside
        document.addEventListener('click', (event) => {
            if (!quickSearchInput.contains(event.target) && !quickSearchResultsDiv.contains(event.target)) {
                quickSearchResultsDiv.style.display = 'none';
            }
        });

        // Show results again if input is focused and has text
        quickSearchInput.addEventListener('focus', () => {
            if (quickSearchResultsDiv.innerHTML !== '' && quickSearchResultsDiv.children.length > 0 && quickSearchInput.value.trim().length >= 2) {
                quickSearchResultsDiv.style.display = 'block';
            }
        });
    }
});