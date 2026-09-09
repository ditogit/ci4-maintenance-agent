# ci4-maintenance-agent

Lightweight, secure, versioned REST API agent for every CodeIgniter 4 app — standardized monitoring & maintenance for **CI4 Project Hub**.

## Install

```bash
composer require ditogit/ci4-agent
php spark maintenance:install
```

Publishes `app/Config/Maintenance.php`, appends `MAINTENANCE_API_KEY/SECRET` to `.env`, migrates `maintenance_audit_logs`.

## Configure (.env)

```env
MAINTENANCE_ENABLED=true
MAINTENANCE_API_KEY=project_xxx
MAINTENANCE_API_SECRET=yyy
MAINTENANCE_ENVIRONMENT=production
MAINTENANCE_SESSION_STATS=true
MAINTENANCE_SESSION_CLEANUP=true
MAINTENANCE_SESSION_CLEAR=false
MAINTENANCE_DATABASE_STATS=true
MAINTENANCE_STORAGE_STATS=true
MAINTENANCE_CACHE_CLEAR=true
MAINTENANCE_LOG_VIEWER=true
MAINTENANCE_BACKUP=false
MAINTENANCE_QUEUE=false
MAINTENANCE_IP_ALLOWLIST=
MAINTENANCE_SESSION_CLEANUP_BATCH=5000
MAINTENANCE_SESSION_CLEANUP_TIMEOUT=30
```

## API

Prefix ` /api/v1/maintenance` — HMAC auth required.

| Method | Path | Permission |
|--------|------|------------|
| GET | /health | maintenance.health.read |
| GET | /info | maintenance.info.read |
| GET | /session/stats | maintenance.session.read |
| POST | /session/cleanup | maintenance.session.cleanup |
| POST | /session/clear | maintenance.session.clear |
| GET | /database | maintenance.database.read |
| GET | /storage | maintenance.storage.read |
| POST | /cache/clear | maintenance.cache.clear |
| GET | /logs | maintenance.logs.read |
| POST | /logs/clear | maintenance.logs.clear |
| POST | /backup | maintenance.backup.create |
| GET | /queue | maintenance.queue.read |
| POST | /queue/retry | maintenance.queue.retry |
| POST | /queue/clear | maintenance.queue.clear |

Headers:
```
X-Maintenance-Key: project_abc123
X-Maintenance-Timestamp: 1788820000
X-Maintenance-Nonce: random
X-Maintenance-Signature: HMAC-SHA256(timestamp+nonce+METHOD+path+body, secret)
```

## CLI

```bash
php spark maintenance:status
php spark maintenance:enable
php spark maintenance:disable
php spark maintenance:rotate-key
```

Emergency disable: `MAINTENANCE_ENABLED=false` → 503.

## Security

- HMAC-SHA256, ±5min clock skew, nonce replay guard, IP allowlist, rate limit (60/10/5 req/min), feature flags, audit log.

## License

MIT
