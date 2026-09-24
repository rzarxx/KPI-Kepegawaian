# CODE STANDARDS — KPI Kepegawaian

## Backend

### Controllers
Thin.

### Form Requests
Validation + authorization where appropriate.

### Actions
Use-case oriented:
- CreateEmployeeAction
- TransferEmployeeAction
- FinalizeEvaluationAction
- ResolveIncidentAction
- ExportPerformanceReportAction

### Policies
Mandatory sensitive resources.

### Enums
Use for stable statuses.

### Transactions
Use DB transaction for:
- employee + assignment creation
- transfer
- finalization
- status transitions

## Frontend

### TypeScript
No unnecessary `any`.

### Forms
React Hook Form + Zod.

Backend still validates.

### Data Table
TanStack Table.

Server-side pagination/filter/sort.

### Icons
Lucide only.

### Styling
Tailwind + shared components.

Avoid one-off styles when reusable.

## Naming

UI text:
Bahasa Indonesia.

Code:
English naming accepted and recommended for consistency.

## Components

Reusable:
- PageHeader
- StatusBadge
- DataTable
- FilterBar
- EmptyState
- ConfirmDialog
- ScoreInput
- EmployeeSummary
- Timeline
- NotificationItem

## Error Handling

No raw exception shown to user.

## Logging

Contextual.
Never log secrets.

## Git

Small coherent commits.

Recommended conventional style:
- feat:
- fix:
- refactor:
- test:
- docs:
- chore:
