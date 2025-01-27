<?php

declare(strict_types=1);

namespace Markc\Pablo\Plugins\Sshm;

use Markc\Pablo\Core\Plugin as BasePlugin;

class Plugin extends BasePlugin
{
    private string $sshDir;
    private string $configDir;
    private \PDO $db;

    public function __construct($theme)
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
        $html = <<<HTML
        <div class="container">
            {$this->renderHostModal()}
            {$this->renderKeyModal()}
            {$this->renderCopyKeyModal()}
            {$this->renderDeleteModal()}
        </div>
        HTML;

        return $html;
    }

    private function renderHostModal(): string
    {
        return <<<HTML
        <div class="modal fade" id="hostModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Host Configuration</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Host form fields will be dynamically populated -->
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }

    private function renderKeyModal(): string
    {
        return <<<HTML
        <div class="modal fade" id="keyModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">SSH Key Management</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Key form fields will be dynamically populated -->
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }

    private function renderCopyKeyModal(): string
    {
        return <<<HTML
        <div class="modal fade" id="copyKeyModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Copy SSH Key</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Copy key content will be dynamically populated -->
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }

    private function renderDeleteModal(): string
    {
        return <<<HTML
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this item?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
