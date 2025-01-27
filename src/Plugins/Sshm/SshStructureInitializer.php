<?php

declare(strict_types=1);

namespace Markc\Pablo\Plugins\Sshm;

use RuntimeException;

class SshStructureInitializer
{
    private string $sshDir;
    private string $configDir;

    public function __construct(string $sshDir, string $configDir)
    {
        $this->sshDir = $sshDir;
        $this->configDir = $configDir;
    }

    public function initialize(): void
    {
        try {
            // Create .ssh directory if it doesn't exist
            if (!is_dir($this->sshDir)) {
                mkdir($this->sshDir, 0700, true);
            }

            // Create authorized_keys file if it doesn't exist
            $authKeys = "{$this->sshDir}/authorized_keys";
            if (!file_exists($authKeys)) {
                touch($authKeys);
                chmod($authKeys, 0600);
            }

            // Create config.d directory if it doesn't exist
            if (!is_dir($this->configDir)) {
                mkdir($this->configDir, 0700, true);
            }

            // Create/update main config file
            $configFile = "{$this->sshDir}/config";
            if (!file_exists($configFile)) {
                $config = "# Created by SSHM Plugin on " . date('Ymd') . "\n" .
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
                chmod($configFile, 0600);
            }

            // Set proper permissions
            system("find {$this->sshDir} -type d -exec chmod 700 {} +");
            system("find {$this->sshDir} -type f -exec chmod 600 {} +");
        } catch (\Exception $e) {
            error_log('SSHM Plugin: SSH structure initialization error: ' . $e->getMessage());
            throw $e;
        }
    }
}
