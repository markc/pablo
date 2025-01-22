<?php declare(strict_types=1);
// src/Interfaces/ThemeInterface.php 20250122 - 20250122
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace Markc\Pablo\Interfaces;

interface ThemeInterface
{
    public function render(): string;
    public function html(): string;
}
