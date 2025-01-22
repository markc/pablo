<?php

declare(strict_types=1);

namespace Markc\Pablo\Interfaces;

interface ThemeInterface 
{
    public function render(): string;
    public function html(): string;
}
