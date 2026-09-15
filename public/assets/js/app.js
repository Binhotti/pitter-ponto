(() => {
    const sidebar = document.getElementById('sidebar');
    const menuButton = document.getElementById('mobile-menu');
    const sidebarBackdrop = document.getElementById('sidebar-backdrop');
    const desktopSidebarToggle = document.getElementById('desktop-sidebar-toggle');

    const closeSidebar = () => {
        sidebar?.classList.remove('open');
        sidebarBackdrop?.classList.remove('open');
        document.body.classList.remove('sidebar-open');
    };

    const applyDesktopSidebarState = (collapsed) => {
        if (!sidebar || !desktopSidebarToggle) {
            return;
        }

        sidebar.classList.toggle('collapsed', collapsed);
        document.body.classList.toggle('sidebar-collapsed', collapsed);

        desktopSidebarToggle.setAttribute(
            'aria-label',
            collapsed ? 'Expandir menu' : 'Recolher menu'
        );

        desktopSidebarToggle.setAttribute(
            'title',
            collapsed ? 'Expandir menu' : 'Recolher menu'
        );

        const icon = desktopSidebarToggle.querySelector('[data-lucide]');

        if (icon) {
            icon.setAttribute(
                'data-lucide',
                collapsed ? 'panel-left-open' : 'panel-left-close'
            );
        }

        if (window.lucide) {
            window.lucide.createIcons({
                attrs: {
                    'stroke-width': 2,
                },
            });
        }
    };

    if (sidebar && desktopSidebarToggle) {
        const isDesktopSidebar = () => window.innerWidth > 680;

        const restoreSidebarState = () => {
            if (!isDesktopSidebar()) {
                sidebar.classList.remove('collapsed');
                document.body.classList.remove('sidebar-collapsed');
                return;
            }

            const collapsed =
                window.localStorage.getItem('pitter-sidebar-collapsed') === '1';

            applyDesktopSidebarState(collapsed);
        };

        restoreSidebarState();

        desktopSidebarToggle.addEventListener('click', () => {
            if (!isDesktopSidebar()) {
                return;
            }

            const collapsed = !sidebar.classList.contains('collapsed');

            applyDesktopSidebarState(collapsed);

            window.localStorage.setItem(
                'pitter-sidebar-collapsed',
                collapsed ? '1' : '0'
            );
        });

        window.addEventListener('resize', restoreSidebarState);
    }

    if (sidebar && menuButton) {
        menuButton.addEventListener('click', () => {
            const willOpen = !sidebar.classList.contains('open');

            sidebar.classList.toggle('open', willOpen);
            sidebarBackdrop?.classList.toggle('open', willOpen);
            document.body.classList.toggle('sidebar-open', willOpen);
        });

        sidebarBackdrop?.addEventListener('click', closeSidebar);

        sidebar.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', closeSidebar);
        });
    }


    const mobileMoreToggle = document.getElementById('mobile-more-toggle');
    const mobileMoreLayer = document.getElementById('mobile-more-layer');
    const mobileMoreBackdrop = document.getElementById('mobile-more-backdrop');
    const mobileMoreClose = document.getElementById('mobile-more-close');

    const closeMobileMore = () => {
        if (!mobileMoreLayer || !mobileMoreToggle) return;

        mobileMoreLayer.classList.remove('open');
        mobileMoreLayer.setAttribute('aria-hidden', 'true');
        mobileMoreToggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('mobile-more-open');
    };

    const openMobileMore = () => {
        if (!mobileMoreLayer || !mobileMoreToggle) return;

        mobileMoreLayer.classList.add('open');
        mobileMoreLayer.setAttribute('aria-hidden', 'false');
        mobileMoreToggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('mobile-more-open');

        setTimeout(() => mobileMoreClose?.focus(), 120);
    };

    mobileMoreToggle?.addEventListener('click', () => {
        mobileMoreLayer?.classList.contains('open')
            ? closeMobileMore()
            : openMobileMore();
    });

    mobileMoreBackdrop?.addEventListener('click', closeMobileMore);
    mobileMoreClose?.addEventListener('click', closeMobileMore);

    mobileMoreLayer?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', closeMobileMore);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobileMoreLayer?.classList.contains('open')) {
            closeMobileMore();
        }
    });

    const syncMobileBottomNav = () => {
        const homeItem = document.querySelector('[data-mobile-nav="dashboard"]');
        const pointItem = document.querySelector('[data-mobile-nav="point"]');

        if (!homeItem || !pointItem) return;

        const isDashboardRoute =
            window.location.search.includes('route=dashboard')
            || !window.location.search.includes('route=');

        if (!isDashboardRoute) return;

        const isPoint = window.location.hash === '#meu-ponto';
        homeItem.classList.toggle('active', !isPoint);
        pointItem.classList.toggle('active', isPoint);
    };

    syncMobileBottomNav();
    window.addEventListener('hashchange', syncMobileBottomNav);


    const clock = document.getElementById('live-clock');

    if (clock) {
        const updateClock = () => {
            const now = new Date();

            clock.textContent = now.toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        updateClock();
        setInterval(updateClock, 1000);
    }




    const notificationCenter = document.querySelector('.notification-center');
    const notificationToggle = document.querySelector('.notification-toggle');
    const notificationDropdown = document.querySelector('.notification-dropdown');

    if (notificationCenter && notificationToggle && notificationDropdown) {
        const closeNotificationDropdown = () => {
            notificationCenter.classList.remove('open');
            notificationToggle.setAttribute('aria-expanded', 'false');
            notificationDropdown.setAttribute('aria-hidden', 'true');
        };

        notificationToggle.addEventListener('click', (event) => {
            event.stopPropagation();

            const willOpen = !notificationCenter.classList.contains('open');

            notificationCenter.classList.toggle('open', willOpen);
            notificationToggle.setAttribute(
                'aria-expanded',
                willOpen ? 'true' : 'false'
            );
            notificationDropdown.setAttribute(
                'aria-hidden',
                willOpen ? 'false' : 'true'
            );
        });

        document.addEventListener('click', (event) => {
            if (!notificationCenter.contains(event.target)) {
                closeNotificationDropdown();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeNotificationDropdown();
            }
        });

        const browserEnabled =
            notificationToggle.dataset.browserNotificationsEnabled === '1';

        if (
            browserEnabled
            && 'Notification' in window
            && Notification.permission === 'granted'
        ) {
            document.querySelectorAll('[data-smart-notification]')
                .forEach((item) => {
                    const key = item.dataset.notificationKey || '';
                    const title = item.dataset.notificationTitle || 'Pitter Ponto';
                    const message = item.dataset.notificationMessage || '';

                    const storageKey = 'pitter-ponto-notification-' + key;

                    if (key && !sessionStorage.getItem(storageKey)) {
                        new Notification(title, {
                            body: message,
                        });

                        sessionStorage.setItem(storageKey, 'shown');
                    }
                });
        }
    }


    const browserNotifications = document.getElementById('browser-notifications');

    if (browserNotifications) {
        browserNotifications.addEventListener('change', async () => {
            if (!browserNotifications.checked) {
                return;
            }

            if (!('Notification' in window)) {
                browserNotifications.checked = false;
                alert('Seu navegador não oferece suporte a notificações.');
                return;
            }

            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                browserNotifications.checked = false;
            }
        });
    }


    const pointEditModal = document.getElementById('point-edit-modal');
    const pointEditTriggers = document.querySelectorAll('[data-point-edit]');
    const pointEditClosers = document.querySelectorAll('[data-point-edit-close]');

    const closePointEditModal = () => {
        if (!pointEditModal) return;

        pointEditModal.classList.remove('open');
        pointEditModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    if (pointEditModal) {
        pointEditTriggers.forEach((button) => {
            button.addEventListener('click', () => {
                const entryId = button.dataset.entryId || '';
                const date = button.dataset.date || '';
                const label = button.dataset.label || '';
                const time = button.dataset.time || '';

                const entryIdInput = document.getElementById('edit-entry-id');
                const dateInput = document.getElementById('edit-entry-date');
                const labelInput = document.getElementById('edit-entry-label');
                const timeInput = document.getElementById('edit-entry-time');

                if (entryIdInput) entryIdInput.value = entryId;
                if (dateInput) dateInput.value = date;
                if (labelInput) labelInput.value = label;
                if (timeInput) timeInput.value = time;

                pointEditModal.classList.add('open');
                pointEditModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';

                setTimeout(() => {
                    timeInput?.focus();
                }, 50);
            });
        });

        pointEditClosers.forEach((button) => {
            button.addEventListener('click', closePointEditModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && pointEditModal.classList.contains('open')) {
                closePointEditModal();
            }
        });
    }


    const historyRangeForm = document.querySelector('.history-range-custom');
    if (historyRangeForm) {
        const fromInput = historyRangeForm.querySelector('input[name="from"]');
        const toInput = historyRangeForm.querySelector('input[name="to"]');

        const clampRange = (changedInput) => {
            if (!fromInput || !toInput || !fromInput.value || !toInput.value) {
                return;
            }

            const from = new Date(fromInput.value + 'T00:00:00');
            const to = new Date(toInput.value + 'T00:00:00');

            if (from > to) {
                if (changedInput === fromInput) {
                    toInput.value = fromInput.value;
                } else {
                    fromInput.value = toInput.value;
                }
                return;
            }

            const maxSpanMs = 6 * 24 * 60 * 60 * 1000;

            if ((to - from) > maxSpanMs) {
                if (changedInput === fromInput) {
                    const newTo = new Date(from.getTime() + maxSpanMs);
                    toInput.value = newTo.toISOString().slice(0, 10);
                } else {
                    const newFrom = new Date(to.getTime() - maxSpanMs);
                    fromInput.value = newFrom.toISOString().slice(0, 10);
                }
            }
        };

        fromInput?.addEventListener('change', () => clampRange(fromInput));
        toInput?.addEventListener('change', () => clampRange(toInput));
    }


    const calendarModal = document.getElementById('calendar-detail-modal');

    if (calendarModal) {
        const dayButtons = document.querySelectorAll('[data-calendar-day]');
        const closeButtons = calendarModal.querySelectorAll('[data-calendar-close]');
        const subtitle = document.getElementById('calendar-detail-subtitle');
        const status = document.getElementById('calendar-detail-status');
        const entriesContainer = document.getElementById('calendar-detail-entries');
        const worked = document.getElementById('cal-worked');
        const extra65 = document.getElementById('cal-extra65');
        const extra100 = document.getElementById('cal-extra100');
        const bank = document.getElementById('cal-bank');
        const money = document.getElementById('calendar-detail-money');
        const estimated = document.getElementById('cal-estimated');
        const historyLink = document.getElementById('calendar-history-link');

        const closeCalendarModal = () => {
            calendarModal.classList.remove('open');
            calendarModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        };

        const statusLabel = (detail) => {
            if (detail.holiday) return ['Feriado', 'holiday'];
            if (detail.dayOff) return [detail.dayOff, 'dayoff'];
            if (detail.absence) return ['Ausência', 'absence'];
            if (detail.completed) return ['Expediente finalizado', 'completed'];
            if (detail.entries.length > 0) return ['Expediente em andamento', 'pending'];
            return ['Sem registros', 'neutral'];
        };

        dayButtons.forEach((button) => {
            button.addEventListener('click', () => {
                let detail;

                try {
                    detail = JSON.parse(button.dataset.calendarDetail || '{}');
                } catch {
                    return;
                }

                if (subtitle) {
                    subtitle.textContent = `${detail.weekday} • ${detail.dateLabel}`;
                }

                if (status) {
                    const [label, statusClass] = statusLabel(detail);
                    status.className = `calendar-detail-status ${statusClass}`;
                    status.textContent = label;
                }

                if (entriesContainer) {
                    entriesContainer.innerHTML = '';

                    if (!detail.entries || detail.entries.length === 0) {
                        const empty = document.createElement('div');
                        empty.className = 'calendar-detail-empty';
                        empty.textContent = 'Nenhuma marcação registrada neste dia.';
                        entriesContainer.appendChild(empty);
                    } else {
                        detail.entries.forEach((entry) => {
                            const row = document.createElement('div');
                            row.className = 'calendar-detail-entry';

                            const iconWrap = document.createElement('span');
                            iconWrap.className = 'calendar-detail-entry-icon';

                            const icon = document.createElement('i');
                            icon.setAttribute('data-lucide', entry.icon || 'circle');
                            iconWrap.appendChild(icon);

                            const copy = document.createElement('span');
                            copy.className = 'calendar-detail-entry-copy';

                            const label = document.createElement('small');
                            label.textContent = entry.label || 'Registro';

                            const time = document.createElement('strong');
                            time.textContent = entry.time || '--:--';

                            copy.appendChild(label);
                            copy.appendChild(time);

                            if (entry.manual) {
                                const badge = document.createElement('em');
                                badge.textContent = 'Ajustado';
                                copy.appendChild(badge);
                            }

                            row.appendChild(iconWrap);
                            row.appendChild(copy);
                            entriesContainer.appendChild(row);
                        });
                    }
                }

                if (worked) worked.textContent = detail.worked || '0h 00min';
                if (extra65) extra65.textContent = detail.extra65 || '0h 00min';
                if (extra100) extra100.textContent = detail.extra100 || '0h 00min';

                if (bank) {
                    bank.textContent = detail.bank || '0h 00min';
                    bank.classList.toggle('balance-positive', Number(detail.bankRaw) > 0);
                    bank.classList.toggle('balance-negative', Number(detail.bankRaw) < 0);
                }

                if (money && estimated) {
                    if (detail.estimated) {
                        estimated.textContent = detail.estimated;
                        money.hidden = false;
                    } else {
                        money.hidden = true;
                    }
                }

                if (historyLink && detail.historyUrl) {
                    historyLink.href = detail.historyUrl;
                }

                calendarModal.classList.add('open');
                calendarModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';

                if (window.lucide) {
                    window.lucide.createIcons({
                        attrs: {'stroke-width': 2},
                    });
                }
            });
        });

        closeButtons.forEach((button) => {
            button.addEventListener('click', closeCalendarModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && calendarModal.classList.contains('open')) {
                closeCalendarModal();
            }
        });
    }


    if (window.lucide) {
        window.lucide.createIcons({
            attrs: {
                'stroke-width': 2,
            },
        });
    }
})();
