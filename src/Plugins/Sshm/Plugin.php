<?php

declare(strict_types=1);

namespace Markc\Pablo\Plugins\Sshm;

use Markc\Pablo\Core\Plugin as BasePlugin;

class Plugin extends BasePlugin
{
    private string $sshDir;
    private string $configDir;
    private \PDO $db;

    public function __construct(\Markc\Pablo\Core\Theme $theme)
    {
        parent::__construct($theme);
        error_log('SSHM Plugin: Starting initialization');

        // Set SSH directories
        $home = getenv('HOME') ?: posix_getpwuid(posix_geteuid())['dir'];
        $this->sshDir = $home . '/.ssh';
        $this->configDir = $home . '/.ssh/config.d';

        // Initialize database
        $dbInitializer = new DatabaseInitializer();
        $this->db = $dbInitializer->initialize();

        // Initialize SSH structure
        $sshInitializer = new SshStructureInitializer($this->sshDir, $this->configDir);
        $sshInitializer->initialize();

        error_log('SSHM Plugin: Initialization complete');
    }

    public function execute(): mixed
    {
        $hostManager = new HostManager($this->db, $this->configDir);
        $keyManager = new KeyManager($this->db, $this->sshDir);
        $apiHandler = new ApiHandler($this->db, $hostManager, $keyManager);

        $apiHandler->handleRequest();

        // Return main page with datatables
        return $this->renderDataTables();
    }

    private function renderDataTables(): string
    {
        $renderer = new DataTablesRenderer();
        return $renderer->render();
    }
}
