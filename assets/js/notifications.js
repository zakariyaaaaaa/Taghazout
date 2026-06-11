/**
 * notifications.js
 * Zidha f assets/js/ u include fiha admin.css wla main.js
 * Katpoll kol 5s u katupdate badge dyal Messages f sidebar
 */
(function () {
    // ─── Config ───────────────────────────────────────────
    const POLL_INTERVAL = 5000; // 5 secondes
    // Path relatif l get-unread.php — bdlha 7ssb mkan dyal js
    const API_URL = '/taghazout_platform/user/messages/get-unread.php';

    // ─── Find badge element ────────────────────────────────
    // Sidebar kayn fiha: <a href="messages.php"><span class="nav-icon">💬</span><span>Messages</span></a>
    // Kandiw badge <span class="nav-badge" id="msg-badge">
    function getBadge() {
        return document.getElementById('msg-unread-badge');
    }

    // ─── Create badge ila makaynch ────────────────────────
    function ensureBadge() {
        let badge = getBadge();
        if (!badge) {
            // Kqlb 3la nav-item dyal messages
            const links = document.querySelectorAll('.sb-nav a');
            let msgLink = null;
            links.forEach(a => {
                if (a.href && a.href.includes('messages.php')) msgLink = a;
            });
            if (!msgLink) return null;

            badge = document.createElement('span');
            badge.id = 'msg-unread-badge';
            badge.className = 'nav-badge';
            badge.style.display = 'none';
            msgLink.appendChild(badge);
        }
        return badge;
    }

    // ─── Update badge ──────────────────────────────────────
    function updateBadge(count) {
        const badge = ensureBadge();
        if (!badge) return;

        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'inline-flex';
            // Animate
            badge.style.transform = 'scale(1.2)';
            setTimeout(() => badge.style.transform = 'scale(1)', 200);
        } else {
            badge.style.display = 'none';
        }
    }

    // ─── Update page title ────────────────────────────────
    let originalTitle = document.title;
    function updateTitle(count) {
        if (count > 0) {
            document.title = `(${count}) ${originalTitle.replace(/^\(\d+\) /, '')}`;
        } else {
            document.title = originalTitle.replace(/^\(\d+\) /, '');
        }
    }

    // ─── Poll ─────────────────────────────────────────────
    function poll() {
        fetch(API_URL)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    updateBadge(data.count);
                    updateTitle(data.count);

                    // Update badges dyal kol conversation (f chat.php sidebar)
                    if (data.conv_unread) {
                        Object.entries(data.conv_unread).forEach(([userId, cnt]) => {
                            const convItem = document.querySelector(`.conv-item[href*="with=${userId}"] .unread-badge`);
                            if (convItem) {
                                convItem.textContent = cnt;
                                convItem.style.display = cnt > 0 ? 'inline-flex' : 'none';
                            }
                        });
                    }
                }
            })
            .catch(() => {}); // silent fail
    }

    // ─── Start ────────────────────────────────────────────
    // Wait for DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            poll();
            setInterval(poll, POLL_INTERVAL);
        });
    } else {
        poll();
        setInterval(poll, POLL_INTERVAL);
    }

})();