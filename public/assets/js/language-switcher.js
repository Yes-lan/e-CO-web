/**
 * Language Switcher Module
 * Handles language dropdown and switching for authenticated pages
 */

class LanguageSwitcher {
    constructor() {
        this.currentLocale = document.documentElement.lang || 'fr';
        this.dropdownBtn = document.getElementById('languageDropdownBtn');
        this.dropdownMenu = document.getElementById('languageDropdownMenu');
        this.currentLanguageFlag = document.getElementById('currentLanguageFlag');
        this.currentLanguageText = document.getElementById('currentLanguageText');
        
        if (this.dropdownBtn && this.dropdownMenu) {
            this.init();
        }
    }

    async init() {
        await this.loadLanguages();
        this.setupEventListeners();
    }

    async loadLanguages() {
        try {
            const response = await fetch('/api/languages');
            const data = await response.json();
            const languages = data.languages || [];
            
            if (languages.length > 0) {
                this.updateCurrentLanguageDisplay(languages);
                this.populateDropdown(languages);
            }
        } catch (error) {
            console.error('Error loading languages:', error);
        }
    }

    updateCurrentLanguageDisplay(languages) {
        const currentLang = languages.find(l => l.code === this.currentLocale);
        if (currentLang && this.currentLanguageFlag && this.currentLanguageText) {
            this.currentLanguageFlag.innerHTML = `<span class="fi fi-${currentLang.flagIcon}"></span>`;
            this.currentLanguageText.textContent = currentLang.displayedText;
        }
    }

    populateDropdown(languages) {
        if (!this.dropdownMenu) return;
        
        this.dropdownMenu.innerHTML = languages.map(lang => `
            <button class="language-dropdown-item ${lang.code === this.currentLocale ? 'active' : ''}" 
                    data-locale="${lang.code}">
                <span class="language-flag"><span class="fi fi-${lang.flagIcon}"></span></span>
                <span class="language-name">${lang.displayedText}</span>
            </button>
        `).join('');
        
        // Add click handlers to dropdown items
        this.dropdownMenu.querySelectorAll('.language-dropdown-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const locale = item.dataset.locale;
                this.changeLanguage(locale);
            });
        });
    }

    setupEventListeners() {
        // Toggle dropdown on button click
        this.dropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.dropdownMenu.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.dropdownBtn.contains(e.target) && !this.dropdownMenu.contains(e.target)) {
                this.dropdownMenu.classList.remove('show');
            }
        });

        // Close dropdown on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.dropdownMenu.classList.contains('show')) {
                this.dropdownMenu.classList.remove('show');
            }
        });
    }

    changeLanguage(locale) {
        if (locale === this.currentLocale) {
            this.dropdownMenu.classList.remove('show');
            return;
        }

        // Store locale preference in localStorage
        localStorage.setItem('locale', locale);
        
        // Reload page with locale query parameter (LocaleSubscriber will save to session)
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('_locale', locale);
        window.location.href = currentUrl.toString();
    }
}

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('languageDropdownBtn')) {
        window.languageSwitcher = new LanguageSwitcher();
    }
});

// Also initialize on Turbo Drive page loads (for SPA navigation)
document.addEventListener('turbo:load', () => {
    if (document.getElementById('languageDropdownBtn') && !window.languageSwitcher) {
        window.languageSwitcher = new LanguageSwitcher();
    }
});
