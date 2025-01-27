<?php

declare(strict_types=1);

namespace Markc\Pablo\Plugins\Sshm;

use PDO;
use RuntimeException;

class DatabaseInitializer
{
    private const DB_PATH = __DIR__ . '/sshm.sqlite';
    private ?PDO $db = null;

    public function initialize(): PDO
    {
        try {
            error_log('SSHM Plugin: Connecting to SQLite database');

            // Create database directory if it doesn't exist
            $dbDir = dirname(self::DB_PATH);
            if (!is_dir($dbDir)) {
                if (!mkdir($dbDir, 0755, true)) {
                    throw new RuntimeException("Failed to create database directory: $dbDir");
                }
            }

            // Connect to SQLite database
            $this->db = new PDO('sqlite:' . self::DB_PATH);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            error_log('SSHM Plugin: Connected to database successfully');

            // Check if tables already exist
            $checkTable = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='hosts'");

            if ($checkTable->fetch()) {
                error_log('SSHM Plugin: Database already initialized, skipping schema creation');
                return $this->db;
            }

            // Initialize schema
            $schemaPath = __DIR__ . '/sshm.sql';
            if (!file_exists($schemaPath)) {
                throw new RuntimeException('Schema file not found: ' . $schemaPath);
            }

            $schema = file_get_contents($schemaPath);
            if ($schema === false) {
                throw new RuntimeException('Failed to read schema file');
            }

            error_log('SSHM Plugin: Initializing database schema');
            $this->db->exec($schema);
            error_log('SSHM Plugin: Schema initialized successfully');

            return $this->db;
        } catch (\Exception $e) {
            error_log('SSHM Plugin: Database initialization error: ' . $e->getMessage());
            error_log('SSHM Plugin: Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}
