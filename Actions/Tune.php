<?php

namespace App\Vito\Plugins\Mohammedazman\MemcachedServicePlugin\Actions;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Models\Server;
use App\ServerFeatures\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Tune extends Action
{
    public function __construct(public Server $server) {}

    public function name(): string
    {
        return 'Apply Tuning';
    }

    public function active(): bool
    {
        return true;
    }

    public function form(): ?DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('memory_mb')
                ->text()
                ->label('Memory (MB)')
                ->description('Total memory to allocate to memcached (e.g., 64, 256, 1024)')
                ->default(64)
                ->required(),
            DynamicField::make('port')
                ->text()
                ->label('Port')
                ->description('TCP port memcached will listen on')
                ->default(11211)
                ->required(),
            DynamicField::make('listen')
                ->text()
                ->label('Listen Address')
                ->description('Bind address (e.g., 127.0.0.1 or 0.0.0.0)')
                ->default('127.0.0.1')
                ->required(),
        ]);
    }

    public function handle(Request $request): void
    {
        // Validate inputs like the RabbitMQ example does
        Validator::make($request->all(), [
            'memory_mb' => 'required|integer|min:16|max:262144',
            'port'      => 'required|integer|between:1,65535',
            'listen'    => 'required|string|max:255',
        ])->validate();

        $memory = (int) $request->input('memory_mb', 64);
        $port   = (int) $request->input('port', 11211);
        $listen = $request->input('listen', '127.0.0.1');

        // Replace existing flags (-m, -p, -l) if present
        $this->server->ssh()->exec(
            "sudo sed -i 's/^\\-m .*/-m {$memory}/' /etc/memcached.conf || true",
            'memcached-tune-replace-m'
        );
        $this->server->ssh()->exec(
            "sudo sed -i 's/^\\-p .*/-p {$port}/' /etc/memcached.conf || true",
            'memcached-tune-replace-p'
        );
        $this->server->ssh()->exec(
            "sudo sed -i 's/^\\-l .*/-l {$listen}/' /etc/memcached.conf || true",
            'memcached-tune-replace-l'
        );

        // Append flags if they were missing
        $this->server->ssh()->exec(
            "grep -q '^\\-m ' /etc/memcached.conf || echo '-m {$memory}' | sudo tee -a /etc/memcached.conf",
            'memcached-tune-append-m'
        );
        $this->server->ssh()->exec(
            "grep -q '^\\-p ' /etc/memcached.conf || echo '-p {$port}' | sudo tee -a /etc/memcached.conf",
            'memcached-tune-append-p'
        );
        $this->server->ssh()->exec(
            "grep -q '^\\-l ' /etc/memcached.conf || echo '-l {$listen}' | sudo tee -a /etc/memcached.conf",
            'memcached-tune-append-l'
        );

        // Restart memcached to apply the new configuration
        $this->server->ssh()->exec(
            'sudo systemctl restart memcached',
            'memcached-restart'
        );

        $request->session()->flash(
            'success',
            "Memcached tuning applied: memory={$memory}MB, port={$port}, listen={$listen}"
        );
    }
}
