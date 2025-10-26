<?php

namespace App\Vito\Plugins\Mohammedazman\MemcachedServicePlugin\Actions;

use App\Models\Server;
use App\ServerFeatures\Action;

/**
 * Server action to apply tuning settings to Memcached.
 *
 * This action reads the form inputs provided by the user, updates the
 * `/etc/memcached.conf` file accordingly, and restarts the service to apply
 * changes. It looks for existing lines starting with `-m`, `-p` and `-l` and
 * replaces them; if they are missing it appends the flags.
 */
class Tune extends Action
{
    /**
     * Execute the tuning action on the given server.
     *
     * @param Server $server
     * @param array $inputs
     */
    public function handle(Server $server, array $inputs = []): void
    {
        // Extract inputs with defaults
        $memory = (int) ($inputs['memory_mb'] ?? 64);
        $port = (int) ($inputs['port'] ?? 11211);
        $listen = $inputs['listen'] ?? '127.0.0.1';

        // Build replacement commands. Use sed to replace existing flags; if
        // flags are absent the subsequent grep/tee will append them.
        $server->run("sudo sed -i 's/^\-m .*/-m {$memory}/' /etc/memcached.conf || true");
        $server->run("sudo sed -i 's/^\-p .*/-p {$port}/' /etc/memcached.conf || true");
        $server->run("sudo sed -i 's/^\-l .*/-l {$listen}/' /etc/memcached.conf || true");

        // Append flags if they were not present in the file
        $server->run("grep -q '^\-m ' /etc/memcached.conf || echo '-m {$memory}' | sudo tee -a /etc/memcached.conf");
        $server->run("grep -q '^\-p ' /etc/memcached.conf || echo '-p {$port}' | sudo tee -a /etc/memcached.conf");
        $server->run("grep -q '^\-l ' /etc/memcached.conf || echo '-l {$listen}' | sudo tee -a /etc/memcached.conf");

        // Restart Memcached to apply the new configuration
        $server->run('sudo systemctl restart memcached');
    }
}