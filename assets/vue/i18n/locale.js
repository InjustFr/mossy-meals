export const LANGUAGES = [
    { value: 'en', label: 'English', intl: 'en-GB' },
    { value: 'fr', label: 'Français', intl: 'fr-FR' },
];

const DEFAULT_LANGUAGE = 'en';

export function currentLanguage() {
    const language = document.body?.dataset.locale;
    return LANGUAGES.some((known) => known.value === language) ? language : DEFAULT_LANGUAGE;
}

export function intlLocale() {
    return LANGUAGES.find((known) => known.value === currentLanguage()).intl;
}

export function chooseLanguage(language) {
    const secure = window.location.protocol === 'https:' ? '; secure' : '';
    document.cookie = `locale=${language}; path=/; max-age=31536000; samesite=lax${secure}`;
    window.location.reload();
}
