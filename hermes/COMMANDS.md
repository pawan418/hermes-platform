# Hermes CLI Command Guide

Detailed usage instructions for the unified platform CLI.

## Commands Reference

- **`hermes version`** / **`hermes about`**: Displays platforms build specs.
- **`hermes status`**: Displays CPU/RAM/Disk stats and active container health.
- **`hermes shell [service]`**: Log in to app, db, redis, qdrant, or minio.
- **`hermes artisan <cmd>`**: Wrapper executing php artisan commands in FPM app.
- **`hermes composer <cmd>`**: Wrapper executing composer inside app container.
- **`hermes npm <cmd>`**: Wrapper executing npm builds.
- **`hermes cache [clear|optimize|rebuild]`**: Laravel caches controls.
- **`hermes ai [test|chat|models|providers|embeddings|index|reindex|documents|prompt-test|benchmark]`**: AI integrations manager.
- **`hermes knowledge [import|export|rebuild|clean|statistics|search]`**: Knowledge base vectors controller.
- **`hermes backup [create|list|restore|verify|delete|schedule]`**: Archives databases and volume storages.
- **`hermes config`**: Interactive wizard credentials compiler.
- **`hermes module [list|enable|disable|install|remove|update]`**: Extension registry switches.
- **`hermes provider [list|switch|test|benchmark]`**: active LLM models driver switches.
- **`hermes doctor`**: Audits system ports, queries, and permissions.
- **`hermes repair`**: Fixes directories permissions, cache configurations, and queue states.
- **`hermes self-update`**: Pulls latest scripts without touching credentials.
- **`hermes test`**: Runs ShellCheck linters.
