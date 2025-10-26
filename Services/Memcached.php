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
     * Return the systemd unit name
     */
    public function unit(): string
    {
        return 'memcached';
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
     */
    public function install(): void
    {

        // Update package index and install memcached and tools
        $this->service->server->ssh()->exec('sudo apt-get update -y');
        $this->service->server->ssh()->exec('sudo apt-get install -y memcached libmemcached-tools');
        // Ensure the service is enabled and running
        $this->service->server->ssh()->exec('sudo systemctl enable memcached');
        $this->service->server->ssh()->exec('sudo systemctl restart memcached');
    }

    /**
     * Uninstall Memcached from the server.
     *
     */
    public function uninstall(): void
    {
        if (! $this->isInstalled()) {
            return;
        }

        // Stop the service if running and remove packages
        $this->service->server->ssh()->exec('sudo systemctl stop memcached || true');
        $this->service->server->ssh()->exec('sudo apt-get remove -y memcached libmemcached-tools');
        // Remove orphaned dependencies
        $this->service->server->ssh()->exec('sudo apt-get autoremove -y');
    }

    /**
     * Start the service.
     */
    public function start(): void
    {
        $this->service->server->ssh()->exec('sudo systemctl start memcached');
    }

    /**
     * Stop the service.
     *
     */
    public function stop(): void
    {
        $this->service->server->ssh()->exec('sudo systemctl stop memcached');
    }

    /**
     * Restart the service.
     *
     */
    public function restart(): void
    {
        $this->service->server->ssh()->exec('sudo systemctl restart memcached');
    }

    /**
     * Enable the service to start on boot.
     *
     */
    public function enable(): void
    {
        $this->service->server->ssh()->exec('sudo systemctl enable memcached');
    }

    /**
     * Disable the service from starting on boot.
     *
     */
    public function disable(): void
    {
        $this->service->server->ssh()->exec('sudo systemctl disable memcached');
    }

    public function version(): string
    {

        try {
            $output = $this->service->server->ssh()->exec('memcached -h | head -n 1');
            if (empty($output)) {
                return  $this->service->version ?? 'unknown';
            }
            return trim(str_replace('memcached', '', $output));
        } catch (\Throwable $e) {
            return $this->service->version ?? 'unknown';
        }
    }

    public function status(): string
    {
        try {
            $result = $this->server->ssh()->exec('sudo systemctl is-active memcached');
            return trim($result) === 'active' ? 'running' : 'stopped';
        } catch (\Throwable $e) {
            return 'stopped';
        }
    }

    public function isInstalled(): bool
    {
        try {
            $result = $this->server->ssh()->exec(
                'dpkg -s memcached 2>/dev/null | grep -i ^status:'
            );
            return str_contains(strtolower($result), 'install ok installed');
        } catch (\Throwable $e) {
            return false;
        }
    }
}