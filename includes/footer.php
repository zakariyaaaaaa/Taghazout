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

<!-- AI CHAT WIDGET -->
<div class="ai-chat-widget" id="aiChat">

    <button class="ai-chat-toggle" id="aiChatToggle" onclick="toggleChat()">
        <span class="ai-chat-icon">🤖</span>
        <span class="ai-chat-label">Assistant</span>
        <span class="ai-chat-dot" id="aiDot"></span>
    </button>

    <div class="ai-chat-box" id="aiChatBox">
        <div class="ai-chat-header">
            <div style="display:flex; align-items:center; gap:0.7rem;">
                <div class="ai-avatar">🤖</div>
                <div>
                    <div style="font-weight:700; font-size:0.9rem;">Assistant Taghazout</div>
                    <div style="font-size:0.72rem; opacity:0.7; display:flex; align-items:center; gap:0.3rem;">
                        <span style="width:6px; height:6px; background:#10b981; border-radius:50%; display:inline-block;"></span>
                        En ligne
                    </div>
                </div>
            </div>
            <button onclick="toggleChat()" style="background:rgba(255,255,255,0.15); border:none; border-radius:50%; width:28px; height:28px; color:white; cursor:pointer; font-size:1rem;">✕</button>
        </div>

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
</div>

<style>
.ai-chat-widget {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 9999;
    font-family: 'DM Sans', sans-serif;
}
.ai-chat-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #0ea5e9, #38bdf8);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.75rem 1.4rem;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 8px 30px rgba(14,165,233,0.4);
    transition: all 0.3s ease;
    position: relative;
}
.ai-chat-toggle:hover { transform: translateY(-3px); }
.ai-chat-icon { font-size: 1.2rem; }
.ai-chat-dot {
    position: absolute;
    top: 6px; right: 6px;
    width: 8px; height: 8px;
    background: #10b981;
    border-radius: 50%;
    border: 2px solid white;
    display: none;
}
.ai-chat-box {
    position: absolute;
    bottom: calc(100% + 1rem);
    right: 0;
    width: 360px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    overflow: hidden;
    display: none;
    flex-direction: column;
    border: 1px solid rgba(14,165,233,0.1);
    animation: slideUp 0.3s ease;
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.ai-chat-box.open { display: flex; }
.ai-chat-header {
    background: linear-gradient(135deg, #0a1628, #0ea5e9);
    padding: 1rem 1.2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: white;
}
.ai-avatar {
    width: 38px; height: 38px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}
.ai-chat-messages {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    max-height: 320px;
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
    background: #f8fafc;
}
.ai-msg { display: flex; }
.ai-msg.bot  { justify-content: flex-start; }
.ai-msg.user { justify-content: flex-end; }
.ai-msg-bubble {
    max-width: 80%;
    padding: 0.7rem 1rem;
    border-radius: 16px;
    font-size: 0.85rem;
    line-height: 1.6;
}
.ai-msg.bot .ai-msg-bubble {
    background: white;
    color: #1a2332;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.ai-msg.user .ai-msg-bubble {
    background: linear-gradient(135deg, #0ea5e9, #38bdf8);
    color: white;
    border-bottom-right-radius: 4px;
}
.ai-typing .ai-msg-bubble { background: white; padding: 0.8rem 1rem; }
.typing-dots { display: flex; gap: 4px; align-items: center; }
.typing-dots span {
    width: 6px; height: 6px;
    background: #94a3b8;
    border-radius: 50%;
    animation: typing 1.2s infinite;
}
.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30%            { transform: translateY(-6px); }
}
.ai-suggestions {
    padding: 0.6rem 1rem;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    background: white;
    border-top: 1px solid #f0f4f8;
}
.ai-suggestions button {
    padding: 0.35rem 0.75rem;
    background: #f0f9ff;
    border: 1px solid rgba(14,165,233,0.2);
    border-radius: 50px;
    font-size: 0.75rem;
    color: #0ea5e9;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
}
.ai-suggestions button:hover { background: #0ea5e9; color: white; }
.ai-chat-input {
    display: flex;
    gap: 0.5rem;
    padding: 0.8rem 1rem;
    background: white;
    border-top: 1px solid #f0f4f8;
}
.ai-chat-input input {
    flex: 1;
    padding: 0.6rem 1rem;
    border: 1.5px solid rgba(14,165,233,0.15);
    border-radius: 50px;
    font-size: 0.85rem;
    outline: none;
    color: #1a2332;
    background: #f8fafc;
    transition: border-color 0.2s;
}
.ai-chat-input input:focus { border-color: #0ea5e9; background: white; }
.ai-chat-input button {
    width: 38px; height: 38px;
    background: linear-gradient(135deg, #0ea5e9, #38bdf8);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(14,165,233,0.3);
}
.ai-chat-input button:hover { transform: scale(1.1); }
@media (max-width: 480px) {
    .ai-chat-box { width: calc(100vw - 2rem); right: -0.5rem; }
    .ai-chat-widget { right: 1rem; bottom: 1rem; }
}
</style>

<script>
let isOpen = false;
let chatHistory = [];

function toggleChat() {
    isOpen = !isOpen;
    const box = document.getElementById('aiChatBox');
    const dot = document.getElementById('aiDot');
    if (isOpen) {
        box.classList.add('open');
        dot.style.display = 'none';
        document.getElementById('aiInput').focus();
    } else {
        box.classList.remove('open');
    }
}

function sendSuggestion(text) {
    document.getElementById('aiInput').value = text;
    document.getElementById('aiSuggestions').style.display = 'none';
    sendMessage();
}

function addMessage(text, type) {
    const messages = document.getElementById('aiMessages');
    const div = document.createElement('div');
    div.className = `ai-msg ${type}`;
    div.innerHTML = `<div class="ai-msg-bubble">${text.replace(/\n/g, '<br>')}</div>`;
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
    const typing = document.getElementById('aiTyping');
    if (typing) typing.remove();
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
        // ⚠️ بدل YOUR_N8N_URL بالـ URL ديال n8n ديالك
const res = await fetch('http://localhost:4000/chat/ai-response', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        message,
        history: chatHistory.slice(-10)
    })
});

        const data = await res.json();
        removeTyping();

        if (data.reply) {
            addMessage(data.reply, 'bot');
            chatHistory.push({ role: 'assistant', content: data.reply });
        } else {
            addMessage('Désolé, une erreur s\'est produite. Réessayez ! 😊', 'bot');
        }
    } catch (e) {
        removeTyping();
        addMessage('Erreur de connexion. Vérifiez votre réseau. 🌐', 'bot');
    }

    btn.disabled = false;
    btn.style.opacity = '1';
}
</script>
</body>
</html>
