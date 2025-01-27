<?php

declare(strict_types=1);

namespace Markc\Pablo\Plugins\Sshm;

use PDO;
use RuntimeException;

class ApiHandler
{
    private PDO $db;
    private HostManager $hostManager;
    private KeyManager $keyManager;

    public function __construct(PDO $db, HostManager $hostManager, KeyManager $keyManager)
    {
        $this->db = $db;
        $this->hostManager = $hostManager;
        $this->keyManager = $keyManager;
    }

    public function handleRequest(): void
    {
        if (isset($_GET['api'])) {
            switch ($_GET['api']) {
                case 'data':
                    $this->apiData();
                    break;
                case 'delete_host':
                    $this->hostManager->deleteHost((int) $_GET['id']);
                    break;
                case 'edit_host':
                    $this->hostManager->editHost((int) $_GET['id'], json_decode(file_get_contents('php://input'), true));
                    break;
                case 'create_host':
                    $this->hostManager->createHost(json_decode(file_get_contents('php://input'), true));
                    break;
                case 'delete_key':
                    $this->keyManager->deleteKey((int) $_GET['id']);
                    break;
                case 'create_key':
                    $this->keyManager->createKey(json_decode(file_get_contents('php://input'), true));
                    break;
                case 'copy_key':
                    $this->copyKey((int) $_GET['key_id'], (int) $_GET['host_id']);
                    break;
                case 'import_hosts':
                    $this->importHosts();
                    break;
                default:
                    throw new RuntimeException('Invalid API request');
            }
        }
    }

    private function apiData(): void
    {
        // Handle API data requests
    }

    private function copyKey(int $keyId, int $hostId): void
    {
        // Handle key copying logic
    }

    private function importHosts(): void
    {
        // Handle host import logic
    }
}
