<?php

declare(strict_types=1);

namespace Markc\Pablo\Plugins\Example;

use Markc\Pablo\Core\Plugin as BasePlugin;

class Plugin extends BasePlugin
{
    public function execute(): mixed 
    {
        $data = [
            'title' => 'Example Plugin',
            'content' => 'This is an example plugin output.'
        ];
        
        return "<h1>{$data['title']}</h1><p>{$data['content']}</p>";
    }
}
