<?php

declare(strict_types=1);

namespace Markc\Pablo\Core;

use Markc\Pablo\Exceptions\{
    PluginNotFoundException,
    InvalidPluginException
};
use Markc\Pablo\Interfaces\PluginInterface;

final class Init
{
    private ?Theme $theme = null;
    private Config $config;
    private array $pluginRegistry = [];
    private PluginScanner $scanner;
    public readonly array $pluginNav;

    public array $nav2 = [
        'Remotes',        
        [
            ['local',     '?o=remote&r=local',    'bi bi-globe fw'],
            ['mgo',       '?o=remote&r=mgo',      'bi bi-globe fw'],
        ], 
        'bi bi-list fw'
    ];
    
    public function __construct(Config $config) 
    {
        $this->validateEnvironment();
        $this->initializeSession();
        $this->setupConfig($config);
        $this->initializePluginNav();
        $this->initializeTheme();
        $this->executePlugin();
    }

    private function initializePluginNav(): void 
    {
        $this->scanner = new PluginScanner();
        $this->pluginNav = $this->scanner->scanPlugins();
    }

    private function initializeTheme(): void 
    {
        $themeType = $this->config->in['theme'] ?? 'Default';
        $themeClass = "Markc\\Pablo\\Themes\\{$themeType}\\Theme";
        
        $this->theme = class_exists($themeClass) 
            ? new $themeClass($this->config, $this)  // Pass $this to Theme
            : throw new \RuntimeException("Theme not found: {$themeClass}");
    }

    private function validateEnvironment(): void 
    {
        if (PHP_VERSION_ID < 80400) {
            throw new \RuntimeException('PHP 8.4 or higher is required');
        }
    }

    private function initializeSession(): void 
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start(
                [
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict'
                ]
            );
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->logDebugInfo();
    }

    private function setupConfig(Config $config): void 
    {
        $this->config = $config;
        $this->config->cfg['host'] ??= $_SERVER['HTTP_HOST'];
        $this->config->cfg['base_path'] = ROOT;
        $this->config->cfg['base_url'] = str_replace('index.php', '', $_SERVER['PHP_SELF']);
        $this->config->sanitizeInput();
    }

    private function executePlugin(): void 
    {
        $pluginName = ucfirst(strtolower($this->config->in['plugin'] ?? 'Home'));
 
        $pluginClass = "Markc\\Pablo\\Plugins\\{$pluginName}\\Plugin";
        if (!class_exists($pluginClass)) {
            throw new PluginNotFoundException("Plugin not found: {$pluginClass}");
        }

        if ($this->config->in['api'] ?? false) {
            $this->validateApiRequest();
        }

        $plugin = new $pluginClass($this->theme);
        if (!$plugin instanceof PluginInterface) {
            throw new InvalidPluginException("Invalid plugin implementation: {$pluginClass}");
        }

        $this->config->out['main'] = (string)$plugin;
        
        if (empty($this->config->in['partial'])) {
            $this->processThemeOutput();
        }
    }
    
    private function validateApiRequest(): void 
    {
        if (!isset($_SERVER['HTTP_X_CSRF_TOKEN'])  
            || !hash_equals($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN'])
        ) {
            throw new \RuntimeException('Invalid CSRF token');
        }
    }

    private function processThemeOutput(): void 
    {
        foreach ($this->config->out as $key => $value) {
            if (method_exists($this->theme, $key)) {
                $this->config->out[$key] = $this->theme->$key();
            }
        }
    }

    public function __toString(): string 
    {
        $format = $this->config->in['format'] ?? 'html';
        
        return match($format) {
            'text' => strip_tags($this->config->out['main']),
            'json' => $this->renderJson($this->config->out['main']),
            'partial' => $this->renderPartial($this->config->in['section'] ?? ''),
            default => $this->theme->html()
        };
    }

    private function renderJson(mixed $data): string 
    {
        header('Content-Type: application/json');
        return json_encode(
            $data, 
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );
    }

    private function renderPartial(string $section): string 
    {
        if (empty($section) || !isset($this->config->out[$section])) {
            throw new \RuntimeException('Invalid partial section requested');
        }

        header('Content-Type: application/json');
        return $this->renderJson($this->config->out[$section]);
    }

    private function logDebugInfo(): void 
    {
        if ($_ENV['APP_DEBUG'] ?? false) {
            error_log(
                sprintf(
                    "Request: %s\nGET: %s\nPOST: %s\nSESSION: %s",
                    $_SERVER['REQUEST_URI'],
                    var_export($_GET, true),
                    var_export($_POST, true),
                    var_export($_SESSION, true)
                )
            );
        }
    }

    public function __destruct() 
    {
        if ($_ENV['APP_DEBUG'] ?? false) {
            error_log(
                sprintf(
                    "Request completed: %s %s %.4f seconds",
                    $_SERVER['REQUEST_URI'],
                    $_SERVER['REMOTE_ADDR'],
                    microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
                )
            );
        }
    }
}
