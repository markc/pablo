<?php

class DataTablesRenderer
{
    public function render(): string
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
        <div class="modal fade" id="hostModal" tabindex="-1" aria-labelledby="hostModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="hostModalLabel">Add Host</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="hostForm">
                            <div class="mb-3">
                                <label for="hostName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="hostName" required>
                            </div>
                            <div class="mb-3">
                                <label for="hostHostname" class="form-label">Hostname</label>
                                <input type="text" class="form-control" id="hostHostname" required>
                            </div>
                            <div class="mb-3">
                                <label for="hostPort" class="form-label">Port</label>
                                <input type="text" class="form-control" id="hostPort" value="22">
                            </div>
                            <div class="mb-3">
                                <label for="hostUsername" class="form-label">Username</label>
                                <input type="text" class="form-control" id="hostUsername" value="root">
                            </div>
                            <div class="mb-3">
                                <label for="hostIdentityFile" class="form-label">Identity File</label>
                                <input type="text" class="form-control" id="hostIdentityFile">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveHost">Save</button>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }

    private function renderKeyModal(): string
    {
        return <<<HTML
        <div class="modal fade" id="keyModal" tabindex="-1" aria-labelledby="keyModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="keyModalLabel">Create SSH Key</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="keyForm">
                            <div class="mb-3">
                                <label for="keyName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="keyName" required>
                            </div>
                            <div class="mb-3">
                                <label for="keyComment" class="form-label">Comment</label>
                                <input type="text" class="form-control" id="keyComment">
                            </div>
                            <div class="mb-3">
                                <label for="keyPassword" class="form-label">Password (optional)</label>
                                <input type="password" class="form-control" id="keyPassword">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveKey">Create</button>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }

    private function renderCopyKeyModal(): string
    {
        return <<<HTML
        <div class="modal fade" id="copyKeyModal" tabindex="-1" aria-labelledby="copyKeyModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="copyKeyModalLabel">Copy Key to Host</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="copyKeyForm">
                            <div class="mb-3">
                                <label for="copyKeySelect" class="form-label">Select Key</label>
                                <select class="form-select" id="copyKeySelect" required></select>
                            </div>
                            <div class="mb-3">
                                <label for="copyHostSelect" class="form-label">Select Host</label>
                                <select class="form-select" id="copyHostSelect" required></select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="copyKey">Copy</button>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }

    private function renderDeleteModal(): string
    {
        return <<<HTML
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this item?
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

// Usage example:
$renderer = new DataTablesRenderer();
echo $renderer->render();
