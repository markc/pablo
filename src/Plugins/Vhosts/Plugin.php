<?php

declare(strict_types=1);

namespace Markc\Pablo\Plugins\Vhosts;

use Markc\Pablo\Core\Plugin as BasePlugin;
use Markc\Pablo\Core\Theme;
use Markc\Pablo\Themes\Default\Vhosts as VhostsTheme;

class Plugin extends BasePlugin
{
    protected array $inputs;

    public function __construct(VhostsTheme $theme)
    {
        parent::__construct($theme);
        $this->inputs = [
            'active'    => 0,
            'aid'       => 0,
            'aliases'   => 10,
            'diskquota' => 1_000_000_000,
            'domain'    => '',
            'gid'       => 1000,
            'mailboxes' => 1,
            'mailquota' => 500_000_000,
            'uid'       => 1000,
            'uname'     => '',
            'cms'       => '',
            'ssl'       => '',
            'ip'        => '',
            'uuser'     => '',
        ];
    }

    public function execute(): string
    {
        $action = $this->getQueryParam('action', 'list');

        if ($this->isPost()) {
            return match ($action) {
                'create' => $this->handleCreate(),
                'update' => $this->handleUpdate(),
                'delete' => $this->handleDelete(),
                default => $this->theme->list([])
            };
        }

        return match ($action) {
            'create' => $this->theme->create($this->inputs),
            'update' => $this->theme->update(['id' => $this->getQueryParam('id')]),
            default => $this->theme->list([])
        };
    }

    protected function handleCreate(): string
    {
        $domain = $this->getInput('domain');
        $cms = $this->getInput('cms') === 'on' ? 'wp' : 'none';
        $ssl = $this->getInput('ssl') === 'on' ? 'self' : 'le';
        $ip = $this->getInput('ip');
        $uuser = $this->getInput('uuser');

        if (is_dir("/home/u/{$domain}")) {
            $this->addMessage("/home/u/{$domain} already exists", 'warning');
            return $this->theme->create($this->inputs);
        }

        $vhost = $uuser ? "{$uuser}@{$domain}" : $domain;
        $this->runCommand('addvhost', [$vhost, $cms, $ssl, $ip]);
        $this->addMessage("Added {$domain}, please wait for setup to complete", 'success');
        $this->redirect('?plugin=vhosts');
        return '';
    }

    protected function handleUpdate(): string
    {
        $id = $this->getQueryParam('id');
        $active = $this->getInput('active', 0);
        $aliases = $this->getInput('aliases', 0);
        $diskquota = $this->getInput('diskquota', 0) * 1_000_000;
        $mailboxes = $this->getInput('mailboxes', 0);
        $mailquota = $this->getInput('mailquota', 0) * 1_000_000;

        if ($mailquota > $diskquota) {
            $this->addMessage('Mailbox quota exceeds disk quota', 'error');
            return $this->theme->update(['id' => $id]);
        }

        $this->addMessage("Vhost ID {$id} updated", 'success');
        $this->redirect('?plugin=vhosts');
        return '';
    }

    protected function handleDelete(): string
    {
        $domain = $this->getInput('domain');
        if ($domain) {
            $this->runCommand('delvhost', [$domain]);
            $this->addMessage("Removed {$domain}", 'success');
        } else {
            $this->addMessage('ERROR: domain does not exist', 'error');
        }
        $this->redirect('?plugin=vhosts');
        return '';
    }

    protected function runCommand(string $cmd, array $args): void
    {
        $escapedArgs = array_map('escapeshellarg', $args);
        $command = sprintf(
            "nohup sh -c 'sudo %s %s' > /tmp/%s.log 2>&1 &",
            $cmd,
            implode(' ', $escapedArgs),
            $cmd
        );
        shell_exec($command);
    }

    protected function getInput(string $key, mixed $default = ''): mixed
    {
        return $_POST[$key] ?? $this->inputs[$key] ?? $default;
    }

    protected function getQueryParam(string $key, mixed $default = ''): mixed
    {
        return $_GET[$key] ?? $default;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function addMessage(string $message, string $type = 'info'): void
    {
        $_SESSION['messages'][] = ['type' => $type, 'text' => $message];
    }

    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
