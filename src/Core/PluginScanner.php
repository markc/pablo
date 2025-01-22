<?php

declare(strict_types=1);

namespace Markc\Pablo\Core;

final class PluginScanner 
{
    private string $pluginsDir;
    
    public function __construct(?string $baseDir = null) 
    {
        $this->pluginsDir = $baseDir ?? dirname(__DIR__) . '/Plugins';
    }
    
    public function scanPlugins(): array 
    {
        $directories = array_filter(glob($this->pluginsDir . '/*'), 'is_dir');
        
        return [
            'Plugins',
            array_map(
                function($dir) {
                    $pluginName = basename($dir);
                    return [
                        $pluginName,
                        "?plugin=" . strtolower($pluginName),
                        'bi bi-box-seam fw'
                    ];
                }, 
                $directories
            ),
            'bi bi-collection fw'
        ];
    }
}
