class ThemeSwitcher {
    constructor() {
        this.init();
    }

    init() {
        this.loadTheme();
        this.setupEventListeners();
        this.setupThemeToggle();
    }

    loadTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        this.applyTheme(savedTheme);
    }

    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        this.updateToggleButton(theme);
    }

    setupEventListeners() {
        // Listen for theme changes from other tabs
        window.addEventListener('storage', (e) => {
            if (e.key === 'theme') {
                this.applyTheme(e.newValue);
            }
        });
    }

    setupThemeToggle() {
        // Create theme toggle button if it doesn't exist
        if (!document.getElementById('theme-toggle')) {
            const toggle = this.createToggleButton();
            document.body.appendChild(toggle);
        }
    }

    createToggleButton() {
        const button = document.createElement('button');
        button.id = 'theme-toggle';
        button.className = 'btn btn-sm btn-outline-secondary position-fixed';
        button.style.cssText = `
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        `;

        const currentTheme = localStorage.getItem('theme') || 'light';
        this.updateToggleButton(currentTheme);

        button.addEventListener('click', () => {
            this.toggleTheme();
        });

        return button;
    }

    updateToggleButton(theme) {
        const button = document.getElementById('theme-toggle');
        if (!button) return;

        if (theme === 'dark') {
            button.innerHTML = '<i class="bi bi-sun-fill"></i>';
            button.title = 'Switch to light mode';
        } else {
            button.innerHTML = '<i class="bi bi-moon-fill"></i>';
            button.title = 'Switch to dark mode';
        }
    }

    toggleTheme() {
        const currentTheme = localStorage.getItem('theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        this.applyTheme(newTheme);
        this.saveThemeToServer(newTheme);
    }

    async saveThemeToServer(theme) {
        try {
            const response = await fetch('/theme/switch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `theme=${theme}`
            });

            if (!response.ok) {
                console.error('Failed to save theme to server');
            }
        } catch (error) {
            console.error('Error saving theme:', error);
        }
    }
}

// Initialize theme switcher when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ThemeSwitcher();
});
