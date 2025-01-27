<?php

declare(strict_types=1);

namespace Markc\Pablo\Themes\Default;

use Markc\Pablo\Core\Theme;
use Markc\Pablo\Core\Config;
use Markc\Pablo\Core\Init;
use PDO;

class Vhosts extends Theme
{
    protected Config $config;
    protected Init $init;
    protected ?PDO $db = null;

    public function __construct(Config $config, Init $init)
    {
        parent::__construct($config, $init);
        $this->config = $config;
        $this->init = $init;
        $this->initializeDatabase();
    }

    private const DB_PATH = __DIR__ . '/../../Plugins/Vhosts/vhosts.sqlite';

    private function initializeDatabase(): void
    {
        try {
            error_log('Vhosts Theme: Connecting to SQLite database');

            // Create database directory if it doesn't exist
            $dbDir = dirname(self::DB_PATH);
            if (!is_dir($dbDir)) {
                if (!mkdir($dbDir, 0755, true)) {
                    throw new \RuntimeException("Failed to create database directory: $dbDir");
                }
            }

            // Connect to SQLite database
            $this->db = new PDO('sqlite:' . self::DB_PATH);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            error_log('Vhosts Theme: Connected to database successfully');

            // Check if tables exist
            $checkTable = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='vhosts'");
            if (!$checkTable->fetch()) {
                error_log('Vhosts Theme: Initializing database schema');

                // Initialize schema
                $schemaPath = __DIR__ . '/../../Plugins/Vhosts/vhosts.sql';
                if (!file_exists($schemaPath)) {
                    throw new \RuntimeException('Schema file not found: ' . $schemaPath);
                }

                $schema = file_get_contents($schemaPath);
                if ($schema === false) {
                    throw new \RuntimeException('Failed to read schema file');
                }

                // Split schema into individual statements
                $statements = array_filter(
                    array_map('trim', explode(';', $schema)),
                    'strlen'
                );

                foreach ($statements as $statement) {
                    $this->db->exec($statement);
                }
                error_log('Vhosts Theme: Schema initialized successfully');
            } else {
                error_log('Vhosts Theme: Database already initialized');
            }
        } catch (\Exception $e) {
            error_log('Vhosts Theme: Database initialization error: ' . $e->getMessage());
            error_log('Vhosts Theme: Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    public function render(): string
    {
        $action = $_GET['action'] ?? 'list';
        $content = match ($action) {
            'create' => $this->create([]),
            'update' => $this->update([]),
            default => $this->list([])
        };

        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Vhosts - Pablo</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
            <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
            <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
        </head>
        <body>
            <div class="container-fluid py-4">
                ' . $content . '
            </div>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>';
    }

    public function create(array $inputs): string
    {
        return $this->modal([
            'title' => 'Create New Vhost',
            'content' => '
                <form method="post" action="?plugin=vhosts&action=create">
                    <div class="mb-3">
                        <label for="domain" class="form-label">Vhost</label>
                        <input type="text" class="form-control" id="domain" name="domain" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12 col-sm-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="cms" id="cms" checked>
                                <label class="form-check-label" for="cms">WordPress</label>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="ssl" id="ssl">
                                <label class="form-check-label" for="ssl">Self Signed SSL</label>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="ip" class="form-label">IP (optional)</label>
                            <input type="text" class="form-control" id="ip" name="ip">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="uuser" class="form-label">Custom User</label>
                            <input type="text" class="form-control" id="uuser" name="uuser">
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>'
        ]);
    }

    public function update(array $data): string
    {
        $domain = htmlspecialchars($data['domain'] ?? '');
        $active = (bool)($data['active'] ?? false);
        $aliases = (int)($data['aliases'] ?? 10);
        $mailboxes = (int)($data['mailboxes'] ?? 1);
        $mailquota = (int)(($data['mailquota'] ?? 500000000) / 1000000);
        $diskquota = (int)(($data['diskquota'] ?? 1000000000) / 1000000);

        $deleteModal = $this->modal([
            'id' => 'deleteModal',
            'title' => 'Delete Vhost',
            'content' => '
                <form method="post" action="?plugin=vhosts&action=delete&id=' . ($data['id'] ?? '') . '">
                    <p class="text-center">Are you sure you want to delete this vhost?<br>
                    <strong>' . $domain . '</strong></p>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>'
        ]);

        return '
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <a href="?plugin=vhosts" class="text-decoration-none">
                        <i class="bi bi-arrow-left me-2"></i>Vhosts
                    </a>
                </h2>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal">
                    <i class="bi bi-trash me-1"></i>Delete
                </button>
            </div>

            <form method="post" action="?plugin=vhosts&action=update&id=' . ($data['id'] ?? '') . '">
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="domain" class="form-label">Domain</label>
                        <input type="text" class="form-control" value="' . $domain . '" disabled>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="aliases" class="form-label">Max Aliases</label>
                        <input type="number" class="form-control" name="aliases" id="aliases" value="' . $aliases . '">
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="mailboxes" class="form-label">Max Mailboxes</label>
                        <input type="number" class="form-control" name="mailboxes" id="mailboxes" value="' . $mailboxes . '">
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="mailquota" class="form-label">Mail Quota (MB)</label>
                        <input type="number" class="form-control" name="mailquota" id="mailquota" value="' . $mailquota . '">
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="diskquota" class="form-label">Disk Quota (MB)</label>
                        <input type="number" class="form-control" name="diskquota" id="diskquota" value="' . $diskquota . '">
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12 col-sm-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="active" id="active"' . ($active ? ' checked' : '') . '>
                            <label class="form-check-label" for="active">Active</label>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 text-end">
                        <a href="?plugin=vhosts" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>' . $deleteModal;
    }

    public function list(array $data): string
    {
        return '
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="bi bi-globe me-2"></i>Vhosts
                </h2>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Vhost
                </button>
            </div>

            <div class="table-responsive">
                <table id="vhosts" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th class="text-end">Alias</th>
                            <th></th>
                            <th class="text-end">Max</th>
                            <th class="text-end">Mbox</th>
                            <th></th>
                            <th class="text-end">Max</th>
                            <th class="text-end">Mail</th>
                            <th></th>
                            <th class="text-end">Quota</th>
                            <th class="text-end">Disk</th>
                            <th></th>
                            <th class="text-end">Quota</th>
                            <th class="text-center">Active</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            new DataTable("#vhosts", {
                processing: true,
                serverSide: true,
                ajax: "?plugin=vhosts&action=list&format=json",
                order: [[15, "desc"]],
                columnDefs: [
                    {targets: 0,  className: "text-truncate", width: "25%"},
                    {targets: 1,  className: "text-end", width: "3rem"},
                    {targets: 2,  className: "text-center", width: "0.5rem", orderable: false},
                    {targets: 3,  className: "text-end", width: "3rem"},
                    {targets: 4,  className: "text-end", width: "3rem"},
                    {targets: 5,  className: "text-center", width: "0.5rem", orderable: false},
                    {targets: 6,  className: "text-end", width: "3rem"},
                    {targets: 7,  className: "text-end", width: "4rem"},
                    {targets: 8,  className: "text-center", width: "0.5rem", orderable: false},
                    {targets: 9,  className: "text-end", width: "4rem"},
                    {targets: 10, className: "text-end", width: "4rem"},
                    {targets: 11, className: "text-center", width: "0.5rem", orderable: false},
                    {targets: 12, className: "text-end", width: "4rem"},
                    {targets: 13, className: "text-center", width: "1rem", orderable: false},
                    {targets: 14, visible: false, orderable: true},
                    {targets: 15, visible: false, orderable: true}
                ]
            });
        });
        </script>' . $this->create([]);
    }

    protected function modal(array $params): string
    {
        $id = $params['id'] ?? 'createModal';
        $title = $params['title'] ?? '';
        $content = $params['content'] ?? '';

        return '
        <div class="modal fade" id="' . $id . '" tabindex="-1" aria-labelledby="' . $id . 'Label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="' . $id . 'Label">' . $title . '</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        ' . $content . '
                    </div>
                </div>
            </div>
        </div>';
    }
}
