#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * SSH Manager (SSHM)
 * Copyright (C) 1995-2024 Mark Constable <markc@renta.net> (AGPL-3.0)
 */

class SSHManager
{
    private readonly string $sshDir;
    private readonly string $configDir;
    private bool $debug = false;

    public function __construct()
    {
        $this->debug = (bool)getenv('DEBUG');
        $home = getenv('HOME') ?: posix_getpwuid(posix_geteuid())['dir'];
        $this->sshDir = $home . '/.ssh';
        $this->configDir = $home . '/.ssh/config.d';
    }

    public function help(?string $command = null): never
    {
        $helpText = match ($command) {
            'c', 'create' => "Create a new SSH Host file in ~/.ssh/config.d/\n" .
                "Usage: sshm create <Name> <Host> [Port] [User] [Skey]",
            'r', 'read' => "Show the content values for a host\n" .
                "Usage: sshm read <Name>",
            'u', 'update' => "Edit the contents of an SSH Host file\n" .
                "Usage: sshm update <Name>",
            'd', 'delete' => "Delete an SSH Host config file\n" .
                "Usage: sshm delete <Name>",
            'l', 'list' => "List all host config files\n" .
                "Usage: sshm list",
            'kc', 'key_create' => "Create a new SSH Key\n" .
                "Usage: sshm key_create <Name> [Comment] [Password]",
            'kr', 'key_read' => "Show SSH Key\n" .
                "Usage: sshm key_read <Name>",
            'ku', 'key_update' => "Update SSH Key (alias for key_create)\n" .
                "Usage: sshm key_update <Name> [Comment] [Password]",
            'kd', 'key_delete' => "Delete SSH Key\n" .
                "Usage: sshm key_delete <Name>",
            'kl', 'key_list' => "List all SSH Keys\n" .
                "Usage: sshm key_list",
            'i', 'init' => "Initialize ~/.ssh structure\n" .
                "Usage: sshm init",
            'p', 'perms' => "Reset permissions for ~/.ssh\n" .
                "Usage: sshm perms",
            default => "Usage: sshm <cmd> [args]\n" .
                "Commands: create, read, update, delete, list, key_create, " .
                "key_read, key_update, key_delete, key_list, init, perms, help"
        };
        echo $helpText . PHP_EOL;
        exit(1);
    }

    public function create(
        string $name,
        string $host,
        string $port = '22',
        string $user = 'root',
        string $skey = 'none'
    ): never {
        $config = "Host $name\n" .
            "    Hostname $host\n" .
            "    Port $port\n" .
            "    User $user\n";

        if ($skey !== 'none') {
            $config .= "    IdentityFile $skey\n";
        } else {
            $config .= "    #IdentityFile none\n";
        }

        file_put_contents("{$this->configDir}/$name", $config);
        exit(0);
    }

    public function read(string $name): never
    {
        $file = "{$this->configDir}/$name";
        if (!file_exists($file)) {
            echo "Notice: ~/.ssh/config.d/'$name' does not exist (254)";
            exit(254);
        }

        $lines = array_map('strval', file($file) ?: []);
        foreach ($lines as $line) {
            if (preg_match('/^\s+(\w+)\s+(.*)$/', $line, $matches)) {
                echo $matches[2] . PHP_EOL;
            }
        }
        exit(0);
    }

    public function update(string $name): never
    {
        $file = "{$this->configDir}/$name";
        if (!file_exists($file)) {
            echo "Notice: ~/.ssh/config.d/'$name' does not exist (254)";
            exit(254);
        }

        //system("nano -t -x -c $file");
        passthru("nano -t -x -c $file");
        exit(0);
    }

    public function delete(string $name): never
    {
        $file = "{$this->configDir}/$name";
        if (file_exists($file)) {
            unlink($file);
            echo "Removed: SSH host '$name' (251)";
            exit(251);
        } else {
            echo "Error: SSH host '$name' does not exist (255)";
            exit(255);
        }
    }

    public function list(): never
    {
        $files = glob("{$this->configDir}/*") ?: [];
        foreach ($files as $file) {
            $content = (string)file_get_contents($file);
            if (preg_match_all('/^\s*(\w+)\s+(.*)$/m', $content, $matches)) {
                $values = array_combine($matches[1], $matches[2]) ?: [];
                printf(
                    "%-15s %25s %5s %10s %20s\n",
                    $values['Host'] ?? '',
                    $values['Hostname'] ?? '',
                    $values['Port'] ?? '',
                    $values['User'] ?? '',
                    $values['IdentityFile'] ?? ''
                );
            }
        }
        exit(0);
    }

    public function keyCreate(
        string $name,
        ?string $comment = null,
        ?string $password = null
    ): never {
        $comment ??= (string)gethostname() . '@lan';
        $keyFile = "{$this->sshDir}/$name";

        if (file_exists($keyFile)) {
            echo "Warning: SSH Key '~/.ssh/$name' already exists";
            exit(254);
        }

        $cmd = "ssh-keygen -o -a 100 -t ed25519 -f $keyFile -C \"$comment\"";
        if ($password !== null) {
            $cmd .= " -N \"$password\"";
        }

        $result = 0;
        system($cmd, $result);
        if ($result !== 0) {
            echo "Error: SSH key '$name' not created";
            exit(254);
        }
        exit(0);
    }

    public function keyRead(string $name): never
    {
        $keyFile = "{$this->sshDir}/$name.pub";
        if (file_exists($keyFile)) {
            echo (string)file_get_contents($keyFile);
            exit(0);
        } else {
            echo "Warning: '$name' key does not exist (254)";
            exit(254);
        }
    }

    public function keyDelete(string $name): never
    {
        $keyFile = "{$this->sshDir}/$name";
        if (file_exists($keyFile)) {
            unlink($keyFile);
            unlink("$keyFile.pub");
            echo "Success: removed ~/.ssh/$name and ~/.ssh/$name.pub";
            exit(0);
        } else {
            echo "Error: ~/.ssh/$name does not exist";
            exit(255);
        }
    }

    public function keyList(): never
    {
        $keys = glob("{$this->sshDir}/*.pub") ?: [];
        foreach ($keys as $key) {
            $basename = basename($key, '.pub');
            echo "$basename ";
            system("ssh-keygen -lf \"$key\"");
        }
        exit(0);
    }

    public function copy(string $skey, string $name): never
    {
        $keyFile = "{$this->sshDir}/$skey.pub";
        $configFile = "{$this->configDir}/$name";

        if (!file_exists($keyFile)) {
            echo "Error: ~/.ssh/$skey.pub does not exist";
            exit(255);
        }
        if (!file_exists($configFile)) {
            echo "Error: ~/.ssh/config.d/$name does not exist";
            exit(255);
        }

        $pubkey = (string)file_get_contents($keyFile);
        $cmd = "ssh $name \"[[ ! -d ~/.ssh ]] && mkdir -p ~/.ssh && chmod 700 ~/.ssh; " .
            "echo '$pubkey' >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys\"";

        $result = 0;
        system($cmd, $result);

        if ($result === 0) {
            echo "Success: Public key $skey.pub was successfully transferred to $name";
            exit(0);
        }
        exit(1);
    }

    public function perms(): never
    {
        system("find {$this->sshDir} -type d -exec chmod 700 {} +");
        system("find {$this->sshDir} -type f -exec chmod 600 {} +");
        echo "Updated permissions for ~/.ssh";
        exit(0);
    }

    public function init(): never
    {
        if (!is_dir($this->sshDir)) {
            mkdir($this->sshDir, 0700, true);
            echo "Created ~/.ssh\n";
        }

        $authKeys = "{$this->sshDir}/authorized_keys";
        if (!file_exists($authKeys)) {
            touch($authKeys);
            chmod($authKeys, 0600);
            echo "Created ~/.ssh/authorized_keys\n";
        }

        if (!is_dir($this->configDir)) {
            mkdir($this->configDir, 0700, true);
            echo "Created ~/.ssh/config.d\n";
        }

        $configFile = "{$this->sshDir}/config";
        if (!file_exists($configFile)) {
            $config = "# Created by sshm on " . date('Ymd') . "\n" .
                "Ciphers aes128-ctr,aes192-ctr,aes256-ctr,aes128-gcm@openssh.com," .
                "aes256-gcm@openssh.com,chacha20-poly1305@openssh.com\n\n" .
                "Include ~/.ssh/config.d/*\n\n" .
                "Host *\n" .
                "  TCPKeepAlive yes\n" .
                "  ServerAliveInterval 30\n" .
                "  ForwardAgent yes\n" .
                "  AddKeysToAgent yes\n" .
                "  IdentitiesOnly yes\n";
            file_put_contents($configFile, $config);
        }

        $this->perms();
        exit(0);
    }

    public function start(): never
    {
        system("sudo systemctl start sshd");
        system("sudo systemctl enable sshd");
        exit(0);
    }

    public function stop(): never
    {
        system("sudo systemctl stop sshd");
        system("sudo systemctl disable sshd");
        exit(0);
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): never
    {
        if (empty($args[1]) || in_array($args[1], ['-h', '--help'], true)) {
            $this->help();
        }

        if ($this->debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }

        $command = $args[1];
        $params = array_slice($args, 2);

        match ($command) {
            'c', 'create' => $this->create(...$params),
            'r', 'read' => $this->read($params[0]),
            'u', 'update' => $this->update($params[0]),
            'd', 'delete' => $this->delete($params[0]),
            'l', 'list' => $this->list(),
            'kc', 'key_create' => $this->keyCreate(...$params),
            'kr', 'key_read' => $this->keyRead($params[0]),
            'ku', 'key_update' => $this->keyCreate(...$params),
            'kd', 'key_delete' => $this->keyDelete($params[0]),
            'kl', 'key_list' => $this->keyList(),
            'i', 'init' => $this->init(),
            'p', 'perms' => $this->perms(),
            'start' => $this->start(),
            'stop' => $this->stop(),
            'h', 'help' => $this->help($params[0] ?? null),
            default => throw new \InvalidArgumentException("Unknown command '$command'")
        };
    }
}

// Run the application
try {
    $sshm = new SSHManager();
    $sshm->run($argv);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
