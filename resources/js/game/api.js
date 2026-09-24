// Talks to the Laravel game API (see routes/web.php + GameController).
export function apiMixin() {
    return {
        csrf() {
            return document.querySelector('meta[name="csrf-token"]').content;
        },

        async api(method, path, body) {
            const res = await fetch(path, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrf(),
                    Accept: 'application/json',
                },
                body: body ? JSON.stringify(body) : undefined,
            });
            const data = await res.json();
            if (!res.ok) {
                this.message = data.message || 'Er ging iets mis';
                throw new Error(data.message);
            }
            return data;
        },
    };
}
