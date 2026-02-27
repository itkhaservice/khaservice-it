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
            $type = strtolower(trim(htmlspecialchars($message['type'])));
            $icon = 'info-circle';
            
            if ($type === 'success') $icon = 'check-circle';
            elseif ($type === 'error' || $type === 'danger') { $icon = 'exclamation-circle'; $type = 'error'; }
            elseif ($type === 'warning') $icon = 'exclamation-triangle';
            elseif ($type === 'info') $icon = 'info-circle';

            echo '<div class="message-box ' . $type . ' show" style="display: flex; align-items: center; opacity: 1;">';
            echo '<div class="message-icon"><i class="fas fa-' . $icon . '"></i></div>';
            echo '<div class="message-content" style="color: #1e293b;">' . htmlspecialchars($message['content']) . '</div>';
            echo '<button class="message-close" onclick="this.parentElement.remove()" title="Đóng"><i class="fas fa-times"></i></button>';
            echo '<div class="progress-bar"></div>';
            echo '</div>';
            echo '<script>window.playAudioFeedback("' . $type . '");</script>';
        }
        echo '</div>';
        unset($_SESSION['messages']); 
    }
}
?>
