<?php

declare(strict_types=1);

namespace Markc\Pablo\Plugins\Sshm;

use PDO;
use RuntimeException;

class HostManager
{
    private PDO $db;
    private string $configDir;

    public function __construct(PDO $db, string $configDir)
    {
        $this->db = $db;
        $this->configDir = $configDir;
    }

    public function createHost(array $data): void
    {
        // Insert into database
        $stmt = $this->db->prepare('INSERT INTO hosts (name, hostname, port, username, identity_file) VALUES (:name, :hostname, :port, :username, :identity_file)');
        $stmt->execute([
            ':name' => $data['name'],
            ':hostname' => $data['hostname'],
            ':port' => $data['port'] ?? '22',
            ':username' => $data['username'] ?? 'root',
            ':identity_file' => $data['identity_file'] ?? null
        ]);

        // Create config file
        $config = "Host {$data['name']}\n" .
            "    Hostname {$data['hostname']}\n" .
            "    Port {$data['port']}\n" .
            "    User {$data['username']}\n";

        if (!empty($data['identity_file'])) {
            $config .= "    IdentityFile {$data['identity_file']}\n";
        }

        file_put_contents("{$this->configDir}/{$data['name']}", $config);
        chmod("{$this->configDir}/{$data['name']}", 0600);
    }

    public function editHost(int $id, array $data): void
    {
        // Get old name for file operations
        $stmt = $this->db->prepare('SELECT name FROM hosts WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $oldName = $stmt->fetchColumn();

        // Update database
        $stmt = $this->db->prepare('UPDATE hosts SET name = :name, hostname = :hostname, port = :port, username = :username, identity_file = :identity_file WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':hostname' => $data['hostname'],
            ':port' => $data['port'] ?? '22',
            ':username' => $data['username'] ?? 'root',
            ':identity_file' => $data['identity_file'] ?? null
        ]);

        // Update config file
        $oldFile = "{$this->configDir}/$oldName";
        $newFile = "{$this->configDir}/{$data['name']}";

        if (file_exists($oldFile) && $oldName !== $data['name']) {
            unlink($oldFile);
        }

        $config = "Host {$data['name']}\n" .
            "    Hostname {$data['hostname']}\n" .
            "    Port {$data['port']}\n" .
            "    User {$data['username']}\n";

        if (!empty($data['identity_file'])) {
            $config .= "    IdentityFile {$data['identity_file']}\n";
        }

        file_put_contents($newFile, $config);
        chmod($newFile, 0600);
    }

    public function deleteHost(int $id): void
    {
        // Get host name before deletion
        $stmt = $this->db->prepare('SELECT name FROM hosts WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $name = $stmt->fetchColumn();

        // Delete from database
        $stmt = $this->db->prepare('DELETE FROM hosts WHERE id = :id');
        $stmt->execute([':id' => $id]);

        // Delete config file
        $configFile = "{$this->configDir}/$name";
        if (file_exists($configFile)) {
            unlink($configFile);
        }
    }
}
