<?php

namespace Markc\Pablo\Plugins\Sshm;

class DataTablesRenderer
{
    public function render(): string
    {
        $html = <<<HTML
        <div class="container">
            <ul class="nav nav-tabs mb-3" id="sshTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="hosts-tab" data-bs-toggle="tab" data-bs-target="#hosts" type="button" role="tab" aria-controls="hosts" aria-selected="true">SSH Hosts</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="keys-tab" data-bs-toggle="tab" data-bs-target="#keys" type="button" role="tab" aria-controls="keys" aria-selected="false">SSH Keys</button>
                </li>
            </ul>
            
            <div class="tab-content" id="sshTabsContent">
                <div class="tab-pane fade show active" id="hosts" role="tabpanel" aria-labelledby="hosts-tab">
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#hostModal">
                            Add Host
                        </button>
                    </div>
                    <table id="hostsTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Hostname</th>
                                <th>Port</th>
                                <th>Username</th>
                                <th>Identity File</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="keys" role="tabpanel" aria-labelledby="keys-tab">
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#keyModal">
                            Create Key
                        </button>
                    </div>
                    <table id="keysTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Public Key</th>
                                <th>Comment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            {$this->renderHostModal()}
            {$this->renderKeyModal()}
            {$this->renderCopyKeyModal()}
            {$this->renderDeleteModal()}

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize DataTables
                const hostsTable = $('#hostsTable').DataTable({
                    ajax: '?api=data&type=hosts',
                    columns: [
                        { data: 'name' },
                        { data: 'hostname' },
                        { data: 'port' },
                        { data: 'username' },
                        { data: 'identity_file' },
                        {
                            data: null,
                            render: function(data, type, row) {
                                return `
                                    <button class="btn btn-sm btn-primary edit-host" data-id="\${row.id}">Edit</button>
                                    <button class="btn btn-sm btn-danger delete-host" data-id="\${row.id}">Delete</button>
                                `;
                            }
                        }
                    ]
                });

                const keysTable = $('#keysTable').DataTable({
                    ajax: '?api=data&type=keys',
                    columns: [
                        { data: 'name' },
                        { data: 'public_key' },
                        { data: 'comment' },
                        {
                            data: null,
                            render: function(data, type, row) {
                                return `
                                    <button class="btn btn-sm btn-primary copy-key" data-id="\${row.id}">Copy</button>
                                    <button class="btn btn-sm btn-danger delete-key" data-id="\${row.id}">Delete</button>
                                `;
                            }
                        }
                    ]
                });
            });
            </script>
        </div>
        HTML;

        return $html;
    }

    private function renderHostModal(): string
    {
        return (new HostModal())->render();
    }

    private function renderKeyModal(): string
    {
        return (new KeyModal())->render();
    }

    private function renderCopyKeyModal(): string
    {
        return (new CopyKeyModal())->render();
    }

    private function renderDeleteModal(): string
    {
        return (new DeleteModal())->render();
    }
}
