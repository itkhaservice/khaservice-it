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

});