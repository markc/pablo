<?php

declare(strict_types=1);

namespace Markc\Pablo\Core;

use Markc\Pablo\Interfaces\ConfigInterface;

class Config implements ConfigInterface 
{
    public function __construct(
        public array $cfg = [],
        public array $in = [],
        public array $out = []
    ) {}

    public function getConfig(): array 
    {
        return $this->cfg;
    }

    public function sanitizeInput(): void
    {
        $this->in = array_map(
            fn($value) => is_string($value) ? htmlspecialchars($value, ENT_QUOTES) : $value,
            $this->in
        );
    }
}
