<?php

declare(strict_types=1);

namespace Markc\Pablo\Core;

use Markc\Pablo\Interfaces\PluginInterface;

abstract class Plugin implements PluginInterface
{
    protected Theme $theme;
    protected array $data = [];

    public function __construct(Theme $theme) 
    {
        $this->theme = $theme;
    }

    abstract public function execute(): mixed;

    public function __toString(): string 
    {
        return (string)$this->execute();
    }
}
