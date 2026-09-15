import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

if (localStorage.theme === 'dark' || (!localStorage.theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
} else {
    document.documentElement.classList.remove('dark');
}

Alpine.store('theme', {
    isDark: localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),

    init() {
        document.documentElement.classList.toggle('dark', this.isDark);
    },

    toggle() {
        this.isDark = !this.isDark;
        localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.isDark);
    },

    set(isDark) {
        this.isDark = isDark;
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.isDark);
    },
});

Alpine.data('layout', () => ({
    sidebarOpen: false,
    sidebarIconOnly: localStorage.getItem('sidebar_icon_only') === 'true',
    isDesktop: window.innerWidth >= 768,

    init() {
        this.$store.theme.init();

        if (this.isDesktop) {
            this.sidebarOpen = !this.sidebarIconOnly;
        }

        window.addEventListener('resize', () => {
            const wasDesktop = this.isDesktop;
            this.isDesktop = window.innerWidth >= 768;
            if (!this.isDesktop) {
                this.sidebarOpen = false;
            } else if (!wasDesktop && this.isDesktop) {
                this.sidebarOpen = !this.sidebarIconOnly;
            }
        });

        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.sidebarOpen && !this.isDesktop) {
                this.sidebarOpen = false;
            }
        });
    },

    toggleSidebar() {
        if (this.isDesktop) {
            this.sidebarIconOnly = !this.sidebarIconOnly;
            localStorage.setItem('sidebar_icon_only', this.sidebarIconOnly ? 'true' : 'false');
        } else {
            this.sidebarOpen = !this.sidebarOpen;
        }
    },

    isActive(url) {
        const current = window.location.pathname;
        const target = new URL(url, window.location.origin).pathname;
        return current === target || current.startsWith(target + '/');
    },
}));

Alpine.data('inlineCreate', (config) => ({
    submitting: false,
    errors: {},

    async submit(e) {
        e.preventDefault();
        this.submitting = true;
        this.errors = {};
        const form = this.$root.querySelector('form');
        const formData = new FormData(form);
        try {
            const resp = await fetch(config.url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
            });
            const data = await resp.json();
            if (data.success && data.entity) {
                if (config.targetSelect) {
                    document.querySelectorAll(config.targetSelect).forEach((select) => {
                        const exists = select.querySelector('option[value="' + data.entity.id + '"]');
                        if (!exists) {
                            select.add(new Option(data.entity.name, data.entity.id));
                        }
                        select.value = data.entity.id;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                }
                window.dispatchEvent(new CustomEvent('entity-created', { detail: { type: config.entityType, entity: data.entity } }));
                window.dispatchEvent(new CustomEvent('close-modal', { detail: config.modalId }));
                form.reset();
            } else {
                this.errors = data.errors || { _form: data.message || 'Could not create the item.' };
            }
        } catch (err) {
            this.errors = { _form: 'Something went wrong. Please try again.' };
        }
        this.submitting = false;
    },

    fieldError(key) {
        if (!this.errors[key]) return '';
        const v = this.errors[key];
        return Array.isArray(v) ? v[0] : v;
    },
}));

Alpine.data('transportForm', () => ({
    recalc() {
        const form = document.getElementById('transport-form');
        if (!form) return;

        const val = (name) => {
            const el = form.elements[name];
            return el ? (parseFloat(el.value) || 0) : 0;
        };

        const totalSale = Math.round((val('to_bb_sale') + val('paid_sale') + val('to_pay')) * 100) / 100;
        const totalAdvance = Math.round((val('diesel_advance') + val('cash_advance')) * 100) / 100;
        const totalExpense = Math.round((val('freight') + val('loading') + val('unloading') + val('dd') + val('tempu_expense') + val('commission') + val('dtg_office_expense')) * 100) / 100;
        const profit = Math.round((totalSale - totalExpense) * 100) / 100;
        const balanceVehiclePayment = Math.round((totalSale - val('payment')) * 100) / 100;

        const setVal = (name, value) => {
            const el = form.elements[name];
            if (el) el.value = value.toFixed(2);
        };

        setVal('total_sale', totalSale);
        setVal('total_advance', totalAdvance);
        setVal('total_expense', totalExpense);
        setVal('profit', profit);
        setVal('balance_vehicle_payment', balanceVehiclePayment);
    },
    getVal(name) {
        const el = document.getElementById('transport-form')?.elements[name];
        return el ? (parseFloat(el.value) || 0) : 0;
    },
    fmt(name) {
        return this.getVal(name).toFixed(2);
    }
}));

Alpine.data('creatableSelect', (config) => ({
    name: config.name,
    options: config.options || [],
    selected: config.selected || null,
    searchable: config.searchable !== false,
    allowClear: config.allowClear !== false,
    createUrl: config.createUrl || '',
    fallbackUrl: config.fallbackUrl || '',
    syncName: config.syncName || '',
    entityType: config.entityType || 'creatable_select',
    createModalId: config.createModalId || '',
    createFormFields: config.createFormFields || [],
    query: '',
    selectedValue: config.selected ? String(config.selected) : '',
    selectedLabel: '',
    open: false,
    highlighted: 0,
    errorMessage: null,
    dropdownTop: 0,
    dropdownLeft: 0,
    dropdownWidth: 0,

    init() {
        if (this.selected) {
            const match = this.options.find(o => String(o.value) === String(this.selected));
            if (match) {
                this.selectedLabel = match.label;
                this.query = match.label;
            }
        }
        window.addEventListener('entity-created', (e) => {
            if (e.detail.type === this.entityType) {
                const entity = e.detail.entity;
                if (!this.options.some(o => String(o.value) === String(entity.id))) {
                    this.options.push({ id: entity.id, value: entity.id, label: entity.name, branch: entity.branch || '' });
                }
                this.select({ value: entity.id, label: entity.name });
            }
        });
        this._reposition = () => {
            if (this.open) this.positionDropdown();
        };
        this.$watch('open', (val) => {
            if (val) {
                this.$nextTick(() => this.positionDropdown());
                window.addEventListener('scroll', this._reposition, true);
                window.addEventListener('resize', this._reposition);
            } else {
                window.removeEventListener('scroll', this._reposition, true);
                window.removeEventListener('resize', this._reposition);
            }
        });
    },

    positionDropdown() {
        const input = this.$refs.input;
        if (!input) {
            this.dropdownTop = 0;
            this.dropdownLeft = 0;
            this.dropdownWidth = 0;
            return;
        }
        const rect = input.getBoundingClientRect();
        this.dropdownTop = rect.bottom;
        this.dropdownLeft = rect.left;
        this.dropdownWidth = rect.width;
    },

    get managementUrl() {
        const url = this.fallbackUrl || this.createUrl;
        if (!this.query || this.hasExactMatch) return url;
        const separator = url.includes('?') ? '&' : '?';
        return url + separator + 'name=' + encodeURIComponent(this.query);
    },

    get selectedOption() {
        if (!this.selectedValue) return null;
        return this.options.find(o => String(o.value) === String(this.selectedValue)) || null;
    },

    get selectedBalance() {
        const option = this.selectedOption;
        if (!option) return null;
        const balance = option.balance ?? option.current_balance;
        return balance === undefined || balance === null ? null : Number(balance);
    },

    formatBalance() {
        return '₹' + Number(this.selectedBalance || 0).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    },

    get filteredOptions() {
        if (!this.searchable) return this.options;
        const q = (this.query || '').toLowerCase().trim();
        if (!q) return this.options;
        return this.options.filter(o =>
            (o.label || '').toLowerCase().includes(q) ||
            (o.branch || '').toLowerCase().includes(q)
        );
    },

    get hasExactMatch() {
        const q = (this.query || '').trim().toLowerCase();
        return this.options.some(o => (o.label || '').toLowerCase() === q);
    },

    filter() {
        this.open = true;
        this.highlighted = 0;
    },

    move(direction) {
        if (!this.open) {
            this.open = true;
            return;
        }
        const max = this.filteredOptions.length;
        if (max === 0) return;
        this.highlighted = (this.highlighted + direction + max) % max;
    },

    selectHighlighted() {
        const list = this.filteredOptions;
        if (list.length === 0) return;
        this.select(list[this.highlighted]);
    },

    syncLegacyField(value) {
        if (!this.syncName) return;
        const field = document.querySelector('[name="' + this.syncName + '"]');
        if (field) field.value = value;
    },

    select(option) {
        this.selected = option.value;
        this.selectedValue = String(option.value);
        this.selectedLabel = option.label;
        this.query = option.label;
        this.open = false;
        this.errorMessage = false;
        this.syncLegacyField(option.label);
        this.$nextTick(() => {
            this.$dispatch('change', { name: this.name, value: this.selectedValue });
        });
    },

    clear() {
        this.selected = null;
        this.selectedValue = '';
        this.selectedLabel = '';
        this.query = '';
        this.errorMessage = false;
        this.syncLegacyField('');
        this.$dispatch('change', { name: this.name, value: '' });
    },
}));

window.addEventListener('DOMContentLoaded', () => {
    const flashMeta = document.querySelector('meta[name="flash-message"]');
    if (flashMeta) {
        const message = flashMeta.getAttribute('content');
        const type = flashMeta.getAttribute('data-flash-type') || 'success';
        if (message) {
            window.notify(message, type);
        }
    }
});

window.notify = function (message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const tones = {
        success: 'border-emerald-200 dark:border-emerald-900/50',
        error: 'border-rose-200 dark:border-rose-900/50',
        info: 'border-zinc-200 dark:border-zinc-800',
    };

    const toast = document.createElement('div');
    toast.className = `flex w-full max-w-sm transform items-center gap-3 rounded-xl border bg-white p-4 shadow-premium-lg transition-all duration-300 ease-out translate-y-2 opacity-0 dark:bg-zinc-900 ${tones[type] || tones.info}`;

    const dismissBtn = document.createElement('button');
    dismissBtn.className = 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200';
    dismissBtn.setAttribute('aria-label', 'Dismiss');
    dismissBtn.innerHTML = `<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`;
    dismissBtn.addEventListener('click', () => {
        toast.classList.add('opacity-0', 'scale-95');
        setTimeout(() => toast.remove(), 300);
    });

    const msgDiv = document.createElement('div');
    msgDiv.className = 'flex-1 text-sm font-medium text-zinc-900 dark:text-zinc-100';
    msgDiv.textContent = message;

    toast.appendChild(msgDiv);
    toast.appendChild(dismissBtn);
    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

        setTimeout(() => {
            toast.classList.add('opacity-0', 'scale-95');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    };

Alpine.data('sortableList', (config) => ({
    dragId: null,

    dragStart(id) {
        this.dragId = id;
        const el = this.$root.querySelector('[data-sortable-id="' + id + '"]');
        if (el) el.classList.add('opacity-50');
    },

    dragOver(id) {
        if (!this.dragId || this.dragId === id) return;
        const list = this.$root;
        const draggable = list.querySelector('[data-sortable-id="' + this.dragId + '"]');
        const target = list.querySelector('[data-sortable-id="' + id + '"]');
        if (!draggable || !target || draggable === target) return;
        list.insertBefore(draggable, draggable.previousElementSibling === target ? target : target.nextSibling);
    },

    async dragEnd() {
        this.$root.querySelectorAll('[data-sortable-id]').forEach((el) => el.classList.remove('opacity-50'));
        const order = [...this.$root.querySelectorAll('[data-sortable-id]')].map((el, i) => Number(el.dataset.sortableId));
        this.dragId = null;
        try {
            await fetch(config.url, {
                method: 'PATCH',
                body: JSON.stringify({ order }),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            });
            window.dispatchEvent(new CustomEvent('custom-fields-reordered'));
        } catch (e) {
        }
    },
}));

Alpine.start();
