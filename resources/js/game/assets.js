// Builds URLs for game art. `paths` comes from the server (see play.blade.php)
// so this file never needs to know the actual asset() base paths.
export function assetsMixin(paths) {
    return {
        categoryIconUrl(id) {
            return id ? `${paths.badgeboard}/${id}.png` : `${paths.badgeboard}/qmark.png`;
        },

        badgeMiddleUrl(row) {
            if (!this.badge.horizontals?.[row]) return `${paths.badgeboard}/qmark.png`;
            return `${paths.badgeboard}/medal.png`;
        },

        themeIconUrl(id) {
            return paths.themes[id];
        },

        checkboxUrl(themeId, i) {
            const checked = (this.themes[themeId]?.checks ?? 0) >= i;
            return `${paths.checkboxes}/` + (checked ? 'checked.png' : 'empty.png');
        },

        ledIconUrl(i) {
            const seg = this.ledSegments[i];
            return seg ? paths.led[seg.color] : paths.led.red;
        },

        // Badgeboard is a 4x5 grid; column 3 is reserved for theme icons.
        categoryAt(row, col) {
            const cols = [1, 2, 4, 5];
            const idx = cols.indexOf(col);
            if (idx === -1) return null;
            return (row - 1) * 4 + idx + 1;
        },

        badgeOn(row, col) {
            const r = this.badge.icon_states?.[row];
            return r ? r[col] === 1 : false;
        },
    };
}
