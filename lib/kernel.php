<?php

declare(strict_types=1);

namespace Framework\Core;

use Framework\Interfaces\{
    ConfigInterface,
    ThemeInterface,
    PluginInterface
};
use Framework\Exceptions\{
    PluginNotFoundException,
    InvalidPluginException
};

/**
 * Framework configuration class
 */
readonly class Config implements ConfigInterface 
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
}

/**
 * Base Theme class
 */
abstract class Theme implements ThemeInterface 
{
    protected Config $config;
    protected array $data = [];

    public function __construct(Config $config) 
    {
        $this->config = $config;
    }

    abstract public function render(): string;

    public function html(): string 
    {
        return $this->render();
    }
}

/**
 * Abstract Plugin base class
 */
abstract class Plugin implements PluginInterface 
{
    protected Theme $theme;
    protected array $data = [];

    public function __construct(Theme $theme) 
    {
        $this->theme = $theme;
    }

    abstract public function execute(): mixed;

    public function __toString(): string 
    {
        return (string)$this->execute();
    }
}

/**
 * Main framework initialization class
 */
final class Init 
{
    private ?Theme $theme = null;
    private Config $config;
    private array $pluginRegistry = [];

    public function __construct(Config $config) 
    {
        $this->validateEnvironment();
        $this->initializeSession();
        $this->setupConfig($config);
        $this->initializeTheme();
        $this->executePlugin();
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
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict'
            ]);
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
        $this->config->cfg['base_path'] = str_replace('index.php', '', $_SERVER['PHP_SELF']);
        
        // Sanitize input data
        $this->config->in = array_map(
            fn($value) => is_string($value) ? htmlspecialchars($value, ENT_QUOTES) : $value,
            $this->config->in
        );
    }

    private function initializeTheme(): void 
    {
        $themeType = $this->config->in['theme'] ?? 'default';
        $themeClass = "Themes\\{$themeType}\\Theme";
        
        $this->theme = class_exists($themeClass) 
            ? new $themeClass($this->config)
            : throw new \RuntimeException("Theme not found: {$themeClass}");
    }

    private function executePlugin(): void 
    {
        $pluginName = $this->config->in['plugin'] ?? '';
        if (empty($pluginName)) {
            throw new PluginNotFoundException('No plugin specified');
        }

        $pluginClass = "Plugins\\{$pluginName}\\Plugin";
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
        if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || 
            !hash_equals($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {
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
        return json_encode($data, 
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
            error_log(sprintf(
                "Request: %s\nGET: %s\nPOST: %s\nSESSION: %s",
                $_SERVER['REQUEST_URI'],
                var_export($_GET, true),
                var_export($_POST, true),
                var_export($_SESSION, true)
            ));
        }
    }

    public function __destruct() 
    {
        if ($_ENV['APP_DEBUG'] ?? false) {
            error_log(sprintf(
                "Request completed: %s %s %.4f seconds",
                $_SERVER['REQUEST_URI'],
                $_SERVER['REMOTE_ADDR'],
                microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
            ));
        }
    }
}

/**
 * Plugin Interface
 */
interface PluginInterface 
{
    public function execute(): mixed;
    public function __toString(): string;
}

/**
 * Theme Interface
 */
interface ThemeInterface 
{
    public function render(): string;
    public function html(): string;
}

/**
 * Configuration Interface
 */
interface ConfigInterface 
{
    public function getConfig(): array;
}

/**
 * Custom Exceptions
 */
class PluginNotFoundException extends \Exception {}
class InvalidPluginException extends \Exception {}

// Example usage:
/*
$config = new Config([
    'app_name' => 'My App',
    'version' => '1.0.0'
], [
    'plugin' => 'Blog',
    'theme' => 'Modern',
    'format' => 'html'
]);

$app = new Init($config);
echo $app;
*/
