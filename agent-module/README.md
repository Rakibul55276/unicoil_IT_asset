# Optional Windows Agent module

This directory contains the Windows inventory Agent API, dashboards, MVC screens,
models, views, styles, and database installer.

The original public URLs and MVC paths are preserved by small compatibility loaders
outside this directory. Asset registration, assignment, return, approval, reporting,
and user workflows are not changed by this organization.

Configure the module in the private root `config.php` file:

```php
'agent_enabled' => true,
```

- `true`: Agent API and Agent screens operate normally.
- `false`: synchronization endpoints return HTTP 503, Agent MVC routes return 404,
  and the main dashboard does not query Agent inventory tables.

The existing Automatic Agent navigation remains in its previous commented state.