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

            function hideMessage() {
                messageBox.style.opacity = '0';
                messageBox.style.transform = 'translateX(100%)';
                setTimeout(() => messageBox.remove(), 300); // Remove after transition
            }

            function startHideTimer() {
                timer = setTimeout(hideMessage, 5000); // Hide after 5 seconds
            }

            function stopHideTimer() {
                clearTimeout(timer);
            }

            // Start timer immediately
            startHideTimer();

            // Pause timer on hover
            messageBox.addEventListener('mouseover', stopHideTimer);
            // Resume timer on mouse out
            messageBox.addEventListener('mouseout', startHideTimer);
        });
    }

    // Custom Confirmation Modal Logic
    const customConfirmModal = document.getElementById('customConfirmModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const confirmBtn = document.getElementById('confirmBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const closeButton = customConfirmModal ? customConfirmModal.querySelector('.close-button') : null;

    if (!customConfirmModal) {
        // Log an error if the modal element is not found.
        // This is useful for debugging in the browser console.
        console.error("Custom confirmation modal element (#customConfirmModal) not found in DOM.");
        // We can stop further execution of modal-related logic if the element is missing.
        return; 
    }

    let confirmCallback = null; // To store the function to call on confirmation

    window.showCustomConfirm = (message, title = 'Xác nhận hành động', callback) => {
        if (!customConfirmModal) return;

        modalTitle.textContent = title;
        modalMessage.textContent = message;
        confirmCallback = callback; // Store the callback

        customConfirmModal.classList.add('show');
        document.body.style.overflow = 'hidden'; // Prevent scrolling background
    };

    const hideCustomConfirm = () => {
        if (!customConfirmModal) return;

        customConfirmModal.classList.remove('show');
        document.body.style.overflow = ''; // Restore scrolling
        confirmCallback = null; // Clear the callback
        // Clear previous event listeners to prevent multiple calls
        confirmBtn.onclick = null; 
        cancelBtn.onclick = null;
        hideSpinner(); // Hide spinner when modal is closed/cancelled
    };

    if (closeButton) {
        closeButton.addEventListener('click', hideCustomConfirm);
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', hideCustomConfirm);
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            if (confirmCallback) {
                confirmCallback();
            }
            hideCustomConfirm();
        });
    }

    // Close modal if clicked outside of modal-content
    if (customConfirmModal) {
        customConfirmModal.addEventListener('click', (event) => {
            if (event.target === customConfirmModal) {
                hideCustomConfirm();
            }
        });
    }

    // Handle "Xóa mục đã chọn" button click using custom confirm
    const deleteSelectedBtn = document.getElementById('delete-selected-btn');
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent default action
            const deviceForm = document.getElementById('devices-form'); // Assuming this is the form containing checkboxes

            showCustomConfirm('Bạn có chắc chắn muốn xóa các mục đã chọn không?', 'Xóa nhiều thiết bị', () => {
                // On confirmation, set form action and submit
                deviceForm.action = 'index.php?page=devices/delete_multiple';
                deviceForm.method = 'POST';
                deviceForm.submit();
            });
        });
    }

    // Handle individual "Xóa" links with custom confirm
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent default navigation
            const deleteUrl = button.getAttribute('href');
            
            showCustomConfirm('Bạn có chắc muốn xóa thiết bị này?', 'Xóa thiết bị', () => {
                window.location.href = deleteUrl; // Redirect on confirmation
            });
        });
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