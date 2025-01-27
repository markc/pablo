<?php

declare(strict_types=1);

namespace Markc\Pablo\Plugins\Sshm;

use PDO;
use RuntimeException;

class KeyManager
{
    private PDO $db;
    private string $sshDir;

    public function __construct(PDO $db, string $sshDir)
    {
        $this->db = $db;
        $this->sshDir = $sshDir;
    }

    public function createKey(array $data): void
    {
        $name = $data['name'];
        $comment = $data['comment'] ?? gethostname() . '@lan';
        $password = $data['password'] ?? '';

        $keyFile = "{$this->sshDir}/$name";

        if (file_exists($keyFile)) {
            throw new RuntimeException("SSH Key '~/.ssh/$name' already exists");
        }

        // Generate key
        $cmd = "ssh-keygen -o -a 100 -t ed25519 -f $keyFile -C \"$comment\"";
        if (!empty($password)) {
            $cmd .= " -N \"$password\"";
        } else {
            $cmd .= " -N \"\"";
        }

        exec($cmd, $output, $result);
        if ($result !== 0) {
            throw new RuntimeException("Error generating SSH key");
        }

        // Read public key
        $publicKey = file_get_contents("$keyFile.pub");
        if ($publicKey === false) {
            throw new RuntimeException("Error reading public key");
        }

        // Store in database
        $stmt = $this->db->prepare('INSERT INTO keys (name, public_key, comment) VALUES (:name, :public_key, :comment)');
        $stmt->execute([
            ':name' => $name,
            ':public_key' => $publicKey,
            ':comment' => $comment
        ]);
    }

    public function deleteKey(int $id): void
    {
        // Get key name before deletion
        $stmt = $this->db->prepare('SELECT name FROM keys WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $name = $stmt->fetchColumn();

        // Delete key files
        $keyFile = "{$this->sshDir}/$name";
        if (file_exists($keyFile)) {
            unlink($keyFile);
            unlink("$keyFile.pub");
        }

        // Delete from database
        $stmt = $this->db->prepare('DELETE FROM keys WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
