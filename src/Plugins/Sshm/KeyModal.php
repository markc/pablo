<?php

namespace Markc\Pablo\Plugins\Sshm;

class KeyModal
{
    public function render(): string
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
}
