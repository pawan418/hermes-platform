# Hermes AI Enterprise Platform - Upgrade Guide

This guide covers how the Hermes Platform performs zero-downtime version upgrades.

---

## 1. Upgrade Engine Workflow

The upgrade process is fully automated. When executing `hermes upgrade`, the following sequence runs:

1. **System Threshold Audit**: Calls the diagnostics engine to verify system metrics and inodes are healthy before proceeding.
2. **Automated Backup Safeguard**: Triggers a full platform backup. If the backup fails, the upgrade halts, ensuring no state is altered.
3. **Repository Sync**: If a `.git` folder is detected, pulls code modifications from the origin branch.
4. **Image & Service Build**: Pulls base images and rebuilds the custom application container:
   ```bash
   docker compose build --pull
   ```
5. **Composer Autoload Optimizations**: Checks if the `vendor/` directory exists. If so, skips slow full installations, executing `composer dump-autoload` and `artisan optimize` for speed.
6. **Pre-Migration DB Snapshot**: Takes a pg_dump schema snapshot.
7. **Database Migration with Auto-Rollback**: Executes `artisan migrate`. If the migrations fail:
   - Drops the corrupted schema.
   - Instantly restores the pre-migration snapshot.
   - Halts installation to prevent partially migrated database states.
8. **Cache Compilations**: Re-compiles Laravel configurations, routes, and views.
9. **Diagnostics Verify**: Executes `hermes doctor`. If any component fails health validations, a full rollback runs to restore the pre-upgrade backup.

---

## 2. Executing an Upgrade

Ensure your workspace is clean, then run:

```bash
# Using global CLI
hermes upgrade

# Using root wrapper script
./upgrade.sh
```

All upgrade step logs are saved independently to `logs/upgrade.log` and `logs/docker.log`.
