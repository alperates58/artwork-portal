import '../css/app.css';
import Alpine from 'alpinejs';
window.Alpine = Alpine;

Alpine.data('mentionPicker', () => ({
    users: [],
    filtered: [],
    show: false,
    query: '',
    cursorIndex: 0,
    atStart: -1,

    async init() {
        try {
            const res = await fetch('/api/internal-users');
            this.users = await res.json();
        } catch (_) {}
    },

    onKeyup(e) {
        const ta = this.$refs.ta;
        const pos = ta.selectionStart;
        const text = ta.value.substring(0, pos);
        const atIdx = text.lastIndexOf('@');

        if (atIdx === -1) { this.show = false; return; }

        const fragment = text.substring(atIdx + 1);
        if (/\s/.test(fragment)) { this.show = false; return; }

        this.atStart = atIdx;
        this.query = fragment.toLowerCase();
        this.filtered = this.users.filter(u => u.name.toLowerCase().startsWith(this.query));
        this.show = this.filtered.length > 0;
        this.cursorIndex = 0;
    },

    onKeydown(e) {
        if (!this.show) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); this.cursorIndex = Math.min(this.cursorIndex + 1, this.filtered.length - 1); }
        if (e.key === 'ArrowUp')   { e.preventDefault(); this.cursorIndex = Math.max(this.cursorIndex - 1, 0); }
        if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); this.select(this.filtered[this.cursorIndex]); }
        if (e.key === 'Escape') { this.show = false; }
    },

    select(user) {
        if (!user) return;
        const ta = this.$refs.ta;
        const before = ta.value.substring(0, this.atStart);
        const after  = ta.value.substring(ta.selectionStart);
        ta.value = before + '@' + user.name + ' ' + after;
        const newPos = before.length + user.name.length + 2;
        ta.setSelectionRange(newPos, newPos);
        ta.dispatchEvent(new Event('input'));
        this.show = false;
        ta.focus();
    },
}));

Alpine.start();
