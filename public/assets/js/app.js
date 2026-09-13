(() => {
    const sidebar = document.getElementById('sidebar');
    const menuButton = document.getElementById('mobile-menu');
    const sidebarBackdrop = document.getElementById('sidebar-backdrop');

    const closeSidebar = () => {
        sidebar?.classList.remove('open');
        sidebarBackdrop?.classList.remove('open');
        document.body.classList.remove('sidebar-open');
    };

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
        setInterval(updateClock, 15000);
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


    if (window.lucide) {
        window.lucide.createIcons({
            attrs: {
                'stroke-width': 2,
            },
        });
    }
})();
