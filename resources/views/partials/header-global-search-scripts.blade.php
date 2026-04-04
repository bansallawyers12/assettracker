@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const dataEl = document.getElementById('header-search-index-data');
                if (!dataEl) return;

                let index = [];
                try {
                    index = JSON.parse(dataEl.textContent);
                } catch (_) {
                    return;
                }

                const wrapNodes = document.querySelectorAll('[data-header-search-instance]');
                if (!wrapNodes.length) return;

                const BADGE = {
                    entity: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                    asset: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                    person: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                };
                const BADGE_LABEL = { entity: 'Entity', asset: 'Asset', person: 'Person' };

                function highlight(text, q) {
                    if (!q) return document.createTextNode(text);
                    const idx = text.toLowerCase().indexOf(q.toLowerCase());
                    if (idx === -1) return document.createTextNode(text);
                    const frag = document.createDocumentFragment();
                    frag.appendChild(document.createTextNode(text.slice(0, idx)));
                    const mark = document.createElement('mark');
                    mark.className = 'bg-yellow-200 dark:bg-yellow-600/50 text-gray-900 dark:text-white rounded-sm';
                    mark.textContent = text.slice(idx, idx + q.length);
                    frag.appendChild(mark);
                    frag.appendChild(document.createTextNode(text.slice(idx + q.length)));
                    return frag;
                }

                function showNoResults(panel, input, q) {
                    panel.innerHTML = '';
                    const wrap = document.createElement('div');
                    wrap.className = 'flex items-center gap-2 px-4 py-4 text-sm text-gray-400 dark:text-gray-500';
                    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    svg.setAttribute('class', 'w-4 h-4 shrink-0');
                    svg.setAttribute('fill', 'none');
                    svg.setAttribute('stroke', 'currentColor');
                    svg.setAttribute('viewBox', '0 0 24 24');
                    svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
                    wrap.appendChild(svg);
                    wrap.appendChild(document.createTextNode('No results for '));
                    const strong = document.createElement('strong');
                    strong.className = 'text-gray-600 dark:text-gray-300 font-semibold';
                    strong.textContent = q;
                    wrap.appendChild(strong);
                    panel.appendChild(wrap);
                    input.setAttribute('aria-expanded', 'true');
                    panel.classList.remove('hidden');
                }

                const instances = [];

                wrapNodes.forEach(function (wrap) {
                    const input = wrap.querySelector('[data-header-search-input]');
                    const panel = wrap.querySelector('[data-header-search-results]');
                    const clearBtn = wrap.querySelector('[data-header-search-clear]');
                    const countEl = wrap.querySelector('[data-header-search-count]');
                    if (!input || !panel || !clearBtn) return;

                    const rootId = wrap.getAttribute('data-header-search-root-id') || 'main';
                    let activeIdx = -1;

                    function setActive(idx) {
                        const items = panel.querySelectorAll('[role="option"]');
                        if (!items.length) return;
                        const safe = Math.max(0, Math.min(idx, items.length - 1));
                        items.forEach(function (el, i) {
                            el.classList.toggle('bg-gray-50', i === safe);
                            el.classList.toggle('dark:bg-gray-700/80', i === safe);
                            if (i === safe) {
                                el.setAttribute('aria-selected', 'true');
                                input.setAttribute('aria-activedescendant', el.id);
                                el.scrollIntoView({ block: 'nearest' });
                            } else {
                                el.removeAttribute('aria-selected');
                            }
                        });
                        activeIdx = safe;
                    }

                    function openPanel() {
                        panel.classList.remove('hidden');
                        input.setAttribute('aria-expanded', 'true');
                    }

                    function closePanel() {
                        panel.classList.add('hidden');
                        panel.innerHTML = '';
                        input.setAttribute('aria-expanded', 'false');
                        input.removeAttribute('aria-activedescendant');
                        activeIdx = -1;
                    }

                    function closeFromOutsideClick() {
                        panel.classList.add('hidden');
                        panel.innerHTML = '';
                        input.setAttribute('aria-expanded', 'false');
                        input.removeAttribute('aria-activedescendant');
                        activeIdx = -1;
                        if (!input.value.trim()) {
                            clearBtn.classList.add('hidden');
                            if (countEl) {
                                countEl.textContent = '';
                                countEl.classList.add('hidden');
                            }
                        }
                    }

                    function render(query) {
                        const q = (query || '').trim();
                        const ql = q.toLowerCase();

                        if (!q) {
                            closePanel();
                            clearBtn.classList.add('hidden');
                            if (countEl) {
                                countEl.textContent = '';
                                countEl.classList.add('hidden');
                            }
                            return;
                        }

                        clearBtn.classList.remove('hidden');
                        activeIdx = -1;

                        const matches = index.filter(function (item) {
                            return ((item.label || '') + ' ' + (item.sub || '')).toLowerCase().includes(ql);
                        }).slice(0, 30);

                        if (countEl) {
                            countEl.textContent = matches.length > 0 ? String(matches.length) + (matches.length === 30 ? '+' : '') : '';
                            countEl.classList.toggle('hidden', matches.length === 0);
                        }

                        if (matches.length === 0) {
                            showNoResults(panel, input, q);
                            return;
                        }

                        const frag = document.createDocumentFragment();
                        matches.forEach(function (item, i) {
                            const a = document.createElement('a');
                            a.href = item.url;
                            a.id = 'hgs-' + rootId + '-item-' + i;
                            a.setAttribute('role', 'option');
                            a.className = 'flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700/80 transition-colors group cursor-pointer';

                            const badge = document.createElement('span');
                            badge.className = 'shrink-0 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded ' + (BADGE[item.type] || BADGE.entity);
                            badge.textContent = BADGE_LABEL[item.type] || item.type;

                            const text = document.createElement('div');
                            text.className = 'min-w-0 flex-1';

                            const title = document.createElement('p');
                            title.className = 'text-sm font-medium text-gray-900 dark:text-white truncate';
                            title.appendChild(highlight(item.label || '', q));
                            text.appendChild(title);

                            if (item.sub) {
                                const sub = document.createElement('p');
                                sub.className = 'text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate';
                                sub.appendChild(highlight(item.sub, q));
                                text.appendChild(sub);
                            }

                            const arrow = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                            arrow.setAttribute('class', 'w-3.5 h-3.5 text-gray-300 group-hover:text-gray-500 dark:group-hover:text-gray-400 shrink-0 transition-colors');
                            arrow.setAttribute('fill', 'none');
                            arrow.setAttribute('stroke', 'currentColor');
                            arrow.setAttribute('viewBox', '0 0 24 24');
                            arrow.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>';

                            a.appendChild(badge);
                            a.appendChild(text);
                            a.appendChild(arrow);
                            frag.appendChild(a);
                        });

                        panel.innerHTML = '';
                        panel.appendChild(frag);
                        openPanel();
                    }

                    input.addEventListener('input', function () {
                        render(this.value);
                    });

                    input.addEventListener('focus', function () {
                        if (this.value.trim()) render(this.value);
                    });

                    clearBtn.addEventListener('click', function () {
                        input.value = '';
                        render('');
                        input.focus();
                    });

                    input.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape') {
                            closePanel();
                            this.blur();
                            return;
                        }
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            if (panel.classList.contains('hidden') && this.value.trim()) render(this.value);
                            const items = panel.querySelectorAll('[role="option"]');
                            const count = items.length;
                            if (count === 0) return;
                            const next = activeIdx < count - 1 ? activeIdx + 1 : 0;
                            setActive(next);
                            return;
                        }
                        if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            if (panel.classList.contains('hidden') && this.value.trim()) render(this.value);
                            const items = panel.querySelectorAll('[role="option"]');
                            const count = items.length;
                            if (count === 0) return;
                            const prev = activeIdx > 0 ? activeIdx - 1 : count - 1;
                            setActive(prev);
                            return;
                        }
                        if (e.key === 'Enter' && activeIdx >= 0) {
                            const opts = panel.querySelectorAll('[role="option"]');
                            if (opts[activeIdx]) {
                                e.preventDefault();
                                opts[activeIdx].click();
                            }
                        }
                    });

                    instances.push({ wrap: wrap, closeFromOutsideClick: closeFromOutsideClick });
                });

                document.addEventListener('click', function (e) {
                    instances.forEach(function (inst) {
                        if (!inst.wrap.contains(e.target)) inst.closeFromOutsideClick();
                    });
                });
            });
        </script>
    @endpush
@endonce
