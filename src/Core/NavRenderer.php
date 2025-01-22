<?php

declare(strict_types=1);

namespace Markc\Pablo\Core;

class NavRenderer 
{
    private Config $config;

    public function __construct(Config $config) 
    {
        $this->config = $config;
    }

    public function renderPluginNav(array $navData): string 
    {
        if (!isset($navData[0])) {
            return '';
        }

        // Since plugins use a fixed structure [section_name, items_array, icon],
        // we treat it as a dropdown
        return $this->renderDropdown([
            $navData[0],  // Section name (e.g., "Plugins")
            $navData[1],  // Array of plugin items
            $navData[2]   // Section icon
        ]);
    }

    private function renderDropdown(array $section): string 
    {
        $currentPlugin = $this->config->in['plugin'] ?? 'Home';
        $icon = isset($section[2]) ? '<i class="' . $section[2] . '"></i> ' : '';
        
        $submenuItems = array_map(
            function($item) use ($currentPlugin) {
                $isActive = strtolower($currentPlugin) === strtolower($item[0]) ? ' active' : '';
                $itemIcon = isset($item[2]) ? '<i class="' . $item[2] . '"></i> ' : '';
                
                return '
                        <li class="nav-item">
                            <a class="nav-link' . $isActive . '" href="' . $item[1] . '">' . 
                                $itemIcon . $item[0] . 
                            '</a>
                        </li>';
            }, 
            $section[1]
        );

        return '
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#' . $section[0] . 'Submenu" 
                   role="button" aria-expanded="false" aria-controls="' . $section[0] . 'Submenu">' .
                    $icon . $section[0] . ' <i class="bi bi-chevron-right chevron-icon fw ms-auto"></i>
                </a>
                <div class="collapse submenu" id="' . $section[0] . 'Submenu">
                    <ul class="nav flex-column">' . 
                        implode('', $submenuItems) . '
                    </ul>
                </div>
            </li>
        </ul>';
    }

    private function renderSingleNav(array $item): string 
    {
        $currentPlugin = $this->config->in['plugin'] ?? 'Home';
        $isActive = $currentPlugin === $item[1] ? ' active' : '';
        $icon = isset($item[2]) ? '<i class="' . $item[2] . '"></i> ' : '';
        
        return '
        <ul class="nav flex-column">
            <li class="nav-item' . $isActive . '">
                <a class="nav-link" href="' . $item[1] . '">' . $icon . $item[0] . '</a>
            </li>
        </ul>';
    }
}
