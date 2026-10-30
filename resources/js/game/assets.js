// Builds URLs for game art. `paths` comes from the server (see play.blade.php)
// so this file never needs to know the actual asset() base paths.
export function assetsMixin(paths) {
    return {
        categoryIconUrl(id) {
            return id ? `${paths.badgeboard}/${id}.png` : `${paths.badgeboard}/qmark.png`;
        },

        // The question mark spins through the theme icons once its row is complete.
        badgeMiddleUrl(row) {
            if (this.themeSpinRow === row && this.themeSpinId) return paths.themes[this.themeSpinId];
            return `${paths.badgeboard}/qmark.png`;
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

        // Same grid as GameCatalog::BADGEBOARD_GRID; column 3 is reserved for theme icons.
        categoryAt(row, col) {
            const idx = [1, 2, 4, 5].indexOf(col);
            if (idx === -1) return null;
            return [[2, 6, 10, 13], [4, 5, 11, 15], [3, 8, 12, 16], [1, 9, 7, 14]][row - 1][idx];
        },

        badgeOn(row, col) {
            const r = this.badge.icon_states?.[row];
            return r ? r[col] === 1 : false;
        },
    };
}
