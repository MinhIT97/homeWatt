import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Theme store for dark mode
document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        mode: localStorage.getItem('theme') || 'system',

        init() {
            this.apply();
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.mode === 'system') this.apply();
            });
        },

        toggle() {
            this.mode = this.mode === 'dark' ? 'light' : 'dark';
            localStorage.setItem('theme', this.mode);
            this.apply();
        },

        setMode(mode) {
            this.mode = mode;
            localStorage.setItem('theme', mode);
            this.apply();
        },

        apply() {
            const isDark = this.mode === 'dark' ||
                (this.mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', isDark);
        },

        get isDark() {
            return document.documentElement.classList.contains('dark');
        },

        get icon() {
            if (this.mode === 'dark') return '🌙';
            if (this.mode === 'light') return '☀️';
            return '🖥️';
        },

        get nextMode() {
            if (this.mode === 'light') return 'dark';
            if (this.mode === 'dark') return 'system';
            return 'light';
        },

        get nextIcon() {
            if (this.mode === 'light') return '🌙';
            if (this.mode === 'dark') return '🖥️';
            return '☀️';
        },
    });
});

Alpine.start();
