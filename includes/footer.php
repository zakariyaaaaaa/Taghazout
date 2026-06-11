<?php
// ============================================================
// includes/footer.php — FULL & FIXED
// ============================================================

// ── Fetch admin & messages قبل HTML ──────────────────────
$widgetAdminId = 0;
$widgetMe      = 0;
$wUnread       = 0;
$wMsgs         = [];

if (isset($_SESSION['user_id'], $pdo)) {
    $widgetMe = (int)$_SESSION['user_id'];

    $widgetAdmin = $pdo->query("SELECT id, name FROM users WHERE role='admin' LIMIT 1")->fetch();
    $widgetAdminId = $widgetAdmin ? (int)$widgetAdmin['id'] : 0;

    if ($widgetAdminId && $widgetAdminId !== $widgetMe) {
        // unread count
        $wq = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE sender_id=? AND receiver_id=? AND is_read=0");
        $wq->execute([$widgetAdminId, $widgetMe]);
        $wUnread = (int)$wq->fetchColumn();

        // last 30 messages
        $wq2 = $pdo->prepare("
            SELECT * FROM (
                SELECT id, sender_id, message, created_at, is_read
                FROM messages
                WHERE (sender_id=? AND receiver_id=?)
                   OR (sender_id=? AND receiver_id=?)
                ORDER BY created_at DESC LIMIT 30
            ) sub ORDER BY created_at ASC
        ");
        $wq2->execute([$widgetMe, $widgetAdminId, $widgetAdminId, $widgetMe]);
        $wMsgs = $wq2->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-brand">
            <span>🏄 Taghazout Platform</span>
            <p>La meilleure plateforme pour découvrir Taghazout</p>
        </div>
        <div class="footer-links">
            <div class="footer-col">
                <h4>Navigation</h4>
                <a href="../hotels.php">Hôtels</a>
                <a href="../activities.php">Activités</a>
                <a href="../surf-courses.php">Surf</a>
                <a href="../restaurants.php">Restaurants</a>
            </div>
            <div class="footer-col">
                <h4>Compte</h4>
                <a href="../auth/login.php">Connexion</a>
                <a href="../auth/signup.php">Inscription</a>
                <a href="../profile/profile.php">Mon Profil</a>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <p>📍 Taghazout, Agadir</p>
                <p>📞 +212 610225288</p>
                <p>✉️ Taghaazout@gmail.com</p>
            </div>
        </div>
    </div>
    <div class="footer-bottom" style="display:flex; justify-content:center;">
        <p>© <?= date('Y') ?> Taghazout Platform. Tous droits réservés.</p>
    </div>
</footer>

<!-- AI + MESSAGES CHAT WIDGET -->
<div class="ai-chat-widget" id="aiChat">

    <button class="ai-chat-toggle" id="aiChatToggle" onclick="toggleChat()">
        <span class="ai-chat-icon">🤖</span>
        <span class="ai-chat-label">Assistant</span>
        <span class="ai-chat-dot" id="aiDot"></span>
        <span class="msg-unread-dot" id="msgUnreadDot" style="display:none;"></span>
    </button>

    <div class="ai-chat-box" id="aiChatBox">

        <!-- HEADER -->
        <div class="ai-chat-header">
            <div style="display:flex; align-items:center; gap:0.7rem;">
                <div class="ai-avatar" id="headerIcon">🤖</div>
                <div>
                    <div style="font-weight:700; font-size:0.9rem;" id="headerTitle">Assistant Taghazout</div>
                    <div style="font-size:0.72rem; opacity:0.7; display:flex; align-items:center; gap:0.3rem;">
                        <span style="width:6px;height:6px;background:#10b981;border-radius:50%;display:inline-block;"></span>
                        En ligne
                    </div>
                </div>
            </div>
            <button onclick="toggleChat()" style="background:rgba(255,255,255,0.15);border:none;border-radius:50%;width:28px;height:28px;color:white;cursor:pointer;font-size:1rem;">✕</button>
        </div>

        <!-- TABS -->
        <div class="widget-tabs">
            <button class="widget-tab active" id="tabAI" onclick="switchTab('ai')">🤖 Assistant</button>
            <button class="widget-tab" id="tabMsg" onclick="switchTab('msg')">
                💬 Messages
                <span class="tab-badge" id="tabBadge" style="display:none;">0</span>
            </button>
        </div>

        <!-- TAB AI -->
        <div id="panelAI">
            <div class="ai-chat-messages" id="aiMessages">
                <div class="ai-msg bot">
                    <div class="ai-msg-bubble">
                        👋 Bonjour ! Je suis votre assistant Taghazout.<br><br>
                        Je peux vous aider à trouver:<br>
                        🏨 Hôtels · 🎯 Activités · 🏄 Surf · 🍽️ Restaurants<br><br>
                        Comment puis-je vous aider ?
                    </div>
                </div>
            </div>
            <div class="ai-suggestions" id="aiSuggestions">
                <button onclick="sendSuggestion('Quel hôtel recommandez-vous ?')">🏨 Hôtels</button>
                <button onclick="sendSuggestion('Quelles activités sont disponibles ?')">🎯 Activités</button>
                <button onclick="sendSuggestion('Je veux apprendre le surf')">🏄 Surf</button>
            </div>
            <div class="ai-chat-input">
                <input type="text" id="aiInput" placeholder="Posez votre question..."
                    onkeypress="if(event.key==='Enter') sendMessage()">
                <button onclick="sendMessage()" id="aiSendBtn">➤</button>
            </div>
        </div>

        <!-- TAB MESSAGES -->
        <div id="panelMsg" style="display:none; flex-direction:column; flex:1;">

            <?php if (!$widgetMe): ?>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem;text-align:center;gap:1rem;background:#f8fafc;">
                <div style="font-size:2.5rem;">🔒</div>
                <p style="font-weight:700;color:#1e293b;font-size:.95rem;">Connectez-vous pour envoyer un message</p>
                <a href="/auth/login.php" style="padding:.6rem 1.4rem;background:#0ea5e9;color:#fff;border-radius:50px;font-weight:600;font-size:.85rem;text-decoration:none;">Se connecter</a>
            </div>
            <?php else: ?>

            <div class="ai-chat-messages" id="msgMessages" style="max-height:300px;">
                <?php if (empty($wMsgs)): ?>
                <div style="text-align:center;padding:2rem;color:#94a3b8;">
                    <div style="font-size:2rem;margin-bottom:.5rem;">👋</div>
                    <p style="font-size:.85rem;">Envoyez un message au support<br>On vous répond rapidement !</p>
                </div>
                <?php else: ?>
                    <?php foreach ($wMsgs as $wm):
                        $isMine = (int)$wm['sender_id'] === $widgetMe;
                        $t = date('H:i', strtotime($wm['created_at']));
                    ?>
                    <div class="ai-msg <?= $isMine ? 'user' : 'bot' ?>" data-msg-id="<?= (int)$wm['id'] ?>">
                        <div class="ai-msg-bubble">
                            <?= nl2br(htmlspecialchars($wm['message'])) ?>
                            <div style="font-size:.65rem;opacity:.6;margin-top:.3rem;text-align:<?= $isMine ? 'right' : 'left' ?>;">
                                <?= $t ?><?= $isMine ? ($wm['is_read'] ? ' ✓✓' : ' ✓') : '' ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="ai-chat-input">
                <input type="text" id="msgInput"
                       placeholder="Écrire au support…"
                       onkeypress="if(event.key==='Enter') msgSend()">
                <button onclick="msgSend()" id="msgSendBtn">➤</button>
            </div>

            <?php endif; ?>
        </div>

    </div>
</div>

<style>
.ai-chat-widget{position:fixed;bottom:2rem;right:2rem;z-index:9999;font-family:'DM Sans',sans-serif;}
.ai-chat-toggle{display:flex;align-items:center;gap:.5rem;background:linear-gradient(135deg,#0ea5e9,#38bdf8);color:white;border:none;border-radius:50px;padding:.75rem 1.4rem;font-size:.9rem;font-weight:600;cursor:pointer;box-shadow:0 8px 30px rgba(14,165,233,.4);transition:all .3s ease;position:relative;}
.ai-chat-toggle:hover{transform:translateY(-3px);}
.ai-chat-icon{font-size:1.2rem;}
.ai-chat-dot{position:absolute;top:6px;right:6px;width:8px;height:8px;background:#10b981;border-radius:50%;border:2px solid white;display:none;}
.msg-unread-dot{position:absolute;top:4px;right:4px;width:10px;height:10px;background:#ef4444;border-radius:50%;border:2px solid white;}
.ai-chat-box{position:absolute;bottom:calc(100% + 1rem);right:0;width:360px;background:white;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.15);overflow:hidden;display:none;flex-direction:column;border:1px solid rgba(14,165,233,.1);animation:slideUp .3s ease;}
@keyframes slideUp{from{opacity:0;transform:translateY(20px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
.ai-chat-box.open{display:flex;}
.ai-chat-header{background:linear-gradient(135deg,#0a1628,#0ea5e9);padding:1rem 1.2rem;display:flex;align-items:center;justify-content:space-between;color:white;}
.ai-avatar{width:38px;height:38px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;}
.widget-tabs{display:flex;background:#f8fafc;border-bottom:1.5px solid #e2e8f0;}
.widget-tab{flex:1;padding:.65rem .5rem;border:none;background:transparent;font-family:'DM Sans',sans-serif;font-size:.82rem;font-weight:600;color:#94a3b8;cursor:pointer;transition:all .18s;display:flex;align-items:center;justify-content:center;gap:.3rem;border-bottom:2.5px solid transparent;margin-bottom:-1.5px;}
.widget-tab:hover{color:#0ea5e9;}
.widget-tab.active{color:#0ea5e9;border-bottom-color:#0ea5e9;background:white;}
.tab-badge{background:#ef4444;color:white;font-size:.65rem;font-weight:700;border-radius:50px;padding:.05rem .35rem;min-width:16px;text-align:center;}
.ai-chat-messages{flex:1;padding:1rem;overflow-y:auto;max-height:280px;display:flex;flex-direction:column;gap:.8rem;background:#f8fafc;}
.ai-msg{display:flex;}
.ai-msg.bot{justify-content:flex-start;}
.ai-msg.user{justify-content:flex-end;}
.ai-msg-bubble{max-width:80%;padding:.7rem 1rem;border-radius:16px;font-size:.85rem;line-height:1.6;}
.ai-msg.bot .ai-msg-bubble{background:white;color:#1a2332;border-bottom-left-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06);}
.ai-msg.user .ai-msg-bubble{background:linear-gradient(135deg,#0ea5e9,#38bdf8);color:white;border-bottom-right-radius:4px;}
.ai-typing .ai-msg-bubble{background:white;padding:.8rem 1rem;}
.typing-dots{display:flex;gap:4px;align-items:center;}
.typing-dots span{width:6px;height:6px;background:#94a3b8;border-radius:50%;animation:typing 1.2s infinite;}
.typing-dots span:nth-child(2){animation-delay:.2s;}
.typing-dots span:nth-child(3){animation-delay:.4s;}
@keyframes typing{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}
.ai-suggestions{padding:.6rem 1rem;display:flex;gap:.5rem;flex-wrap:wrap;background:white;border-top:1px solid #f0f4f8;}
.ai-suggestions button{padding:.35rem .75rem;background:#f0f9ff;border:1px solid rgba(14,165,233,.2);border-radius:50px;font-size:.75rem;color:#0ea5e9;cursor:pointer;font-weight:500;transition:all .2s;}
.ai-suggestions button:hover{background:#0ea5e9;color:white;}
.ai-chat-input{display:flex;gap:.5rem;padding:.8rem 1rem;background:white;border-top:1px solid #f0f4f8;}
.ai-chat-input input{flex:1;padding:.6rem 1rem;border:1.5px solid rgba(14,165,233,.15);border-radius:50px;font-size:.85rem;outline:none;color:#1a2332;background:#f8fafc;transition:border-color .2s;}
.ai-chat-input input:focus{border-color:#0ea5e9;background:white;}
.ai-chat-input button{width:38px;height:38px;background:linear-gradient(135deg,#0ea5e9,#38bdf8);color:white;border:none;border-radius:50%;cursor:pointer;font-size:1rem;transition:all .2s;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(14,165,233,.3);}
.ai-chat-input button:hover{transform:scale(1.1);}
@media(max-width:480px){.ai-chat-box{width:calc(100vw - 2rem);right:-.5rem;}.ai-chat-widget{right:1rem;bottom:1rem;}}
</style>

<script>
let isOpen         = false;
let chatHistory    = [];
let currentTab     = 'ai';
let msgLastId      = 0;
let msgPoll        = null;
let msgSeenReplies = [];

const WIDGET_ME       = <?= $widgetMe ?>;
const WIDGET_ADMIN_ID = <?= $widgetAdminId ?>;
const WIDGET_UNREAD   = <?= $wUnread ?>;

// ── Init unread badge ──────────────────────────────────────
if (WIDGET_UNREAD > 0) {
    document.getElementById('msgUnreadDot').style.display = 'block';
    const tb = document.getElementById('tabBadge');
    tb.textContent = WIDGET_UNREAD;
    tb.style.display = 'inline-block';
}

// ── Toggle widget ──────────────────────────────────────────
function toggleChat() {
    isOpen = !isOpen;
    const box = document.getElementById('aiChatBox');
    const dot = document.getElementById('aiDot');
    if (isOpen) {
        box.classList.add('open');
        dot.style.display = 'none';
        if (currentTab === 'ai') {
            document.getElementById('aiInput').focus();
        } else {
            startMsgPoll();
        }
    } else {
        box.classList.remove('open');
        stopMsgPoll();
    }
}

// ── Switch tabs ────────────────────────────────────────────
function switchTab(tab) {
    currentTab = tab;
    document.getElementById('tabAI').classList.toggle('active', tab === 'ai');
    document.getElementById('tabMsg').classList.toggle('active', tab === 'msg');
    document.getElementById('panelAI').style.display  = tab === 'ai'  ? 'block' : 'none';
    document.getElementById('panelMsg').style.display = tab === 'msg' ? 'flex'  : 'none';

    if (tab === 'msg') {
        document.getElementById('headerIcon').textContent  = '💬';
        document.getElementById('headerTitle').textContent = 'Support Taghazout';
        document.getElementById('msgUnreadDot').style.display = 'none';
        document.getElementById('tabBadge').style.display = 'none';
        if (WIDGET_ME && WIDGET_ADMIN_ID) {
            fetch(`../chat/mark-read.php?from=${WIDGET_ADMIN_ID}`);
        }
        scrollMsgBottom();
        startMsgPoll();
    } else {
        document.getElementById('headerIcon').textContent  = '🤖';
        document.getElementById('headerTitle').textContent = 'Assistant Taghazout';
        stopMsgPoll();
        document.getElementById('aiInput').focus();
    }
}
function msgSend() {
    const input = document.getElementById('msgInput');
    const txt   = input.value.trim();
    if (!txt || !WIDGET_ADMIN_ID) return;

    input.value = '';

    fetch('../chat/send-message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `receiver_id=${WIDGET_ADMIN_ID}&message=${encodeURIComponent(txt)}`
    })
    .then(r => r.json())
    .then(d => {
        if (d.success && d.message) {
            const id  = parseInt(d.message.id);
            const key = String(id);
            if (!isNaN(id) && !msgSeenReplies.includes(key)) {
                msgSeenReplies.push(key);
                appendMsgBubble(d.message);
                if (id > msgLastId) msgLastId = id;
                scrollMsgBottom();
            }
        }
    })
    .catch(() => {});
}
// ── Append bubble ──────────────────────────────────────────
function appendMsgBubble(m) {
    const box    = document.getElementById('msgMessages');
    const isMine = parseInt(m.sender_id) === WIDGET_ME;
    const d      = new Date(m.created_at);
    const timeStr = d.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' });

    const empty = box.querySelector('div[style*="text-align:center"]');
    if (empty) empty.remove();

    const div = document.createElement('div');
    div.className    = `ai-msg ${isMine ? 'user' : 'bot'}`;
    div.dataset.msgId = m.id || '';
    div.innerHTML = `
        <div class="ai-msg-bubble">
            ${escHtml(m.message).replace(/\n/g,'<br>')}
            <div style="font-size:.65rem;opacity:.6;margin-top:.3rem;text-align:${isMine?'right':'left'};">
                ${timeStr}${isMine ? (m.is_read ? ' ✓✓' : ' ✓') : ''}
            </div>
        </div>`;
    box.appendChild(div);
}

// ── Fetch new messages ─────────────────────────────────────
function fetchNewMsgs() {
    if (!WIDGET_ME || !WIDGET_ADMIN_ID) return;
    fetch(`../chat/fetch-messages.php?with=${WIDGET_ADMIN_ID}&last_id=${msgLastId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.messages) return;
            let added = false;
            data.messages.forEach(m => {
                const key = String(m.id);
                if (!document.querySelector(`[data-msg-id="${key}"]`) && !msgSeenReplies.includes(key)) {
                    msgSeenReplies.push(key);
                    appendMsgBubble(m);
                    added = true;
                }
                const id = parseInt(m.id);
                if (!isNaN(id) && id > msgLastId) msgLastId = id;
            });
            if (added) scrollMsgBottom();
        })
        .catch(() => {});
}

function startMsgPoll() {
    if (msgPoll) return;
    fetchNewMsgs();
    msgPoll = setInterval(fetchNewMsgs, 4000);
}
function stopMsgPoll() {
    clearInterval(msgPoll);
    msgPoll = null;
}

// ── AI functions ───────────────────────────────────────────
function sendSuggestion(text) {
    document.getElementById('aiInput').value = text;
    document.getElementById('aiSuggestions').style.display = 'none';
    sendMessage();
}

function addMessage(text, type) {
    const messages = document.getElementById('aiMessages');
    const div = document.createElement('div');
    div.className = `ai-msg ${type}`;
    div.innerHTML = `<div class="ai-msg-bubble">${text.replace(/\n/g,'<br>')}</div>`;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
    return div;
}

function showTyping() {
    const messages = document.getElementById('aiMessages');
    const div = document.createElement('div');
    div.className = 'ai-msg bot ai-typing';
    div.id = 'aiTyping';
    div.innerHTML = `<div class="ai-msg-bubble"><div class="typing-dots"><span></span><span></span><span></span></div></div>`;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
}
function removeTyping() {
    const t = document.getElementById('aiTyping');
    if (t) t.remove();
}

async function sendMessage() {
    const input   = document.getElementById('aiInput');
    const btn     = document.getElementById('aiSendBtn');
    const message = input.value.trim();
    if (!message) return;

    addMessage(message, 'user');
    chatHistory.push({ role: 'user', content: message });
    input.value  = '';
    btn.disabled = true;
    btn.style.opacity = '0.6';
    showTyping();

    try {
        const res = await fetch('http://localhost:5678/webhook/ai-chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message, history: chatHistory.slice(-10) })
        });
        const data = await res.json();
        removeTyping();
        if (data.reply) {
            addMessage(data.reply, 'bot');
            chatHistory.push({ role: 'assistant', content: data.reply });
        } else {
            addMessage("Désolé, une erreur s'est produite. Réessayez ! 😊", 'bot');
        }
    } catch(e) {
        removeTyping();
        addMessage('Erreur de connexion. Vérifiez votre réseau. 🌐', 'bot');
    }
    btn.disabled = false;
    btn.style.opacity = '1';
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Init msgLastId ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#msgMessages .ai-msg[data-msg-id]').forEach(m => {
        const key = m.dataset.msgId;
        msgSeenReplies.push(key);
        const id = parseInt(key);
        if (!isNaN(id) && id > msgLastId) msgLastId = id;
    });
    scrollMsgBottom();
});
</script>