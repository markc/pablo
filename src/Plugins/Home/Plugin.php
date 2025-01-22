<?php

declare(strict_types=1);

namespace Markc\Pablo\Plugins\Home;

// Use absolute namespace path
use \Markc\Pablo\Core\Plugin as BasePlugin;

class Plugin extends BasePlugin 
{
    public function execute(): mixed 
    {
        return "<h1>Welcome to the Pablo Micro PHP Framework</h1>
                <p>This is the default home plugin.</p>";
    }
}
