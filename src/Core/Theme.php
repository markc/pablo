<?php

declare(strict_types=1);
// src/Core/Theme.php 20250122 - 20250122
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace Markc\Pablo\Core;

use Markc\Pablo\Interfaces\ThemeInterface;
use Markc\Pablo\Core\NavRenderer;

abstract class Theme implements ThemeInterface
{
    protected Config $config;
    protected Init $init;

    public function __construct(Config $config, Init $init)
    {
        $this->config = $config;
        $this->init = $init;
    }

    protected function lhsNav(): string
    {
        $navRenderer = new NavRenderer($this->config);
        return $navRenderer->renderPluginNav($this->init->pluginNav);
    }

    protected function rhsNav(): string
    {
        $navRenderer = new NavRenderer($this->config);
        return $navRenderer->renderPluginNav($this->init->nav2);
    }

    abstract public function render(): string;

    abstract public function list(array $data): string;

    abstract public function create(array $inputs): string;

    abstract public function update(array $data): string;

    public function html(): string
    {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if ($isAjax) {
            return $this->config->out['main'];
        }

        return $this->render();
    }
}
