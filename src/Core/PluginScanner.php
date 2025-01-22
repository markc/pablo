<?php declare(strict_types=1);
// src/Core/PluginScanner.php 20250122 - 20250122
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace Markc\Pablo\Core;

final class PluginScanner
{
    private string $pluginsDir;
    
    public function __construct(?string $baseDir = null) 
    {
        $this->pluginsDir = $baseDir ?? dirname(__DIR__) . '/Plugins';
    }
    
    private function getPluginMeta(string $dir): array
    {
        $metaFile = $dir . '/meta.json';
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
            return [
                'name' => $meta['name'] ?? basename($dir),
                'icon' => $meta['icon'] ?? 'bi bi-box-seam fw',
                'order' => $meta['order'] ?? 999
            ];
        }
        
        return [
            'name' => basename($dir),
            'icon' => 'bi bi-box-seam fw',
            'order' => 999
        ];
    }

    public function scanPlugins(): array 
    {
        $directories = array_filter(glob($this->pluginsDir . '/*'), 'is_dir');
        
        $plugins = array_map(
            function ($dir) {
                $meta = $this->getPluginMeta($dir);
                return [
                    'name' => $meta['name'],
                    'url' => "?plugin=" . strtolower(basename($dir)),
                    'icon' => $meta['icon'],
                    'order' => $meta['order']
                ];
            }, 
            $directories
        );

        // Sort plugins by order
        usort($plugins, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        // Convert to final format
        $pluginList = array_map(
            function ($plugin) {
                return [
                    $plugin['name'],
                    $plugin['url'],
                    $plugin['icon']
                ];
            },
            $plugins
        );
        
        return [
            'Plugins',
            $pluginList,
            'bi bi-collection fw'
        ];
    }
}
