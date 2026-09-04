function fuelStationFlow() {
                return {
                    selectedId: null,
                    selectedName: '',
                    search: '',
                    fragment: '',
                    loading: false,
                    pumpMatches(el) {
                        if (!this.search) return true;
                        const name = (el.dataset.pumpName || '').toLowerCase();
                        return name.includes(this.search.toLowerCase());
                    },
                    noMatches() {
                        if (!this.search) return false;
                        const cards = this.$root ? this.$root.querySelectorAll('[data-pump-card]') : [];
                        let visible = 0;
                        cards.forEach(c => { if (c.style.display !== 'none') visible++; });
                        return visible === 0;
                    },
                    selectAccount(id, name) {
                        this.selectedName = name || '';
                        this.selectedId = id;
                        this.loadFragment(id);
                        this.updateUrl();
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    },
                    backToList() {
                        this.selectedId = null;
                        this.fragment = '';
                        this.selectedName = '';
                        const url = new URL(window.location.href);
                        url.searchParams.delete('selected');
                        window.history.pushState({}, '', url);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    },
                    loadFragment(id, params = {}) {
                        this.loading = true;
                        this.fragment = '';
                        const qs = new URLSearchParams(params);
                        fetch(`/accounts/${id}/pump-flow?${qs.toString()}`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(r => r.text())
                        .then(html => {
                            this.fragment = html;
                            this.loading = false;
                            this.$nextTick(() => this.bindFragmentEvents(id));
                        })
                        .catch(() => {
                            this.fragment = '<p class="text-sm text-red-600 dark:text-red-400">Failed to load pump details.</p>';
                            this.loading = false;
                        });
                    },
                    bindFragmentEvents(id) {
                        const container = this.$refs.fragmentContainer;
                        if (!container) return;
                        const select = container.querySelector('#pump-branch-select');
                        if (select) {
                            select.addEventListener('change', (e) => {
                                this.loadFragment(id, { branch_id: e.target.value });
                            });
                        }
                        container.querySelectorAll('[data-page]').forEach(btn => {
                            btn.addEventListener('click', (e) => {
                                e.preventDefault();
                                const page = btn.getAttribute('data-page');
                                const branch = select ? select.value : '';
                                this.loadFragment(id, { branch_id: branch, page: page });
                            });
                        });
                    },
                    updateUrl() {
                        const url = new URL(window.location.href);
                        url.searchParams.set('selected', this.selectedId);
                        window.history.pushState({}, '', url);
                    },
                    init() {
                        const params = new URLSearchParams(window.location.search);
                        const sel = params.get('selected');
                        if (sel) {
                            const card = this.$root.querySelector('[data-pump-id="' + sel + '"]');
                            const name = card ? card.getAttribute('data-pump-name') : '';
                            this.selectAccount(parseInt(sel, 10), name);
                        }
                    }
                }
            }
        
