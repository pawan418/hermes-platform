# Hermes AI Enterprise Platform - Backup Operations

This document describes how to execute and automate platform backups.

---

## 1. Scope of Backups

The `hermes backup` command creates a single, self-contained gzip archive (`backups/hermes_backup_YYYYMMDD_HHMMSS.tar.gz`) containing:

1. **PostgreSQL Relational Database**: Creates an SQL database dump using `pg_dump` within the active database container.
2. **Docker Named Volumes**: Mounts named volumes to an isolated Alpine helper container and archives:
   - `hermes-redis` (Key-value cache and queue records)
   - `hermes-minio` (S3 object store storage files)
   - `hermes-qdrant` (AI vector databases)
   - `hermes-n8n` (Workflow execution databases)
3. **Local Host Directories**:
   - `uploads/` (User uploads)
   - `knowledge/` (Knowledge base text/files)
4. **System Configurations**: Captures the `.env` settings file.

---

## 2. Running a Manual Backup

Run the backup utility from anywhere if linked, or locally in the root:

```bash
# Using global CLI
hermes backup

# Using root wrapper script
./backup.sh
```

The output summary details the absolute path and size of the archive:
```
✓ Active project resolved: hermes
[1/8] Dumping PostgreSQL database...
✓ Database dump finished.
[2/8] Archiving Redis cache storage volume...
...
✓ Backup Created Successfully!
Archive File:      backups/hermes_backup_20260627_000000.tar.gz
Archive Size:      42MB
Restore Command:   hermes restore backups/hermes_backup_20260627_000000.tar.gz
```

---

## 3. Automation (Cron Job)

To configure automated nightly backups (e.g., at 2:00 AM), edit the root system crontab:

```bash
sudo crontab -e
```

Append the command:
```cron
0 2 * * * /usr/local/bin/hermes backup >> /var/www/hermes/logs/backup.log 2>&1
```
Ensure `/usr/local/bin/hermes` exists by running `hermes link` first.
