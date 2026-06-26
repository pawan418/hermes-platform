# Hermes AI Enterprise Platform - Troubleshooting Guide

This guide details steps to isolate, diagnose, and resolve issues on the Hermes Platform.

---

## 1. Diagnostics Auditor (`hermes doctor`)

The first step in diagnosing any issue is running the deep diagnostics utility:

```bash
hermes doctor
```

This audits 11 core components:
1. **Docker Daemon Status**
2. **Docker Compose Operations**
3. **PostgreSQL SELECT 1 Query Execution**
4. **Redis Cache Store ping/pong**
5. **Qdrant Vector REST Collections**
6. **MinIO storage live check**
7. **Laravel routing health endpoint**
8. **Laravel Queue worker process count**
9. **Laravel Scheduler daemon process status**
10. **Filesystem read/write flags**
11. **OpenAI and SMTP connectivity**

---

## 2. Resolving Permissions Issues

If the diagnostic check reports permissions warnings (e.g., storage or caches are not writable), execute the repair command:

```bash
hermes repair
```

This utility automatically:
- Creates folders if missing (`storage/`, `bootstrap/cache/`, `uploads/`, `knowledge/`, `logs/`).
- Enforces permission mask `0775` on directories.
- Sets group ownership to `www-data` if the group is registered on the system.
- Enforces secure permission mask `0600` on the `.env` settings file.

---

## 3. Investigating Log Files

Hermes splits log streams into independent files to make debugging simple:

| Log File | Scope |
| :--- | :--- |
| **`logs/install.log`** | Installation actions and wizard configuration details |
| **`logs/upgrade.log`** | Repository pulls, container builds, and migrations |
| **`logs/docker.log`** | Output logs from Docker pulling, building, and starting |
| **`logs/health.log`** | Diagnostic doctor outputs and connection checks |
| **`logs/rollback.log`** | Database schema dumps and restoration tracks on failure |
| **`logs/error.log`** | Unified exceptions and critical script errors |

To stream logs live:
```bash
# Stream all container logs
hermes logs

# Stream queue workers logs
hermes logs queue

# Stream database logs
hermes logs db
```

---

## 4. Third-Party Integration Checks

If services fail to process AI request or emails:
- Run `hermes update` to review API keys, SMTP credentials, Google client IDs, and WhatsApp phone number IDs.
- The manager will run connection tests immediately when you apply changes.
