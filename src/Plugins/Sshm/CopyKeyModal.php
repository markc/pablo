<?php

namespace Plugins\Sshm;

class CopyKeyModal
{
    public function render(): string
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
}
