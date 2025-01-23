<?php

declare(strict_types=1);

namespace Markc\Pablo\Plugins\Docs;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Markc\Pablo\Core\Plugin as BasePlugin;

class Plugin extends BasePlugin
{
    private readonly GithubFlavoredMarkdownConverter $converter;
    private const DOCS_PATH = ROOT . '/docs';

    public function __construct()
    {
        $this->converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
            'renderer' => [
                'block_separator' => "\n",
                'inner_separator' => "\n",
                'soft_break' => "\n"
            ]
        ]);
    }

    public function execute(): string
    {
        return match (true) {
            isset($_GET['doc']) => $this->renderDocument($_GET['doc']),
            default => $this->renderDocumentList()
        };
    }

    private function css(): string
    {
        return '
            <style>
            li p {
              margin-bottom: 0;
            }
            .markdown-body ul, ol {
               margin-bottom: 1.5em;
            }
            .markdown-body h1, h2, h3 {
               font-weight: bold;
               margin-bottom: 0.75em;
               border-bottom: 1px solid #efefef;
            }
            .markdown-body h1, h2, h3 {
               margin-top: 1em;
            }
            .markdown-body h1 {
               margin-top: 0.5em;
            }
            .markdown-body h2 {
               font-size: 1.5em;
            }
            .markdown-body h3 {
                font-size: 1.25em;
            }
            .markdown-body p {
                margin-bottom: .5em;
            }
           .markdown-body {
               box-sizing: border-box;
                min-width: 200px;
                /* max-width: 980px; */
                margin: 0 auto;
                /* padding: 45px; */
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial,
                    sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            }
            .markdown-body pre {
                padding: 16px;
                overflow: auto;
                /* font-size: 95%; */
                line-height: 1.45;
                background-color: #f6f8fa;
                border-radius: 6px;
            }
            .markdown-body code:not(pre code) {
                padding: 0.2em 0.4em;
                margin: 0;
                /* font-size: 85%; */
                background-color: rgba(27, 31, 35, 0.05);
                border-radius: 6px;
            }
            @media (max-width: 767px) {
                .markdown-body {
                    padding: 15px;
                }
            }
            </style>';
    }

    private function renderDocument(string $docName): string
    {
        // Add .md extension if not present
        if (!str_ends_with(strtolower($docName), '.md')) {
            $docName .= '.md';
        }

        error_log('self::DOCS_PATH=' . self::DOCS_PATH);

        //$docPath = $this->sanitizePath(self::DOCS_PATH . '/' . $docName);
        $docPath = self::DOCS_PATH . '/' . $docName;

        error_log('docPath=' . $docPath);

        if (!$this->isValidDocument($docPath)) {
            return $this->renderError('Document not found or invalid (' . $docPath . ')');
        }

        // Use the actual file path from isValidDocument if it was updated
        //if (isset($_GET['doc']) && $_GET['doc'] !== $docName) {
        //    $docPath = $this->sanitizePath(self::DOCS_PATH . '/' . $_GET['doc']);
        //}

        $content = file_get_contents($docPath);
        $processedContent = $this->preprocessMarkdown($content);

        return $this->css() . sprintf(
            '<div class="markdown-body">%s</div>',
            $this->converter->convert($processedContent)
        );
    }

    private function renderDocumentList(): string
    {
        $docs = glob(self::DOCS_PATH . '/*.md') ?: [];

        $listItems = array_map(
            fn(string $doc): string => $this->createDocumentLink($doc),
            $docs
        );

        return sprintf(
            '<div class="documentation-container">
                <h1>Documentation</h1>
                <div class="list-group">%s</div>
            </div>',
            implode('', $listItems)
        );
    }

    private function createDocumentLink(string $docPath): string
    {
        $filename = basename($docPath);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        // Convert underscores to hyphens but keep numeric prefix
        $urlName = str_replace('_', '-', $name);
        // Create title without numeric prefix
        $title = ucwords(str_replace(['_', '-'], ' ', preg_replace('/^\d+[_-]/', '', $name)));

        return sprintf(
            '<a href="?plugin=docs&doc=%s" class="list-group-item list-group-item-action">
                %s
            </a>',
            urlencode($urlName),
            htmlspecialchars($title)
        );
    }

    private function preprocessMarkdown(string $content): string
    {
        // Fix relative image paths
        $content = preg_replace(
            '/!\[(.*?)\]\((?!http)(.*?)\)/',
            '![$1](/docs/assets/$2)',
            $content
        );

        return $content;
    }

    //private function sanitizePath(string $path): string
    //{
    //    return str_replace(['../', './'], '', $path);
    //}

    private function isValidDocument(string $path): bool
    {
        // If exact file exists, use it
        if (file_exists($path) && is_file($path)) {
            return pathinfo($path, PATHINFO_EXTENSION) === 'md' &&
                strpos(realpath($path), realpath(self::DOCS_PATH)) === 0;
        }

        return false;
    }

    private function renderError(string $message): string
    {
        return sprintf(
            '<div class="alert alert-danger">%s</div>',
            htmlspecialchars($message)
        );
    }
}
