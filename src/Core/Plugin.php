<?php declare(strict_types=1);
// src/Core/Plugin.php 20250122 - 20250122
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace Markc\Pablo\Core;

use Markc\Pablo\Interfaces\PluginInterface;

abstract class Plugin implements PluginInterface
{
    protected Theme $theme;

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
