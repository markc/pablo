<?php

namespace Markc\Pablo\Plugins\Sshm;

class HostModal
{
    public function render(): string
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
}
