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

Alpine.data('fuelStationPicker', (config) => ({
    stations: config.stations || [],
    createUrl: config.createUrl,
    query: config.initialName || '',
    selectedId: config.initialId || null,
    open: false,
    highlighted: 0,
    error: false,

    init() {
        if (this.selectedId) {
            const match = this.stations.find(s => Number(s.id) === Number(this.selectedId));
            if (match) {
                this.query = match.name;
            }
        }
    },

    get filtered() {
        const q = (this.query || '').toLowerCase().trim();
        if (!q) return this.stations;
        return this.stations.filter(s => {
            const name = (s.name || '').toLowerCase();
            const branch = (s.branch || '').toLowerCase();
            return name.includes(q) || branch.includes(q);
        });
    },

    get selectedStation() {
        if (!this.selectedId) return null;
        return this.stations.find(s => Number(s.id) === Number(this.selectedId)) || null;
    },

    get selectedBalance() {
        return this.selectedStation ? Number(this.selectedStation.current_balance) : 0;
    },

    get hasExactMatch() {
        const q = (this.query || '').trim().toLowerCase();
        return this.stations.some(s => (s.name || '').toLowerCase() === q);
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
        const max = this.filtered.length;
        if (max === 0) return;
        this.highlighted = (this.highlighted + direction + max) % max;
    },

    selectHighlighted() {
        const list = this.filtered;
        if (list.length === 0) return;
        this.select(list[this.highlighted]);
    },

    select(station) {
        this.selectedId = station.id;
        this.query = station.name;
        this.open = false;
        this.error = false;
        this.$nextTick(() => {
            const fuelName = document.querySelector('input[name="fuel_station_name"]');
            if (fuelName) fuelName.value = station.name;
        });
    },

    clear() {
        this.selectedId = null;
        this.query = '';
        this.error = false;
        const fuelName = document.querySelector('input[name="fuel_station_name"]');
        if (fuelName) fuelName.value = '';
    }
}));

Alpine.start();

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
