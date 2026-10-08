import Alpine from 'alpinejs';
import { createIcons, BookOpenCheck, LayoutDashboard, LibraryBig, Layers, ClipboardList, CircleHelp, BookOpenText, Upload, Users, ChartNoAxesCombined, MonitorSmartphone, UserRound, LogOut, Menu, X, Globe, Eye, ArrowRight, ArrowLeft, ArrowUpRight, CircleCheck, Inbox, Clock3, CalendarDays, Timer, ChevronRight, Info, Target, Check, ClipboardCheck, CloudCheck, Send, BadgeCheck, CircleX, RotateCcw, Plus, UserPlus, Search, ListFilter, Pencil, Archive, Bold, Italic, Heading2, List, Save, ShieldCheck, Download, FileSpreadsheet, CloudUpload, ScanSearch, KeyRound, ShieldAlert, FileQuestionMark } from 'lucide';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { History } from 'lucide';

const icons = { BookOpenCheck, LayoutDashboard, LibraryBig, Layers, ClipboardList, CircleHelp, BookOpenText, Upload, Users, ChartNoAxesCombined, MonitorSmartphone, UserRound, LogOut, Menu, X, Globe, Eye, ArrowRight, ArrowLeft, ArrowUpRight, CircleCheck, Inbox, Clock3, CalendarDays, Timer, ChevronRight, Info, Target, Check, ClipboardCheck, CloudCheck, Send, BadgeCheck, CircleX, RotateCcw, Plus, UserPlus, Search, ListFilter, Pencil, Archive, Bold, Italic, Heading2, List, Save, ShieldCheck, Download, FileSpreadsheet, CloudUpload, ScanSearch, KeyRound, ShieldAlert, FileQuestionMark };

Alpine.data('materialEditor', () => ({
    preview: false, html: '',
    wrap(marker) {
        const input = this.$refs.input;
        const start = input.selectionStart, end = input.selectionEnd;
        input.setRangeText(marker + input.value.slice(start, end) + marker, start, end, 'select');
        input.focus(); this.preview = false;
    },
    prefix(marker) {
        const input = this.$refs.input;
        const start = input.value.lastIndexOf('\n', input.selectionStart - 1) + 1;
        input.setRangeText(marker, start, start, 'end');
        input.focus(); this.preview = false;
    },
    render() {
        this.html = DOMPurify.sanitize(marked.parse(this.$refs.input.value), { FORBID_TAGS: ['img', 'iframe', 'video', 'audio'] });
        this.preview = true;
    },
}));

Alpine.data('practiceSession', () => ({
    answers: {}, pending: {}, remaining: 0, error: false, submitError: false,
    confirmOpen: false, submitting: false, finished: false, saving: false,
    config: null, deadline: 0, tick: null, sync: null,
    get pendingCount() { return Object.keys(this.pending).length; },
    get clock() { return `${Math.floor(this.remaining / 60).toString().padStart(2, '0')}:${(this.remaining % 60).toString().padStart(2, '0')}`; },
    init() {
        this.config = JSON.parse(document.getElementById('practice-config').textContent);
        this.answers = this.config.answers;
        this.remaining = this.config.remaining;
        this.deadline = performance.now() + this.remaining * 1000;
        this.tick = setInterval(() => {
            this.remaining = Math.max(0, Math.ceil((this.deadline - performance.now()) / 1000));
            if (this.remaining === 0 && !this.submitting && !this.finished) this.finish(true);
        }, 500);
        this.sync = setInterval(() => { this.flush(); this.checkStatus(); }, 5000);
    },
    destroy() { clearInterval(this.tick); clearInterval(this.sync); },
    choose(id, answer) {
        if (this.finished || this.remaining <= 0) return;
        this.answers[id] = answer;
        this.pending[id] = answer;
        this.flush();
    },
    go(id) { document.getElementById(`question-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' }); },
    async request(url, data) {
        const response = await fetch(url, {
            method: data ? 'POST' : 'GET', credentials: 'same-origin', cache: 'no-store',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: data ? JSON.stringify(data) : undefined,
            signal: AbortSignal.timeout(10000),
        });
        if (response.redirected || [401, 403, 419].includes(response.status)) {
            this.finished = true;
            window.location.assign(response.redirected ? response.url : (response.status === 403 ? '/dashboard' : '/login'));
            throw new Error('Session or access ended');
        }
        if (!response.ok) throw new Error(`Request failed: ${response.status}`);
        return response.json();
    },
    async flush() {
        if (this.saving || this.finished || !this.pendingCount) return;
        this.saving = true;
        try {
            while (this.pendingCount && this.remaining > 0) {
                const [id, answer] = Object.entries(this.pending)[0];
                const result = await this.request(this.config.answerUrl, { question_id: Number(id), answer });
                if (result.status !== 'in_progress') { this.finished = true; window.location.assign(this.config.resultUrl); return; }
                if (this.pending[id] === answer) delete this.pending[id];
                this.error = false;
            }
        } catch { this.error = true; }
        finally { this.saving = false; }
    },
    async checkStatus() {
        if (this.finished) return;
        try {
            const result = await this.request(this.config.statusUrl);
            if (result.status !== 'in_progress') { this.finished = true; window.location.assign(this.config.resultUrl); return; }
            this.deadline = performance.now() + result.remaining * 1000;
        } catch { /* Pending answers remain queued until the connection returns. */ }
    },
    async finish(expired) {
        if (this.submitting || this.finished || (!expired && (this.pendingCount || this.saving))) return;
        this.submitting = true;
        try {
            if (expired) {
                const status = await this.request(this.config.statusUrl);
                if (status.status === 'in_progress') {
                    this.deadline = performance.now() + Math.max(1, status.remaining) * 1000;
                    return;
                }
                this.finished = true;
                window.location.assign(this.config.resultUrl);
                return;
            }
            const result = await this.request(this.config.submitUrl, {});
            this.finished = true;
            window.location.assign(result.url);
        } catch { this.submitError = true; this.error = true; }
        finally { this.submitting = false; }
    },
}));

document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
}));
document.querySelectorAll('[data-copy-protected]').forEach(element => {
    ['copy', 'cut', 'contextmenu', 'dragstart'].forEach(event => element.addEventListener(event, e => e.preventDefault()));
});
createIcons({ icons: { ...icons, History }, attrs: { 'stroke-width': 1.8, 'aria-hidden': 'true' } });
window.Alpine = Alpine;
Alpine.start();
