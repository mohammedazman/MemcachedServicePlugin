<?php

namespace App\Vito\Plugins\MohammedAzman\MemcachedServicePlugin;

use App\Plugins\AbstractPlugin;
use App\Plugins\RegisterServiceType;
use App\Plugins\RegisterServerFeature;
use App\Plugins\RegisterServerFeatureAction;
use App\Plugins\RegisterViews;
use App\DTOs\DynamicForm;
use App\DTOs\DynamicField;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Models\Service;
use App\Vito\Plugins\MohammedAzman\MemcachedServicePlugin\Services\Memcached;
use App\Vito\Plugins\MohammedAzman\MemcachedServicePlugin\Actions\Tune;

/**
 * Plugin bootstrap class for the Memcached service.
 *
 * This class is responsible for registering the Memcached service, along with
 * any server features and actions. It exposes the Memcached systemd service
 * through Vito’s Services page, allowing users to install, start/stop and
 * configure the service. In addition to the basic service integration, it
 * registers a simple server feature with an action to tune core settings like
 * memory allocation, port and listen address. A view is also registered to
 * provide a descriptive panel inside the Memcached service page.
 */
class Plugin extends AbstractPlugin
{
    /**
     * Human‑friendly name of the plugin.
     *
     * @var string
     */
    protected string $name = 'Memcached Service Plugin';

    /**
     * Description of the plugin shown in the Vito UI.
     *
     * @var string
     */
    protected string $description = 'Adds Memcached as a manageable service with config editing and tuning features.';

    /**
     * Called by Vito when bootstrapping the plugin. All service, feature
     * registrations should happen here.
     */
    public function boot(): void
    {
        // Register the Memcached service. This makes the service visible under
        // the Services tab in Vito’s UI and enables config file editing. The
        // handler points to our service class which controls installation and
        // management of the systemd service.
        RegisterServiceType::make(Memcached::id())
            ->type(Memcached::type())
            ->label('Memcached')
            ->handler(Memcached::class)
            ->configPaths([
                [
                    'name' => 'memcached.conf',
                    'path' => '/etc/memcached.conf',
                    'sudo' => true,
                ],
            ])
            ->register();

        // Register a server feature for Memcached. Features show up on the
        // server overview page and allow users to perform actions not tied to a
        // particular site. Here we provide an action for tuning the service.
        RegisterServerFeature::make('memcached')
            ->label('Memcached')
            ->description('Install, configure and tune Memcached on this server')
            ->register();

        // Register a server feature action named "tune". The form collects
        // tuning parameters and passes them to the action handler. Each field
        // includes sensible defaults and labels. When executed, the action
        // updates the configuration file and restarts the service.
        RegisterServerFeatureAction::make('memcached', 'tune')
            ->label('Apply Tuning')
            ->form(
                DynamicForm::make([
                    DynamicField::make('memory_mb')
                        ->text()
                        ->label('Memory (MB)')
                        ->placeholder('64')
                        ->description('Amount of memory to allocate (in MB)'),
                    DynamicField::make('port')
                        ->text()
                        ->label('Port')
                        ->placeholder('11211')
                        ->description('Port for Memcached to listen on'),
                    DynamicField::make('listen')
                        ->text()
                        ->label('Listen Address')
                        ->placeholder('127.0.0.1')
                        ->description('IP address Memcached binds to'),
                ])
            )
            ->handler(Tune::class)
            ->register();

        // Register a Blade view directory. Views can be referenced in the UI to
        // display custom panels or pages. In this plugin we provide a simple
        // informational view for the service overview.
        RegisterViews::make('memcached-plugin')
            ->path(__DIR__ . '/views')
            ->register();

        // Listen for service install/uninstall events to log actions or
        // trigger additional behaviour. Here we simply log when Memcached is
        // installed or removed.
        Event::listen('service.installed', function (Service $service) {
            if ($service->type === Memcached::type()) {
                Log::info('Memcached installed', ['service_id' => $service->id]);
            }
        });

        Event::listen('service.uninstalled', function (Service $service) {
            if ($service->type === Memcached::type()) {
                Log::info('Memcached uninstalled', ['service_id' => $service->id]);
            }
        });
    }

    /**
     * Hook called when installing the plugin. Override if needed.
     */
    public function install(): void
    {
    }

    /**
     * Hook called when uninstalling the plugin. Override if needed.
     */
    public function uninstall(): void
    {
    }

    /**
     * Hook called when enabling the plugin. Override if needed.
     */
    public function enable(): void
    {
    }

    /**
     * Hook called when disabling the plugin. Override if needed.
     */
    public function disable(): void
    {
    }
}