<?php

declare(strict_types=1);

namespace Markc\Pablo\Themes\Default;

use Markc\Pablo\Core\Theme as BaseTheme;

class Theme extends BaseTheme
{
    public function render(): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$this->config->cfg['app_name']}</title>
        </head>
        <body>
            {$this->config->out['main']}
        </body>
        </html>
        HTML;
    }
}
