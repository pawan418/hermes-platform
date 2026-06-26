# Hermes AI Enterprise Platform - Installation Guide

This guide details the system requirements and steps required to install the Hermes AI Platform using the unified `hermes` CLI.

---

## 1. System Requirements

The installation system automatically checks these specifications before beginning:

| Metric | Minimum Requirement | Recommendation |
| :--- | :--- | :--- |
| **Operating System** | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS (x86_64) |
| **CPU Cores** | 4 Cores | 8 Cores |
| **System Memory** | 8 GB RAM | 16 GB RAM |
| **Disk Storage** | 80 GB SSD | 120 GB SSD |
| **Root Inodes** | $\le 95\%$ usage | $\le 75\%$ usage |

Additionally, the installer verifies network latency and link connectivity to:
- `download.docker.com` (Docker repository)
- `github.com` (Source controls)
- `api.openai.com` (AI completions endpoint)

---

## 2. Installation Modes (Profiles)

You can choose between three environment presets during the wizard:

1. **Development**:
   - `APP_ENV=local` and `APP_DEBUG=true`
   - Bypasses Laravel config, route, and view caching to allow hot reloads.
2. **Production**:
   - `APP_ENV=production` and `APP_DEBUG=false`
   - Enables full Laravel optimizations and starts background Redis queue workers.
3. **Enterprise**:
   - Same optimizations as Production.
   - Restricts filesystem write scopes (security hardening) and schedules automated database backups.

---

## 3. Quickstart Installation

Run the installation script in the root directory:

```bash
# 1. Make installer executable
chmod +x installer/bin/hermes install.sh

# 2. Run the interactive installer
./install.sh
```

### Wizard Checklist
During setup, you will be prompted for:
- **Application URL**: The primary domain/URL used for routing (e.g. `http://hermes.example.com`).
- **Timezone**: Default timezone selector (defaults to `Asia/Kolkata`).
- **Admin account**: Email and password (support secure auto-generation).
- **Integrations**: Optional credentials for OpenAI, Google OAuth, Twilio Voice, Meta WhatsApp, and SMTP mail.

---

## 4. Register CLI Globally

Link the CLI to your local system path to manage Hermes from anywhere:

```bash
./installer/bin/hermes link
```

You can now run commands directly (e.g., `hermes doctor`, `hermes status`).
