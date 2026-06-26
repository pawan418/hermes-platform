# Hermes AI Enterprise Platform - Restore Operations

This guide covers how to restore the database, directories, and volumes from a backup archive.

---

## 1. Safety Information

> [!CAUTION]
> Restoring a backup is a destructive action. It will overwrite all configuration settings, wipe named Docker volumes, reset local upload/knowledge directories, and drop/recreate active database schemas.
> Always run `hermes backup` before executing a restore operation.

---

## 2. Restoration Process

Run the restore command by providing the path to a valid `.tar.gz` backup archive:

```bash
# Using global CLI
hermes restore backups/hermes_backup_YYYYMMDD_HHMMSS.tar.gz

# Using root wrapper script
./restore.sh backups/hermes_backup_YYYYMMDD_HHMMSS.tar.gz
```

### Unattended Restores (CI/CD / scripts)
To bypass the interactive confirmation prompt, append the `--force` flag:

```bash
hermes restore backups/hermes_backup_YYYYMMDD_HHMMSS.tar.gz --force
```

---

## 3. Under the Hood

The restoration script operates in 7 sequential phases:
1. **Integrity Audit**: Validates that all files (`database.sql`, named volume archives, `.env`) exist inside the backup package.
2. **Infrastructure Shutdown**: Runs `docker compose down` to release file locks on named volumes.
3. **Volume Overwrite**: Mounts Docker named volumes (`hermes-redis`, `hermes-minio`, `hermes-qdrant`, `hermes-n8n`) to Alpine containers, cleans out old contents, and extracts the backed-up tarballs.
4. **Host Directory Restoration**: Clears out and unpacks the host `uploads/` and `knowledge/` directories.
5. **Configuration Restore**: Copies the archived `.env` settings file.
6. **Database Refresh**:
   - Power-ups database container.
   - Executes `DROP DATABASE` and `CREATE DATABASE` to guarantee a clean slate.
   - Loads the SQL database dump.
7. **Diagnostics Verify**: Starts the remaining container services and executes `hermes doctor` to ensure the platform is healthy.
