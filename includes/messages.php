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
        echo '<div id="message-container" class="message-container">'; 
        foreach ($_SESSION['messages'] as $message) {
            $type = htmlspecialchars($message['type']);
            $icon = 'info-circle';
            if ($type === 'success') $icon = 'check';
            if ($type === 'error') $icon = 'exclamation-circle';
            if ($type === 'warning') $icon = 'exclamation-triangle';

            echo '<div class="message-box ' . $type . ' show">';
            echo '<div class="message-icon"><i class="fas fa-' . $icon . '"></i></div>';
            echo '<div class="message-content">' . htmlspecialchars($message['content']) . '</div>';
            echo '<button class="message-close" onclick="this.parentElement.remove()" title="Đóng"><i class="fas fa-times"></i></button>';
            echo '</div>';
            echo '<script>window.playAudioFeedback("' . $type . '");</script>';
        }
        echo '</div>';
        unset($_SESSION['messages']); 
    }
}
?>
