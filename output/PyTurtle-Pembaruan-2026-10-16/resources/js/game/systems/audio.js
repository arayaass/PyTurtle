export class ForestAudio {
    constructor(settings = {}) {
        this.settings = settings || {};
        this.context = null;
    }
    start() {
        if (!this.settings.music_enabled && !this.settings.sound_enabled) return;
        this.context ??= new (window.AudioContext || window.webkitAudioContext)();
        void this.context.resume();
        if (this.settings.music_enabled && !this.ambient) {
            this.ambient = true;
            this.musicTimer = setInterval(() => {
                if (!document.hidden)
                    this.tone(
                        [261.63, 329.63, 392, 523.25][Math.floor(Math.random() * 4)],
                        1.8,
                        0.018,
                    );
            }, 2600);
        }
    }
    tone(frequency, duration = 0.3, volume = 0.04) {
        if (!this.context) return;
        const oscillator = this.context.createOscillator(),
            gain = this.context.createGain(),
            now = this.context.currentTime;
        oscillator.type = 'sine';
        oscillator.frequency.value = frequency;
        gain.gain.setValueAtTime(0, now);
        gain.gain.linearRampToValueAtTime(volume, now + 0.03);
        gain.gain.exponentialRampToValueAtTime(0.001, now + duration);
        oscillator.connect(gain).connect(this.context.destination);
        oscillator.start();
        oscillator.stop(now + duration);
    }
    checkpoint() {
        if (this.settings.sound_enabled !== false) this.tone(659, 0.22);
    }
    complete() {
        if (this.settings.sound_enabled !== false) {
            this.tone(523, 0.4);
            setTimeout(() => this.tone(784, 0.6), 180);
        }
    }
}
