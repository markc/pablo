-- src/Plugins/Vhosts/vhosts.sql 20250127
-- Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

CREATE TABLE IF NOT EXISTS vhosts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  active INTEGER NOT NULL DEFAULT 0,
  aid INTEGER NOT NULL DEFAULT 0,
  aliases INTEGER NOT NULL DEFAULT 10,
  diskquota INTEGER NOT NULL DEFAULT 1000000000,
  domain TEXT NOT NULL,
  gid INTEGER NOT NULL DEFAULT 1000,
  mailboxes INTEGER NOT NULL DEFAULT 1,
  mailquota INTEGER NOT NULL DEFAULT 500000000,
  uid INTEGER NOT NULL DEFAULT 1000,
  uname TEXT NOT NULL DEFAULT '',
  created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_domain ON vhosts(domain);

CREATE VIEW IF NOT EXISTS vhosts_view AS
SELECT 
  v.*,
  (SELECT COUNT(*) FROM aliases WHERE domain = v.domain) as num_aliases,
  (SELECT COUNT(*) FROM mailboxes WHERE domain = v.domain) as num_mailboxes,
  (SELECT SUM(quota) FROM mailboxes WHERE domain = v.domain) as size_mpath,
  (SELECT SUM(size) FROM files WHERE domain = v.domain) as size_upath
FROM vhosts v;

-- Create related tables for the view to work
CREATE TABLE IF NOT EXISTS aliases (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  domain TEXT NOT NULL,
  FOREIGN KEY (domain) REFERENCES vhosts(domain)
);

CREATE TABLE IF NOT EXISTS mailboxes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  domain TEXT NOT NULL,
  quota INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (domain) REFERENCES vhosts(domain)
);

CREATE TABLE IF NOT EXISTS files (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  domain TEXT NOT NULL,
  size INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (domain) REFERENCES vhosts(domain)
);
