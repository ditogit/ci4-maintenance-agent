# PRD — CI4 Maintenance Agent

## Lightweight Remote Management & Monitoring Agent for CodeIgniter 4

**Version:** 1.0  
**Status:** Product Requirement Document  
**Product Type:** Internal Composer Package / CI4 Module  
**Target Framework:** CodeIgniter 4  
**Runtime:** PHP 8.x+  
**Primary Database:** MySQL  
**Primary Consumer:** CI4 Project Hub  
**Deployment Model:** Installed independently in every CI4 project

---

# 1. Product Overview

**CI4 Maintenance Agent** adalah lightweight management agent yang dipasang pada setiap aplikasi CodeIgniter 4.

Agent menyediakan REST API terstandarisasi yang memungkinkan centralized management system seperti **CI4 Project Hub** untuk:

- mengetahui status aplikasi;
- mendapatkan informasi aplikasi;
- memonitor session;
- membersihkan expired session;
- mengetahui kondisi database;
- mengetahui penggunaan storage;
- menjalankan maintenance operation yang telah diizinkan;
- menyediakan telemetry dasar;
- mencatat aktivitas maintenance;
- melakukan authentication dan authorization secara aman.

Agent **tidak menjadi dashboard**.

Agent hanya bertanggung jawab sebagai:

```text
                    CI4 APPLICATION
                          │
                          │
                ┌─────────▼─────────┐
                │ CI4 Maintenance   │
                │ Agent             │
                └─────────┬─────────┘
                          │
                    Secure REST API
                          │
                          ▼
                    CI4 Project Hub
```

---

# 2. Problem Statement

Saat ini terdapat banyak project CodeIgniter 4 yang dikelola secara terpisah.

Contoh:

```text
Project A
Project B
Project C
Project D
Project E
Project F
...
```

Sebagian besar menggunakan:

```text
CodeIgniter 4
+
DatabaseHandler
+
MySQL
```

Salah satu permasalahan utama adalah session.

Contoh:

```text
ci4_sessions
-----------------------
500 rows
5,000 rows
50,000 rows
500,000 rows
```

Maintenance secara manual mengharuskan administrator:

```text
Login cPanel
    ↓
Open database
    ↓
Open phpMyAdmin
    ↓
Inspect ci4_sessions
    ↓
Cleanup
```

Jika project bertambah menjadi 20 atau 50, pendekatan ini tidak scalable.

---

# 3. Proposed Solution

Pasang agent standar di setiap project.

```text
Project A
└── Maintenance Agent

Project B
└── Maintenance Agent

Project C
└── Maintenance Agent
```

Setiap agent mempunyai API contract yang sama.

Contoh:

```text
GET  /api/maintenance/health
GET  /api/maintenance/info

GET  /api/maintenance/session/stats
POST /api/maintenance/session/cleanup

GET  /api/maintenance/database
GET  /api/maintenance/storage
```

Central Hub kemudian dapat mengelola semua project melalui API tersebut.

---

# 4. Product Vision

> **Create one standardized, secure and lightweight operational interface for every CodeIgniter 4 application managed by the organization.**

Agent harus:

- lightweight;
- secure;
- predictable;
- versioned;
- easy to install;
- easy to update;
- easy to disable;
- independent from the central dashboard.

---

# 5. Product Principles

## 5.1 Secure by Default

Agent harus dalam kondisi aman sejak pertama kali dipasang.

Tidak boleh:

```text
Public API
+
No authentication
```

---

## 5.2 Least Privilege

Agent hanya boleh melakukan operasi yang memang dibutuhkan.

Agent **tidak boleh menjadi remote shell**.

Tidak menyediakan:

```text
execute SQL
execute PHP
execute shell
read arbitrary files
write arbitrary files
```

---

## 5.3 Read Before Write

Operation monitoring bersifat read-only.

Operation maintenance bersifat explicit.

Contoh:

```text
GET session/stats
```

aman.

Sedangkan:

```text
POST session/cleanup
```

harus membutuhkan permission khusus.

---

## 5.4 API First

Agent tidak mempunyai UI.

Semua interaksi dilakukan melalui API.

```text
Agent
 └── REST API
```

---

## 5.5 Versioned API

Semua endpoint menggunakan API version.

Contoh:

```text
/api/maintenance/v1/health
```

atau:

```text
/api/v1/maintenance/health
```

Rekomendasi:

```text
/api/v1/maintenance/...
```

---

# 6. Target Users

Agent digunakan oleh:

### Primary

Developer / System Administrator

### Secondary

Central management platform

```text
CI4 Project Hub
```

End user aplikasi tidak mengetahui keberadaan agent.

---

# 7. Scope MVP

MVP harus fokus pada operasi berikut:

```text
Health
Application Info
Session Statistics
Session Cleanup
Session Clear
Database Statistics
Storage Statistics
Authentication
Authorization
Audit
Agent Configuration
```

---

# 8. Out of Scope MVP

Tidak termasuk:

```text
Remote shell
Arbitrary SQL
Arbitrary PHP execution
File manager
Deployment
Git operations
Server restart
OS package management
cPanel automation
Full log management
Backup orchestration
Queue management
```

Fitur tersebut dapat ditambahkan melalui fase berikutnya.

---

# 9. Architecture

## 9.1 Internal Architecture

```text
CodeIgniter 4
│
├── Routes
│
├── Maintenance Agent
│   │
│   ├── Controllers
│   │
│   ├── Authentication
│   │
│   ├── Authorization
│   │
│   ├── Services
│   │   ├── HealthService
│   │   ├── InfoService
│   │   ├── SessionService
│   │   ├── DatabaseService
│   │   └── StorageService
│   │
│   ├── Validators
│   ├── Filters
│   ├── Audit
│   └── Config
│
└── Existing Application
```

---

# 10. Composer Package

Agent idealnya dibuat sebagai Composer package.

Contoh:

```bash
composer require organization/ci4-maintenance-agent
```

Package:

```text
organization/ci4-maintenance-agent
```

Tujuannya agar semua project menggunakan source code yang sama.

---

# 11. Installation Flow

Setelah install:

```bash
composer require organization/ci4-maintenance-agent
```

jalankan:

```bash
php spark maintenance:install
```

Command melakukan:

```text
Check CI4
    ↓
Publish configuration
    ↓
Generate API credentials
    ↓
Create required database table
    ↓
Register routes
    ↓
Run security validation
```

Output:

```text
CI4 Maintenance Agent

✓ Package installed
✓ Configuration published
✓ API credentials generated
✓ Database ready
✓ Routes registered

Agent version: 1.0.0

Status: READY
```

---

# 12. Configuration

File:

```text
app/Config/Maintenance.php
```

atau konfigurasi `.env`.

Contoh:

```env
MAINTENANCE_ENABLED=true

MAINTENANCE_API_KEY=...
MAINTENANCE_API_SECRET=...

MAINTENANCE_ENVIRONMENT=production

MAINTENANCE_SESSION_STATS=true
MAINTENANCE_SESSION_CLEANUP=true
MAINTENANCE_SESSION_CLEAR=false

MAINTENANCE_DATABASE_STATS=true
MAINTENANCE_STORAGE_STATS=true
```

---

# 13. Feature Flags

Setiap operasi dapat dinonaktifkan.

```env
MAINTENANCE_SESSION_STATS=true
MAINTENANCE_SESSION_CLEANUP=true
MAINTENANCE_SESSION_CLEAR=false
MAINTENANCE_DATABASE_STATS=true
MAINTENANCE_STORAGE_STATS=true
```

Contoh:

Jika:

```text
MAINTENANCE_SESSION_CLEAR=false
```

request:

```text
POST /session/clear
```

harus menghasilkan:

```text
403 Forbidden
```

---

# 14. Authentication

Semua endpoint agent harus authenticated kecuali endpoint tertentu yang memang secara eksplisit dirancang public.

MVP:

```text
API Key
+
API Secret
```

Recommended:

```text
HMAC-SHA256
```

---

# 15. Request Authentication

Contoh request:

```http
GET /api/v1/maintenance/session/stats

Host: example.ac.id

X-Maintenance-Key: project_abc123
X-Maintenance-Timestamp: 1788820000
X-Maintenance-Nonce: random-value
X-Maintenance-Signature: signature
```

Signature:

```text
HMAC-SHA256(
    timestamp
    + nonce
    + HTTP method
    + request path
    + request body,
    API_SECRET
)
```

---

# 16. Timestamp Validation

Agent harus menolak request yang terlalu lama.

Contoh:

```text
Allowed clock skew:
± 5 minutes
```

Jika timestamp:

```text
10 minutes old
```

response:

```text
401 Unauthorized
```

Tujuannya mencegah replay attack.

---

# 17. Nonce

Setiap request dapat memiliki nonce.

Contoh:

```text
X-Maintenance-Nonce:
a91f82c9...
```

Agent dapat menggunakan nonce untuk mencegah request replay.

Untuk MVP sederhana, timestamp + signature dapat digunakan terlebih dahulu.

---

# 18. Authorization

Authentication:

> Siapa yang melakukan request?

Authorization:

> Operasi apa yang boleh dilakukan?

Permission:

```text
maintenance.health.read
maintenance.info.read

maintenance.session.read
maintenance.session.cleanup
maintenance.session.clear

maintenance.database.read
maintenance.storage.read
```

---

# 19. API Endpoint Overview

MVP:

```text
GET  /api/v1/maintenance/health
GET  /api/v1/maintenance/info

GET  /api/v1/maintenance/session/stats
POST /api/v1/maintenance/session/cleanup
POST /api/v1/maintenance/session/clear

GET  /api/v1/maintenance/database
GET  /api/v1/maintenance/storage
```

---

# 20. Health API

Endpoint:

```http
GET /api/v1/maintenance/health
```

Purpose:

Mengetahui apakah aplikasi dan agent dapat diakses.

Response:

```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "agent": "healthy",
    "application": "healthy",
    "database": "healthy",
    "timestamp": "2026-09-08T09:30:00+07:00"
  }
}
```

---

# 21. Health Status

Status:

```text
healthy
warning
critical
offline
```

Contoh:

```text
Application:
healthy

Database:
warning

Agent:
healthy
```

---

# 22. Health Response Time

Hub dapat menghitung:

```text
request started
        ↓
API response
        ↓
calculate duration
```

Contoh:

```text
response_ms: 42
```

Agent juga dapat mengembalikan execution time:

```json
{
  "response_ms": 18
}
```

---

# 23. Application Info API

Endpoint:

```http
GET /api/v1/maintenance/info
```

Response:

```json
{
  "success": true,
  "data": {
    "application": "Teknik Lingkungan",
    "environment": "production",
    "ci_version": "4.x",
    "php_version": "8.3",
    "database": "MySQL",
    "session_driver": "DatabaseHandler",
    "agent_version": "1.0.0"
  }
}
```

---

# 24. Information Security

Jangan mengembalikan:

```text
API Secret
Database password
Application encryption key
Environment secrets
Private keys
```

Endpoint info hanya mengembalikan metadata yang aman.

---

# 25. Session Statistics API

Endpoint:

```http
GET /api/v1/maintenance/session/stats
```

Response:

```json
{
  "success": true,
  "data": {
    "driver": "DatabaseHandler",
    "table": "ci4_sessions",
    "total": 18432,
    "expired": 15230,
    "active": 3202
  }
}
```

---

# 26. Session Expiration Calculation

Agent harus membaca konfigurasi session aplikasi.

Jangan mengasumsikan:

```text
7 days
```

atau:

```text
30 days
```

secara hard-coded.

Perhitungan:

```text
current timestamp
        -
session expiration
```

Harus konsisten dengan konfigurasi CI4.

---

# 27. Session Database Size

Jika memungkinkan:

```text
session rows
session table size
```

Response:

```json
{
  "total": 18432,
  "expired": 15230,
  "active": 3202,
  "size_mb": 48.32
}
```

Jika database engine tidak mendukung informasi size:

```text
size_mb: null
```

Jangan menganggap size selalu tersedia.

---

# 28. Session Cleanup API

Endpoint:

```http
POST /api/v1/maintenance/session/cleanup
```

Tujuan:

Menghapus session yang sudah expired.

---

# 29. Cleanup Request

Request:

```json
{
  "mode": "expired"
}
```

MVP hanya mendukung:

```text
expired
```

Future:

```text
older_than
```

tetapi jangan terlalu fleksibel pada MVP.

---

# 30. Cleanup Response

```json
{
  "success": true,
  "data": {
    "deleted": 15230,
    "remaining": 3202,
    "duration_ms": 183
  }
}
```

---

# 31. Cleanup Safety

Cleanup harus:

- hanya menghapus expired sessions;
- tidak menghapus active sessions;
- menggunakan parameterized query;
- tidak menerima arbitrary SQL;
- mencatat audit;
- mempunyai permission khusus.

---

# 32. Session Clear API

Endpoint:

```http
POST /api/v1/maintenance/session/clear
```

Tujuan:

Menghapus seluruh session.

Ini adalah **dangerous operation**.

---

# 33. Clear Session Configuration

Default:

```env
MAINTENANCE_SESSION_CLEAR=false
```

Harus diaktifkan secara eksplisit.

---

# 34. Clear Session Response

```json
{
  "success": true,
  "data": {
    "deleted": 18432,
    "warning": "All application sessions were invalidated"
  }
}
```

---

# 35. Database API

Endpoint:

```http
GET /api/v1/maintenance/database
```

Response:

```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "driver": "MySQL",
    "connection": "healthy",
    "response_ms": 32,
    "database_size_mb": 1820
  }
}
```

MVP jangan menyediakan arbitrary database query.

---

# 36. Storage API

Endpoint:

```http
GET /api/v1/maintenance/storage
```

Response:

```json
{
  "success": true,
  "data": {
    "total_gb": 20,
    "used_gb": 14.4,
    "free_gb": 5.6,
    "used_percent": 72
  }
}
```

Jika shared hosting tidak memungkinkan mendapatkan informasi filesystem:

```text
status:
unsupported
```

bukan error palsu.

---

# 37. Standard API Response

Semua endpoint harus memiliki format konsisten.

Success:

```json
{
  "success": true,
  "data": {}
}
```

Error:

```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Invalid authentication"
  }
}
```

---

# 38. Standard Error Codes

```text
UNAUTHORIZED
FORBIDDEN
NOT_FOUND
VALIDATION_ERROR
AGENT_DISABLED
FEATURE_DISABLED
DATABASE_ERROR
MAINTENANCE_ERROR
RATE_LIMITED
TIMESTAMP_EXPIRED
INVALID_SIGNATURE
INTERNAL_ERROR
UNSUPPORTED
```

---

# 39. HTTP Status Codes

```text
200 OK
201 Created
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
409 Conflict
422 Unprocessable Entity
429 Too Many Requests
500 Internal Server Error
503 Service Unavailable
```

---

# 40. Rate Limiting

MVP:

```text
Read endpoints:
60 requests/minute

Maintenance endpoints:
10 requests/minute
```

Clear:

```text
5 requests/minute
```

Rate limit dapat dikonfigurasi.

---

# 41. IP Allowlist

Optional feature.

Configuration:

```env
MAINTENANCE_IP_ALLOWLIST=
```

Contoh:

```text
203.0.113.10
203.0.113.11
```

Jika diaktifkan:

```text
Request
 ↓
IP check
 ↓
Authentication
```

IP allowlist menjadi lapisan keamanan tambahan, bukan satu-satunya authentication.

---

# 42. Agent Audit Log

Agent harus mencatat operasi penting.

Minimal:

```text
Authentication failure
Session cleanup
Session clear
Configuration change
```

Audit:

```text
timestamp
action
status
affected_rows
request_id
source_ip
```

---

# 43. Request ID

Setiap request diberi ID.

Contoh:

```text
X-Request-ID:
mnt_01J...
```

Response:

```json
{
  "success": true,
  "request_id": "mnt_01J..."
}
```

Tujuannya mempermudah debugging.

---

# 44. Logging

Jangan mencatat:

```text
API Secret
Signature
Password
Database Password
```

Log hanya:

```text
Request ID
Endpoint
Method
Status
Duration
Action
IP
```

---

# 45. Database Tables

MVP sebaiknya **tidak membuat banyak tabel**.

Minimal agent dapat berjalan tanpa database tambahan.

Untuk audit lokal, gunakan:

```text
application log
```

atau tabel optional:

```text
maintenance_audit_logs
```

Jika menggunakan tabel:

```text
maintenance_audit_logs

id
request_id
action
status
affected_rows
ip_address
metadata
created_at
```

---

# 46. Why Avoid Agent Database Tables?

Karena agent harus:

```text
Lightweight
Easy to install
Easy to remove
Low maintenance
```

Jangan sampai agent sendiri menjadi sumber maintenance baru.

---

# 47. Route Structure

Recommended:

```text
Routes
└── /api/v1/maintenance
      ├── /health
      ├── /info
      ├── /session
      │     ├── /stats
      │     ├── /cleanup
      │     └── /clear
      ├── /database
      └── /storage
```

---

# 48. Controller Structure

```text
Maintenance/
├── Controllers/
│   └── Api/
│       ├── HealthController.php
│       ├── InfoController.php
│       ├── SessionController.php
│       ├── DatabaseController.php
│       └── StorageController.php
```

Jangan membuat satu controller raksasa:

```text
MaintenanceController.php
```

untuk seluruh operasi.

---

# 49. Service Structure

```text
Maintenance/
└── Services/
    ├── HealthService.php
    ├── InfoService.php
    ├── SessionService.php
    ├── DatabaseService.php
    └── StorageService.php
```

Business logic berada di service.

Controller hanya:

```text
Request
 ↓
Validate
 ↓
Authorize
 ↓
Service
 ↓
Response
```

---

# 50. Security Filter

Request flow:

```text
HTTP Request
     ↓
HTTPS
     ↓
IP Allowlist
     ↓
Rate Limit
     ↓
Authentication
     ↓
Timestamp
     ↓
Signature
     ↓
Authorization
     ↓
Controller
     ↓
Service
```

---

# 51. Session Service

SessionService bertanggung jawab:

```text
getStats()
cleanupExpired()
clearAll()
```

Tidak boleh:

```text
executeRawUserSQL()
```

---

# 52. Database Service

DatabaseService:

```text
checkConnection()
getDatabaseSize()
getResponseTime()
```

Tidak:

```text
executeQuery($userInput)
```

---

# 53. Storage Service

StorageService:

```text
getDiskUsage()
```

Tidak menyediakan:

```text
deleteFile($path)
```

pada MVP.

---

# 54. Agent CLI Commands

Agent menyediakan CLI commands.

### Install

```bash
php spark maintenance:install
```

### Status

```bash
php spark maintenance:status
```

Output:

```text
Maintenance Agent

Status: Enabled
Version: 1.0.0
Session: Enabled
Database: Enabled
Storage: Enabled
```

### Disable

```bash
php spark maintenance:disable
```

### Enable

```bash
php spark maintenance:enable
```

---

# 55. Credential Rotation

Agent harus mendukung API credential rotation.

Command:

```bash
php spark maintenance:rotate-key
```

Output:

```text
API credentials rotated.

Old credentials are now invalid.
```

Dashboard harus menyediakan mekanisme update credential.

---

# 56. Emergency Disable

Harus tersedia cara cepat mematikan agent.

`.env`:

```env
MAINTENANCE_ENABLED=false
```

Semua endpoint:

```text
503 Service Unavailable
```

atau:

```text
404
```

tergantung security strategy.

Recommended:

```text
503
```

untuk internal diagnostics, tetapi jangan membocorkan detail sensitif.

---

# 57. Agent Version

Agent harus mempunyai version:

```text
1.0.0
```

Format:

```text
Semantic Versioning
```

Contoh:

```text
1.0.0
1.1.0
1.1.1
2.0.0
```

---

# 58. API Version vs Agent Version

Keduanya berbeda.

Contoh:

```text
Agent:
1.4.2

API:
v1
```

Agent dapat berubah:

```text
1.4.2
→
1.5.0
```

tetapi API tetap:

```text
v1
```

---

# 59. Compatibility

Central Hub harus mengetahui:

```text
Agent Version
API Version
```

Contoh:

```text
Project A
Agent 1.0.0
API v1
✓ Supported

Project B
Agent 0.8.0
API v1
⚠ Upgrade recommended
```

---

# 60. Maintenance Agent Self Check

Endpoint:

```text
GET /health
```

harus memeriksa minimal:

```text
Agent loaded
Configuration valid
Database accessible
Required services available
```

Jangan melakukan operasi berat pada health endpoint.

---

# 61. Performance Requirements

Agent harus lightweight.

Target:

```text
Health API:
< 500ms

Info API:
< 500ms

Session Stats:
< 1s untuk database normal
```

Cleanup tidak memiliki target fixed karena bergantung jumlah record.

---

# 62. Large Session Table Handling

Jika:

```text
ci4_sessions
=
1,000,000+ rows
```

cleanup tidak boleh selalu melakukan satu DELETE raksasa jika berpotensi menyebabkan:

- lock panjang;
- timeout;
- excessive memory;
- database overload.

Implementasikan batch deletion.

Contoh konsep:

```text
DELETE 5,000 rows
        ↓
repeat
        ↓
until finished
```

Ukuran batch configurable.

```env
MAINTENANCE_SESSION_CLEANUP_BATCH=5000
```

---

# 63. Cleanup Transaction Strategy

Jangan menggunakan transaction besar untuk seluruh jutaan records.

Lebih baik:

```text
Batch
 ↓
Commit
 ↓
Batch
 ↓
Commit
```

Tujuannya mengurangi lock duration.

---

# 64. Cleanup Timeout

Cleanup harus mempunyai batas waktu.

Contoh:

```env
MAINTENANCE_SESSION_CLEANUP_TIMEOUT=30
```

Jika timeout:

```json
{
  "success": false,
  "error": {
    "code": "MAINTENANCE_TIMEOUT"
  },
  "data": {
    "deleted": 15000
  }
}
```

---

# 65. Concurrent Cleanup Protection

Jangan izinkan:

```text
Cleanup request #1
        +
Cleanup request #2
        +
Cleanup request #3
```

secara bersamaan.

Gunakan locking mechanism.

Concept:

```text
cleanup lock
     ↓
already running?
     ↓
YES → 409 CONFLICT
```

---

# 66. Maintenance Lock

Example:

```text
session_cleanup
    ↓
Acquire lock
    ↓
Execute
    ↓
Release lock
```

Jika gagal mendapatkan lock:

```text
409 Conflict
```

Response:

```json
{
  "success": false,
  "error": {
    "code": "MAINTENANCE_IN_PROGRESS"
  }
}
```

---

# 67. Clear All Protection

Clear All harus mempunyai protection tambahan.

Recommended permission:

```text
maintenance.session.clear
```

dan:

```text
MAINTENANCE_SESSION_CLEAR=false
```

by default.

Dengan demikian ada dua lapisan:

```text
Configuration
+
Authorization
```

---

# 68. Production Safety

Agent harus mengetahui environment:

```text
production
staging
development
```

Jika production:

```text
Clear All
```

lebih ketat.

Contoh:

```text
Production:
disabled by default

Staging:
enabled

Development:
enabled
```

---

# 69. API Documentation

Package harus menyediakan dokumentasi:

```text
docs/
├── installation.md
├── configuration.md
├── authentication.md
├── authorization.md
├── api.md
├── session.md
└── security.md
```

---

# 70. OpenAPI

Fase berikutnya sebaiknya menyediakan:

```text
openapi.yaml
```

Dengan demikian CI4 Project Hub dapat mengintegrasikan API berdasarkan contract yang jelas.

---

# 71. API Example

```yaml
GET /api/v1/maintenance/session/stats

Responses:
  200:
    description: Session statistics

  401:
    description: Unauthorized

  403:
    description: Forbidden

  503:
    description: Agent disabled
```

---

# 72. Testing Strategy

## Unit Test

Test:

```text
SessionService
HealthService
DatabaseService
StorageService
AuthenticationService
AuthorizationService
```

---

# 73. Integration Test

Test:

```text
Hub
 ↓
HTTPS
 ↓
Agent
 ↓
CI4
 ↓
MySQL
```

---

# 74. Security Test

Wajib menguji:

```text
Invalid API key
Invalid secret
Invalid signature
Expired timestamp
Replay request
Missing headers
Wrong permission
Rate limit
IP blocked
Disabled agent
```

---

# 75. Session Test

Test:

```text
0 sessions
10 sessions
10,000 sessions
100,000 sessions
Expired sessions
Active sessions
Mixed sessions
Concurrent cleanup
```

---

# 76. Failure Handling

Jika database mati:

```text
GET /health
```

harus:

```text
database: unhealthy
```

bukan membuat seluruh agent crash.

---

# 77. Database Failure Response

```json
{
  "success": true,
  "data": {
    "status": "degraded",
    "database": "unhealthy"
  }
}
```

Health endpoint tetap memberikan informasi sebisa mungkin.

---

# 78. Agent Upgrade Strategy

Package diupdate menggunakan Composer.

```bash
composer update organization/ci4-maintenance-agent
```

Migration harus backward compatible.

---

# 79. Backward Compatibility

Perubahan minor:

```text
1.0
→
1.1
```

tidak boleh merusak API v1.

Breaking change:

```text
2.0
```

harus menggunakan API version baru:

```text
/api/v2/maintenance
```

---

# 80. Rollback

Jika update agent bermasalah:

```bash
composer require organization/ci4-maintenance-agent:1.0.0
```

Pastikan setiap project dapat kembali ke versi agent sebelumnya.

---

# 81. Installation Documentation

Dokumentasi harus menjelaskan:

```text
1. Install package
2. Run install command
3. Generate credentials
4. Configure environment
5. Test health endpoint
6. Register project to Hub
```

---

# 82. Onboarding Flow

Ideal:

```text
Install Agent
      ↓
Generate Credentials
      ↓
Copy API Key
      ↓
Copy API Secret
      ↓
Open CI4 Hub
      ↓
Add Project
      ↓
Test Connection
      ↓
Project Online
```

Target onboarding:

```text
< 5 minutes
```

---

# 83. Project Registration Metadata

Agent dapat menyediakan metadata:

```text
application_name
environment
agent_version
api_version
ci_version
php_version
session_driver
```

Hub menggunakan data ini saat project onboarding.

---

# 84. Agent Discovery

Future feature:

Agent menghasilkan:

```text
maintenance-manifest.json
```

atau CLI:

```bash
php spark maintenance:info
```

Output:

```text
Application:
Teknik Lingkungan

Agent:
1.0.0

API:
v1

Environment:
production
```

---

# 85. Health Heartbeat

Future feature:

Agent dapat menerima heartbeat request dari Hub.

```text
Hub
 ↓
Agent
 ↓
Response
```

Hub menyimpan:

```text
last_seen_at
```

Contoh:

```text
Last seen:
12 seconds ago
```

---

# 86. Maintenance Job Model

Future architecture:

```text
Hub
 ↓
Create maintenance job
 ↓
Agent
 ↓
Execute
 ↓
Return job ID
 ↓
Poll status
```

Ini lebih cocok untuk operasi berat.

Contoh:

```text
Session cleanup 1,000,000 rows
```

tidak harus menunggu HTTP request sampai selesai.

---

# 87. Async Maintenance

Phase advanced:

```text
POST /session/cleanup
```

response:

```json
{
  "success": true,
  "job_id": "job_123"
}
```

Kemudian:

```text
GET /maintenance/jobs/job_123
```

Response:

```json
{
  "status": "running",
  "progress": 64,
  "deleted": 640000
}
```

---

# 88. Recommended MVP vs Future

| Feature | MVP | Future |
|---|---:|---:|
| Health | ✓ | |
| App Info | ✓ | |
| Session Stats | ✓ | |
| Session Cleanup | ✓ | |
| Clear All Session | ✓ | |
| Database Stats | ✓ | |
| Storage Stats | ✓ | |
| Authentication | ✓ | |
| HMAC | ✓ | |
| Authorization | ✓ | |
| Rate Limit | ✓ | |
| Audit | ✓ | |
| IP Allowlist | Optional | ✓ |
| Async Job | | ✓ |
| Queue | | ✓ |
| Backup | | ✓ |
| Cache | | ✓ |
| Logs | | ✓ |
| Deployment | | ✓ |

---

# 89. Future Maintenance Modules

Agent dapat berkembang menggunakan modular architecture.

```text
Maintenance Agent
│
├── Core
│
├── Session Module
├── Database Module
├── Storage Module
├── Cache Module
├── Log Module
├── Backup Module
├── Queue Module
└── Deployment Module
```

Module dapat diaktifkan/nonaktifkan.

---

# 90. Future Cache API

```text
GET  /api/v1/maintenance/cache/stats
POST /api/v1/maintenance/cache/clear
```

---

# 91. Future Log API

```text
GET /api/v1/maintenance/logs
```

Dengan:

```text
level
date
limit
cursor
```

Tidak boleh memberikan akses arbitrary filesystem.

---

# 92. Future Backup API

```text
POST /api/v1/maintenance/backup/database
GET  /api/v1/maintenance/backup/status
```

Backup harus asynchronous.

---

# 93. Future Queue API

```text
GET /api/v1/maintenance/queue/stats
POST /api/v1/maintenance/queue/retry
```

---

# 94. Future Deployment API

Tidak disarankan dimasukkan ke MVP.

Jika nanti diperlukan:

```text
POST /api/v1/maintenance/deploy
```

harus memiliki security model jauh lebih ketat.

---

# 95. Security Threat Model

Threat utama:

### Credential theft

Mitigation:

```text
HTTPS
HMAC
credential rotation
secret storage
```

### Replay attack

Mitigation:

```text
Timestamp
Nonce
```

### Unauthorized cleanup

Mitigation:

```text
Permission
Feature flag
Audit
```

### Brute force

Mitigation:

```text
Rate limit
```

### Endpoint abuse

Mitigation:

```text
Allowlisted operations
No arbitrary command execution
```

---

# 96. Do Not Build This

Agent **jangan pernah** memiliki API seperti:

```text
POST /execute
POST /shell
POST /sql
POST /php
POST /file
```

Contoh buruk:

```json
{
  "command": "rm -rf ..."
}
```

atau:

```json
{
  "sql": "DELETE FROM ..."
}
```

Agent harus menyediakan **specific business operations**, bukan generic remote execution.

---

# 97. Observability

Setiap operation harus mempunyai:

```text
request_id
timestamp
duration
status
```

Contoh:

```text
Request:
mnt_123

Operation:
SESSION_CLEANUP

Duration:
183ms

Deleted:
15,230

Status:
SUCCESS
```

---

# 98. Agent Metrics

Future:

```text
maintenance_requests_total
maintenance_request_duration
maintenance_errors_total
session_cleanup_total
session_cleanup_deleted
```

Metrics dapat dikirim ke Hub.

---

# 99. MVP Acceptance Criteria

Agent dianggap siap production jika:

### Installation

- Composer install berhasil.
- CLI installer berhasil.
- Configuration dapat dibuat.
- Credential dapat digenerate.

### Security

- Endpoint tanpa credential ditolak.
- Signature salah ditolak.
- Timestamp expired ditolak.
- Rate limit bekerja.
- Permission bekerja.

### Health

- Health endpoint berhasil.
- Database status dapat diperiksa.

### Session

- Total session benar.
- Expired session benar.
- Cleanup hanya menghapus expired session.
- Clear all hanya berjalan jika diaktifkan.
- Concurrent cleanup ditolak.

### Storage

- Storage usage dapat diperoleh jika environment mendukung.

### Operations

- Semua maintenance operation mempunyai audit.
- Error response konsisten.
- Request ID tersedia.

---

# 100. Definition of Done

Agent MVP harus memiliki:

```text
✓ Composer Package
✓ CI4 Integration
✓ Configuration
✓ CLI Installer
✓ API Authentication
✓ HMAC
✓ Authorization
✓ Rate Limiting
✓ Health API
✓ Info API
✓ Session Stats API
✓ Session Cleanup API
✓ Session Clear API
✓ Database Stats API
✓ Storage Stats API
✓ Audit
✓ Logging
✓ Error Handling
✓ API Versioning
✓ Unit Tests
✓ Integration Tests
✓ Security Tests
✓ API Documentation
✓ OpenAPI Specification
```

---

# 101. Recommended Package Structure

Struktur awal yang direkomendasikan:

```text
ci4-maintenance-agent/
│
├── src/
│   │
│   ├── Config/
│   │   └── Maintenance.php
│   │
│   ├── Controllers/
│   │   └── Api/
│   │       ├── HealthController.php
│   │       ├── InfoController.php
│   │       ├── SessionController.php
│   │       ├── DatabaseController.php
│   │       └── StorageController.php
│   │
│   ├── Services/
│   │   ├── HealthService.php
│   │   ├── InfoService.php
│   │   ├── SessionService.php
│   │   ├── DatabaseService.php
│   │   └── StorageService.php
│   │
│   ├── Filters/
│   │   ├── AuthenticationFilter.php
│   │   ├── AuthorizationFilter.php
│   │   └── RateLimitFilter.php
│   │
│   ├── Security/
│   │   ├── SignatureService.php
│   │   ├── TimestampValidator.php
│   │   └── NonceService.php
│   │
│   ├── Commands/
│   │   ├── InstallCommand.php
│   │   ├── StatusCommand.php
│   │   ├── EnableCommand.php
│   │   ├── DisableCommand.php
│   │   └── RotateKeyCommand.php
│   │
│   ├── Models/
│   │   └── MaintenanceAuditModel.php
│   │
│   └── Routes.php
│
├── tests/
│   ├── Unit/
│   ├── Feature/
│   └── Security/
│
├── docs/
│   ├── installation.md
│   ├── configuration.md
│   ├── authentication.md
│   ├── authorization.md
│   ├── api.md
│   └── security.md
│
├── openapi.yaml
├── composer.json
├── LICENSE
└── README.md
```

---

# 102. Development Phases

## Phase A — Core

```text
Package
Configuration
Routes
CLI installer
Health
Info
```

---

## Phase B — Security

```text
API Key
HMAC
Timestamp
Authorization
Rate limit
```

---

## Phase C — Session

```text
Session stats
Expired calculation
Cleanup
Clear all
Locking
Batch deletion
```

---

## Phase D — System Information

```text
Database
Storage
PHP
CI4
Environment
```

---

## Phase E — Operational Quality

```text
Audit
Logging
Request ID
Error handling
Tests
Documentation
```

---

# 103. Recommended MVP Release

Version:

```text
ci4-maintenance-agent
v1.0.0
```

API:

```text
v1
```

Capabilities:

```text
Health
Info
Session
Database
Storage
```

Security:

```text
HMAC
Timestamp
Authorization
Rate limit
```

---

# 104. Integration With CI4 Project Hub

Final architecture:

```text
                       CI4 PROJECT HUB
                              │
                         HTTPS / HMAC
                              │
          ┌───────────────────┼───────────────────┐
          │                   │                   │
          ▼                   ▼                   ▼
     Project A            Project B            Project C
          │                   │                   │
     CI4 Agent             CI4 Agent             CI4 Agent
          │                   │                   │
          ▼                   ▼                   ▼
      MySQL A              MySQL B              MySQL C
```

Hub hanya mengetahui:

```text
Project URL
API Key
API Secret
Agent Version
```

Hub tidak membutuhkan:

```text
Database Host
Database Username
Database Password
cPanel Password
SSH Password
```

---

# 105. Final Product Definition

**CI4 Maintenance Agent** adalah:

> Lightweight, secure, versioned REST API agent yang dipasang pada setiap aplikasi CodeIgniter 4 untuk menyediakan standardized monitoring dan maintenance capabilities kepada centralized CI4 Project Hub.

Agent bukan:

```text
Dashboard
Remote shell
Database manager
cPanel replacement
```

Agent adalah:

```text
Operational API Layer
```

yang berada di dalam setiap project.

---

# 106. Long-Term Architecture

Pada akhirnya:

```text
                     CI4 PROJECT HUB
                            │
                     Central Control
                            │
               ┌────────────┼────────────┐
               │            │            │
            Monitor      Maintain     Automate
               │            │            │
               └────────────┼────────────┘
                            │
                    Maintenance API
                            │
       ┌────────────────────┼────────────────────┐
       │                    │                    │
       ▼                    ▼                    ▼
   CI4 Agent            CI4 Agent            CI4 Agent
       │                    │                    │
   Project A             Project B             Project C
       │                    │                    │
      DB                   DB                   DB
```

Dengan pendekatan ini, **setiap project CI4 menjadi "managed node"**, sedangkan CI4 Project Hub menjadi **control plane**.

Inilah fondasi yang memungkinkan sistem berkembang dari sekadar session cleanup menjadi platform centralized operations untuk seluruh aplikasi CodeIgniter 4.