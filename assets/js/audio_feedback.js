// assets/js/audio_feedback.js

(function() {
    const audioPlayer = {
        sounds: {},
        enabled: true, // Always enabled

        init: function() {
            this.sounds.success = new Audio('../assets/audio/success.mp3');
            this.sounds.error = new Audio('../assets/audio/error.mp3');
            this.sounds.info = new Audio('../assets/audio/info.mp3'); // Using info for general messages

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
        }
    };
    audioPlayer.init(); // Initialize the audio player when the script loads

    // Expose a global function to play sounds
    window.playAudio = function(type) {
        audioPlayer.play(type);
    };
})();