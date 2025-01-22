<?php

declare(strict_types=1);

namespace Markc\Pablo\Interfaces;

interface PluginInterface 
{
    public function execute(): mixed;
    public function __toString(): string;
}
