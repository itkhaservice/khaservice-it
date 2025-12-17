<?php
// Function to set a session message
function set_message($type, $message) {
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = [];
    }
    $_SESSION['messages'][] = ['type' => $type, 'content' => $message];
}

// Function to display and clear session messages
function display_messages() {
    if (isset($_SESSION['messages']) && !empty($_SESSION['messages'])) {
        foreach ($_SESSION['messages'] as $message) {
            echo '<div class="message-box ' . htmlspecialchars($message['type']) . '">';
            echo htmlspecialchars($message['content']);
            echo '</div>';
            // Trigger audio feedback via JavaScript
            echo '<script>window.playAudioFeedback("' . htmlspecialchars($message['type']) . '");</script>';
        }
        unset($_SESSION['messages']); // Clear messages after displaying
    }
}
?>
