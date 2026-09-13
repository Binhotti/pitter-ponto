(() => {
    const sidebar = document.getElementById('sidebar');
    const menuButton = document.getElementById('mobile-menu');

    if (sidebar && menuButton) {
        menuButton.addEventListener('click', () => {
            sidebar.classList.toggle('open');
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

    if (window.lucide) {
        window.lucide.createIcons({
            attrs: {
                'stroke-width': 2,
            },
        });
    }
})();
