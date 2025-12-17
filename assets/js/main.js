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
});