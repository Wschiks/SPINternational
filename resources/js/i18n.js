// Translates a UI string with the current language's lang/*/js.php, which the
// server puts on the page (see components/translations.blade.php), the same way
// __() does in Blade. Strings without a translation are shown as they are.
export function t(text, replacements = {}) {
    let translated = globalThis.spinTranslations?.[text] ?? text;

    for (const [key, value] of Object.entries(replacements)) {
        translated = translated.replaceAll(`:${key}`, value);
    }

    return translated;
}
