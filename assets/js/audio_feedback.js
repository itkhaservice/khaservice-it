// assets/js/audio_feedback.js

(function() {
    const audioPlayer = {
        sounds: {},
        enabled: false, // Default to disabled, will be loaded from localStorage

        init: function() {
            this.sounds.success = new Audio('../assets/audio/success.mp3');
            this.sounds.error = new Audio('../assets/audio/error.mp3');
            this.sounds.info = new Audio('../assets/audio/info.mp3'); // Using info for general messages

            // Load preference from localStorage
            const storedPreference = localStorage.getItem('audioFeedbackEnabled');
            this.enabled = storedPreference === 'true';

            // Ensure volume is low to be micro-interaction friendly
            for (const key in this.sounds) {
                if (this.sounds.hasOwnProperty(key)) {
                    this.sounds[key].volume = 0.2; // Set a low volume
                }
            }
        },

        play: function(type) {
            if (this.enabled && this.sounds[type]) {
                this.sounds[type].play().catch(e => console.error("Error playing audio:", e));
            }
        },

        toggle: function(enable) {
            this.enabled = enable;
            localStorage.setItem('audioFeedbackEnabled', this.enabled);
            console.log("Audio feedback " + (this.enabled ? "enabled" : "disabled"));
        }
    };

    audioPlayer.init();

    // Expose a global function to allow PHP to trigger sounds
    window.playAudioFeedback = function(type) {
        audioPlayer.play(type);
    };

    // Expose toggle function for UI
    window.toggleAudioFeedback = function(enable) {
        audioPlayer.toggle(enable);
    };

    // Optionally, setup a listener for a custom event if messages are added dynamically
    // document.addEventListener('messageDisplayed', function(e) {
    //     if (e.detail && e.detail.type) {
    //         audioPlayer.play(e.detail.type);
    //     }
    // });
})();