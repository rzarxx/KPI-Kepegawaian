# ARCHITECTURE — KPI Kepegawaian

## 1. Architectural Style

Gunakan **Modular Monolith**.

Alasan:
- scope belum memerlukan microservices
- deployment sederhana
- transactional consistency lebih mudah
- maintenance lebih murah
- cocok dengan Laravel

## 2. High Level

```text
Browser / Installed PWA
        |
      HTTPS
        |
   Cloudflare (optional)
        |
      Nginx
        |
   Laravel 13
        |
  Inertia.js
        |
React + TypeScript
        |
Application Services
        |
Domain Rules
        |
MySQL 8
```

Async:
```text
Laravel App
  |
 Queue
  |
Supervisor Worker
```

Scheduler:
```text
Cron
 |
artisan schedule:run
```

## 3. Modules

### Identity & Access
- users
- roles
- permissions
- organizational scopes

### Organization
- branches
- divisions
- sub divisions
- positions

### Employee
- employees
- assignments
- status history
- documents

### Evaluation
- periods
- components
- rules
- evaluations
- scores
- criteria

### Incident
- employee incidents
- resolution

### Reporting
- filters
- exports

### Notification
- in-app notifications
- future push notification

### Audit
- audit logs
- login history
- sensitive action logs

## 4. Layering

Recommended:

```text
HTTP
├── Controllers
├── FormRequests
└── Resources

Application
├── Actions
├── Services
└── DTO

Domain
├── Policies
├── Rules
├── Enums
└── Value Objects

Infrastructure
├── Models
├── Repositories (only if justified)
├── Queue Jobs
├── Notifications
└── Storage
```

## 5. Controller Rule

Controller:
- menerima request
- authorize
- call action/service
- return Inertia/response

Tidak menyimpan business logic panjang.

## 6. Frontend

```text
resources/js/
├── components/
│   ├── ui/
│   ├── data-table/
│   ├── forms/
│   ├── employees/
│   └── evaluations/
├── features/
│   ├── employees/
│   ├── evaluations/
│   ├── incidents/
│   └── reports/
├── layouts/
├── pages/
├── hooks/
├── lib/
├── types/
└── utils/
```

## 7. Authentication

Gunakan Laravel session auth untuk Inertia.

Jangan menyimpan credential/token manual di localStorage.

## 8. Authorization Flow

```text
Authenticated User
  ↓
Permission
  ↓
Policy
  ↓
Organizational Scope
  ↓
Business Rule
  ↓
Query / Mutation
```

## 9. Query Scope

Buat centralized scope resolver/service.

Contoh tanggung jawab:
- determine allowed branch IDs
- division IDs
- sub division IDs
- check target assignment
- intersect filter requested vs allowed scope

## 10. Employee Identity

`employees` = identitas permanen.

`employee_assignments` = histori penempatan.

Jangan overwrite histori.

## 11. Evaluation Model

Header:
`employee_evaluations`

Detail:
`employee_evaluation_scores`

Komponen:
`evaluation_components`

Dengan demikian penambahan komponen baru tidak butuh migration score-column baru.

## 12. Queue

Queue untuk:
- XLSX export besar
- import
- email
- notification
- bulk assignment
- heavy recalculation

Database queue cukup untuk MVP.

Redis optional.

## 13. Scheduler

Gunakan Laravel Scheduler untuk:
- reminder period
- stale draft notification
- problem rule evaluation
- cleanup temporary export
- health checks tertentu

## 14. Storage

Public:
- app logo
- static UI asset
- PWA asset

Private:
- employee documents
- evidence
- generated sensitive report jika disimpan

## 15. PWA

Service worker:
- static cache
- versioned build cache
- offline shell

Authenticated employee data:
NetworkOnly / carefully NetworkFirst sesuai sensitivity.

## 16. Error Handling

User:
pesan umum.

Server:
detail log.

APP_DEBUG false production.

## 17. Observability

Minimal:
- Laravel log
- queue log
- audit log
- failed_jobs
- login/security event

Optional:
- uptime monitor
- centralized error monitoring
