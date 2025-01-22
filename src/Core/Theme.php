<?php

declare(strict_types=1);

namespace Markc\Pablo\Core;

use Markc\Pablo\Interfaces\ThemeInterface;
use Markc\Pablo\Core\NavRenderer;

abstract class Theme implements ThemeInterface
{
    protected Config $config;
    protected Init $init;
    protected array $data = [];

    public function __construct(Config $config, Init $init) 
    {
        $this->config = $config;
        $this->init = $init;
    }

    public function lhsNav(): string
    {
        $navRenderer = new NavRenderer($this->config);
        return $navRenderer->renderPluginNav($this->init->pluginNav);
    }

    public function rhsNav(): string
    {
        $navRenderer = new NavRenderer($this->config);
        return $navRenderer->renderPluginNav($this->init->nav2);
    }

    abstract public function render(): string;

    public function html(): string 
    {
        return $this->render();
    }
}
