<?php

declare(strict_types=1);

namespace Markc\Pablo\Core;

use Markc\Pablo\Interfaces\ThemeInterface;

abstract class Theme implements ThemeInterface
{
    protected Config $config;
    protected array $data = [];

    public function __construct(Config $config) 
    {
        $this->config = $config;
    }

    abstract public function render(): string;

    public function html(): string 
    {
        return $this->render();
    }
}
