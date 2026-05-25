<?php include __DIR__ . '/../includes/header.php'; requireAgency(); ?>
<div class="page-container">
<h1>Chat with Travellers</h1>
<div class="chat-layout" id="chatApp">
    <div class="chat-sidebar">
        <div class="conversation-list" id="conversationList">
            <p class="text-muted">Loading conversations...</p>
        </div>
    </div>
    <div class="chat-main" id="chatMain">
        <div class="chat-empty">
            <p>Select a conversation to view messages</p>
        </div>
    </div>
</div>
</div>

<script>
const API_BASE = '<?php echo BASE_URL; ?>/api/index.php/chat';
let currentOther = null;
let currentOtherName = '';
let pollTimer = null;

function api(url, options = {}) {
    return fetch(url, options).then(r => r.json());
}

async function loadConversations() {
    const res = await api(API_BASE + '/conversations');
    const list = document.getElementById('conversationList');
    if (!res.success || !res.data.length) {
        list.innerHTML = '<p class="text-muted" style="padding:1rem;">No conversations yet. Travellers will appear here when they message you.</p>';
        return;
    }
    list.innerHTML = res.data.map(c => `
        <div class="conversation-item ${currentOther === c.UserID ? 'active' : ''}"
             onclick="openChat(${c.UserID}, '${escapeHtml(c.DisplayName)}')">
            <div class="conv-name">${escapeHtml(c.DisplayName)}</div>
            <div class="conv-type">${c.UserType}</div>
            <div class="conv-preview">${escapeHtml((c.LastMessage || '').substring(0, 50))}</div>
            ${c.UnreadCount > 0 ? `<span class="conv-badge">${c.UnreadCount}</span>` : ''}
        </div>
    `).join('');
}

async function openChat(otherId, name) {
    currentOther = otherId;
    currentOtherName = name;
    document.getElementById('chatMain').innerHTML = `
        <div class="chat-header">
            <h3>${escapeHtml(name)}</h3>
        </div>
        <div class="chat-messages" id="chatMessages">
            <p class="text-muted">Loading messages...</p>
        </div>
        <div class="chat-input">
            <input type="text" id="messageInput" placeholder="Type a message..." onkeypress="if(event.key==='Enter') sendMessage()">
            <button class="btn btn-primary" onclick="sendMessage()">Send</button>
        </div>
    `;
    document.querySelectorAll('.conversation-item').forEach(el => el.classList.remove('active'));
    loadMessages();
    startPolling();
}

async function loadMessages() {
    if (!currentOther) return;
    const res = await api(API_BASE + '/messages/' + currentOther + '?limit=200');
    const box = document.getElementById('chatMessages');
    if (!box) return;
    if (!res.success || !res.data.length) {
        box.innerHTML = '<p class="text-muted" style="text-align:center;padding:2rem;">No messages yet.</p>';
        return;
    }
    box.innerHTML = res.data.map(m => `
        <div class="message ${m.IsMine ? 'message-mine' : 'message-theirs'}">
            <div class="message-text">${escapeHtml(m.Message)}</div>
            <div class="message-time">${formatTime(m.SentAt)}</div>
        </div>
    `).join('');
    box.scrollTop = box.scrollHeight;
}

async function sendMessage() {
    const input = document.getElementById('messageInput');
    const msg = input.value.trim();
    if (!msg || !currentOther) return;
    input.value = '';
    const res = await api(API_BASE + '/send', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({receiver_id: currentOther, message: msg})
    });
    if (res.success) {
        await loadMessages();
        loadConversations();
    }
}

function startPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(async () => {
        if (!currentOther) return;
        await loadMessages();
        await loadConversations();
    }, 3000);
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatTime(dt) {
    if (!dt) return '';
    const d = new Date(dt.replace(' ', 'T') + 'Z');
    const now = new Date();
    const diff = now - d;
    if (diff < 86400000) return d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    return d.toLocaleDateString([], {month:'short',day:'numeric'}) + ' ' + d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
}

document.addEventListener('DOMContentLoaded', () => {
    loadConversations();
    startPolling();
});
</script>

<style>
.chat-layout { display: flex; height: calc(100vh - 200px); min-height: 500px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
.chat-sidebar { width: 320px; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; background: #f8fafc; }
.conversation-list { flex: 1; overflow-y: auto; padding: 0.5rem; }
.conversation-item { padding: 0.8rem; border-radius: 8px; cursor: pointer; margin-bottom: 0.25rem; position: relative; }
.conversation-item:hover, .conversation-item.active { background: #e2e8f0; }
.conv-name { font-weight: 600; font-size: 0.95rem; }
.conv-type { font-size: 0.75rem; color: #64748b; }
.conv-preview { font-size: 0.8rem; color: #94a3b8; margin-top: 0.15rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.conv-badge { position: absolute; top: 0.8rem; right: 0.8rem; background: #ef4444; color: #fff; border-radius: 10px; padding: 0.1rem 0.45rem; font-size: 0.7rem; font-weight: 600; }
.chat-main { flex: 1; display: flex; flex-direction: column; }
.chat-empty { flex: 1; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 1rem; }
.chat-header { padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0; background: #fff; }
.chat-header h3 { margin: 0; font-size: 1.1rem; }
.chat-messages { flex: 1; overflow-y: auto; padding: 1rem 1.5rem; display: flex; flex-direction: column; gap: 0.6rem; }
.message { max-width: 75%; padding: 0.6rem 0.9rem; border-radius: 12px; }
.message-mine { align-self: flex-end; background: #4f46e5; color: #fff; border-bottom-right-radius: 4px; }
.message-theirs { align-self: flex-start; background: #f1f5f9; color: #1e293b; border-bottom-left-radius: 4px; }
.message-text { font-size: 0.95rem; line-height: 1.4; word-break: break-word; }
.message-time { font-size: 0.7rem; opacity: 0.7; margin-top: 0.2rem; text-align: right; }
.message-theirs .message-time { text-align: left; }
.chat-input { padding: 1rem 1.5rem; border-top: 1px solid #e2e8f0; display: flex; gap: 0.5rem; background: #fff; }
.chat-input input { flex: 1; padding: 0.7rem 0.9rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; }
.chat-input button { padding: 0.7rem 1.2rem; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
