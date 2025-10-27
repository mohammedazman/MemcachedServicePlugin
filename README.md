# Memcached Service Plugin for VitoDeploy

This plugin adds **Memcached** as a first‑class service to your VitoDeploy
instance. It follows the Vito plugin API, registering a service type,
server feature and an action to tune Memcached settings via the UI.

## Features

* **Install/Uninstall** Memcached from the server via the Services page
* **Start/Stop/Enable/Disable** the Memcached systemd service
* **Edit configuration files** (`/etc/memcached.conf`) directly from the UI
* **Apply tuning**: adjust memory, port and listen address with a single
  action that updates the config file and restarts the service

## Installation (Development)

Place the plugin under:

```
app/Vito/Plugins/Mohammedazman/MemcachedServicePlugin
```

Make sure the namespace in `Plugin.php`, service and action classes matches
the directory structure (e.g. `App\Vito\Plugins\Mohammedazman\MemcachedServicePlugin`).
Then navigate to **Settings → Plugins → Discover** in the Vito UI and install
and enable the plugin.

