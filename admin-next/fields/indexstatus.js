/**
 * indexstatus — Admin2 version of the classic "Search Index Status" field.
 * Shows whether the index exists and how many documents it holds, with a
 * button to (re)build it. Display only: it never sends a value to the form.
 */

const TAG = window.__GRAV_FIELD_TAG;

class TNTSearchIndexStatus extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this._field = null;
        this._value = null;
        this._state = { loading: true, busy: false, indexed: false, message: '' };
    }

    set field(v) { this._field = v; }
    get field() { return this._field; }
    set value(v) { this._value = v; }
    get value() { return this._value; }

    connectedCallback() {
        this._render();
        this._load();
    }

    _url(path) {
        return (window.__GRAV_API_SERVER_URL || '') + (window.__GRAV_API_PREFIX || '/api/v1') + path;
    }

    async _call(method, path) {
        const headers = {};
        if (window.__GRAV_API_TOKEN) headers['X-API-Token'] = window.__GRAV_API_TOKEN;
        const resp = await fetch(this._url(path), { method, headers });
        const json = await resp.json().catch(() => ({}));
        if (!resp.ok) throw new Error(json.detail || json.message || `HTTP ${resp.status}`);
        return json.data || {};
    }

    async _load() {
        try {
            const data = await this._call('GET', '/tntsearch/status');
            this._state = { ...this._state, indexed: !!data.indexed, message: data.message || '' };
        } catch (e) {
            this._state = { ...this._state, indexed: false, message: e.message };
        }
        this._state.loading = false;
        this._render();
    }

    async _reindex() {
        this._state.busy = true;
        this._render();
        try {
            const data = await this._call('POST', '/tntsearch/reindex');
            this._state = { ...this._state, indexed: !!data.indexed, message: data.message || '' };
        } catch (e) {
            this._state = { ...this._state, indexed: false, message: e.message };
        }
        this._state.busy = false;
        this._render();
    }

    _escape(s) {
        return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    _render() {
        const { loading, busy, indexed, message } = this._state;
        const label = busy ? 'Indexing…' : (indexed ? 'Re-Index Content' : 'Index Content');
        const status = loading ? 'Checking index…' : message;

        this.shadowRoot.innerHTML = `
            <style>
                :host { display: block; font-family: inherit; color: var(--foreground); }
                .row { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
                button {
                    font: inherit; font-size: 0.875rem; font-weight: 500; cursor: pointer;
                    padding: 0.4rem 0.9rem; border-radius: var(--radius, 0.375rem);
                    border: 1px solid var(--border); background: var(--background); color: var(--foreground);
                }
                button.primary { background: var(--primary); color: var(--primary-foreground); border-color: var(--primary); }
                button:disabled { opacity: 0.6; cursor: default; }
                .status { font-size: 0.875rem; color: var(--muted-foreground); }
                .status.error { color: var(--destructive); }
            </style>
            <div class="row">
                <button type="button" class="${indexed ? '' : 'primary'}" ${loading || busy ? 'disabled' : ''}>${label}</button>
                <span class="status ${!loading && !indexed ? 'error' : ''}">${this._escape(status)}</span>
            </div>
        `;
        this.shadowRoot.querySelector('button').addEventListener('click', () => this._reindex());
    }
}

customElements.define(TAG, TNTSearchIndexStatus);
