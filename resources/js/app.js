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
            this.refresh();
        } catch (_) {}
    },

    close() {
        this.show = false;
        this.filtered = [];
        this.cursorIndex = 0;
    },

    normalize(value) {
        return String(value ?? '')
            .toLocaleLowerCase('tr')
            .replace(/\s+/g, ' ')
            .trim();
    },

    refresh() {
        const ta = this.$refs.ta;

        if (!ta) {
            this.close();
            return;
        }

        const caretPosition = ta.selectionStart ?? 0;
        const textBeforeCaret = ta.value.substring(0, caretPosition);
        const match = textBeforeCaret.match(/(?:^|\s)@([^\s@]*)$/u);

        if (!match) {
            this.close();
            return;
        }

        const fragment = match[1] ?? '';
        this.atStart = caretPosition - fragment.length - 1;
        this.query = this.normalize(fragment);

        const filteredUsers = this.query === ''
            ? this.users
            : this.users.filter((user) => this.normalize(user.name).includes(this.query));

        this.filtered = filteredUsers.slice(0, 12);
        this.show = this.filtered.length > 0;
        this.cursorIndex = 0;
    },

    onKeyup(e) {
        if (['ArrowDown', 'ArrowUp', 'Enter', 'Tab', 'Escape'].includes(e.key)) {
            return;
        }

        this.refresh();
    },

    onKeydown(e) {
        if (!this.show) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); this.cursorIndex = Math.min(this.cursorIndex + 1, this.filtered.length - 1); }
        if (e.key === 'ArrowUp')   { e.preventDefault(); this.cursorIndex = Math.max(this.cursorIndex - 1, 0); }
        if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); this.select(this.filtered[this.cursorIndex]); }
        if (e.key === 'Escape') { this.close(); }
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
        this.close();
        ta.focus();
    },
}));

Alpine.start();
