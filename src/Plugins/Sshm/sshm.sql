-- SSH Hosts table
CREATE TABLE IF NOT EXISTS hosts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    hostname TEXT NOT NULL,
    port TEXT DEFAULT '22',
    username TEXT DEFAULT 'root',
    identity_file TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- SSH Keys table
CREATE TABLE IF NOT EXISTS keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    public_key TEXT NOT NULL,
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_hosts_name ON hosts(name);
CREATE INDEX IF NOT EXISTS idx_keys_name ON keys(name);
