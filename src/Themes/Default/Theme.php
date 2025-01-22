<?php declare(strict_types=1);
// src/Themes/Default/Theme.php 20250122 - 20250122
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace Markc\Pablo\Themes\Default;

use Markc\Pablo\Core\{Theme as BaseTheme, NavRenderer, Init, Config};

class Theme extends BaseTheme
{
    public function __construct(Config $config, Init $init) 
    {
        parent::__construct($config, $init);
    }

    public function render(): string
    {
        $appName = $this->config->cfg['app_name'];
        $mainContent = $this->config->out['main'];
        $lhsNav = $this->lhsNav();
        $rhsNav = $this->rhsNav();

        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
                
        if ($isAjax) {
            return $this->config->out['main'];
        }

        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>{$appName}</title>
                <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
                <link href="assets/css/pablo.css" rel="stylesheet">
            </head>
            <body>
                <nav class="navbar navbar-dark bg-dark fixed-top navbar-height">
                    <div class="container-fluid">
                        <button class="btn btn-dark" id="leftSidebarToggle" type="button">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <a class="navbar-brand" href="/">
                            {$appName}
                        </a>
                        <button class="btn btn-dark" id="rightSidebarToggle" type="button">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                    </div>
                </nav>
                <div class="sidebar left" id="leftSidebar">
                    {$lhsNav}
                </div>
                <div class="sidebar right" id="rightSidebar">
                    {$rhsNav}
                </div>
                <div class="main-content" id="main">
                    <div class="container-fluid">
                        <div class="row">
                            <main class="col-md-12">
                                <div class="content-section" id="content-section">
                                    {$mainContent}
                                </div>
                            </main>
                        </div>
                    </div>
                </div>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
                <script src="assets/js/pablo.js"></script>
            </body>
            </html>
        HTML;
    }
}
