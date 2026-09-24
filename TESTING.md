# TESTING — KPI Kepegawaian

## 1. Test Layers

- Unit
- Feature
- Policy
- Integration
- Frontend component
- E2E critical path
- Security regression

## 2. Authentication

Test:
- valid login
- invalid password
- generic error
- throttling
- session regeneration
- logout invalidation

## 3. RBAC

Critical matrix:
- employee view allowed
- employee view denied outside scope
- edit denied outside scope
- incident denied outside scope
- evaluation denied outside scope
- report export denied outside scope

## 4. IDOR

Test changing resource ID manually.

Expected:
403/404 per policy design without information leakage.

## 5. Employee

- create in allowed scope
- cannot create outside scope
- assignment history
- transfer closes old assignment
- resign retains history
- duplicate employee identifier detection

## 6. Evaluation

- weights total validation
- raw score validation
- total formula
- criteria boundary
- tenure mapping
- draft update
- finalized immutability

## 7. Incident

- severity validation
- status transition
- resolution requirement
- notification trigger

## 8. Reports

- custom period
- filter
- scope
- XLSX columns
- numeric formats
- audit log export

## 9. PWA

- manifest valid
- installable
- service worker registration
- static cache works
- sensitive endpoints not cached
- update prompt
- offline page

## 10. Frontend

- responsive
- keyboard
- focus
- empty state
- loading
- form error
- dirty form warning
- double submit prevention

## 11. Security

- CSRF
- XSS payload rendering
- SQLi attempts
- upload MIME mismatch
- oversized upload
- unauthorized file download
- mass assignment

## 12. CI Suggested

Run:
```text
php artisan test
vendor/bin/pint --test
npm run lint
npm run typecheck
npm run build
```

If Pest configured:
use Pest consistently.

## 13. Definition of Done

Feature belum selesai jika:
- policy test belum ada untuk sensitive feature
- happy path saja yang diuji
- build gagal
- docs tidak diperbarui
