<?php

namespace App\Vito\Plugins\Mohammedazman\MemcachedServicePlugin\Services;

use App\Models\Server;
use App\Services\AbstractService;

/**
 * Service handler for Memcached.
 *
 * This class implements the necessary methods for Vito to manage the
 * Memcached systemd service. It exposes commands to install, uninstall and
 * manage the service on the underlying server. Memcached is an in-memory
 * caching system and is categorized under the `memory_database` service type.
 */
class Memcached extends AbstractService
{
    /**
     * Unique identifier for the service.
     *
     * @return string
     */
    public static function id(): string
    {
        return 'memcached';
    }

    /**
     * Category/type of the service. Memcached falls under the
     * `memory_database` group so that it appears alongside Redis in Vito.
     *
     * @return string
     */
    public static function type(): string
    {
        return 'memory_database';
    }

    /**
     * Human‑readable name for display in the Vito UI.
     *
     * @return string
     */
    public function displayName(): string
    {
        return 'Memcached';
    }

    /**
     * The systemd unit name for the service. Vito uses this name to run
     * commands like start, stop and restart.
     *
     * @return string
     */
    public function serviceName(): string
    {
        return 'memcached';
    }

    /**
     * Install Memcached on the server. This method is executed when the user
     * clicks the Install button in the Services UI.
     *
     * @param Server $server
     */
    public function install(Server $server): void
    {
        // Update package index and install memcached and tools
        $server->run('sudo apt-get update -y');
        $server->run('sudo apt-get install -y memcached libmemcached-tools');
        // Ensure the service is enabled and running
        $server->run('sudo systemctl enable memcached');
        $server->run('sudo systemctl restart memcached');
    }

    /**
     * Uninstall Memcached from the server.
     *
     * @param Server $server
     */
    public function uninstall(Server $server): void
    {
        // Stop the service if running and remove packages
        $server->run('sudo systemctl stop memcached || true');
        $server->run('sudo apt-get remove -y memcached libmemcached-tools');
        // Remove orphaned dependencies
        $server->run('sudo apt-get autoremove -y');
    }

    /**
     * Start the service.
     *
     * @param Server $server
     */
    public function start(Server $server): void
    {
        $server->run('sudo systemctl start memcached');
    }

    /**
     * Stop the service.
     *
     * @param Server $server
     */
    public function stop(Server $server): void
    {
        $server->run('sudo systemctl stop memcached');
    }

    /**
     * Restart the service.
     *
     * @param Server $server
     */
    public function restart(Server $server): void
    {
        $server->run('sudo systemctl restart memcached');
    }

    /**
     * Enable the service to start on boot.
     *
     * @param Server $server
     */
    public function enable(Server $server): void
    {
        $server->run('sudo systemctl enable memcached');
    }

    /**
     * Disable the service from starting on boot.
     *
     * @param Server $server
     */
    public function disable(Server $server): void
    {
        $server->run('sudo systemctl disable memcached');
    }
}