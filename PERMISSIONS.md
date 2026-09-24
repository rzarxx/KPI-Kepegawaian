# PERMISSIONS — RBAC & ORGANIZATIONAL SCOPE

## 1. Model

Authorization:

**Role + Permission + Organizational Scope + Policy**

## 2. Roles

Initial:
- Super Admin
- HR Admin
- HR Manager
- Branch Head
- Division Head
- Sub Division Head
- Auditor
- Employee

## 3. Permissions

### Employee
- employee.view
- employee.create
- employee.update
- employee.change_status
- employee.transfer
- employee.export

### Incident
- employee_incident.view
- employee_incident.create
- employee_incident.update
- employee_incident.resolve

### Evaluation
- evaluation.view
- evaluation.create
- evaluation.update
- evaluation.submit
- evaluation.approve
- evaluation.finalize

### Report
- report.view
- report.export

### Organization
- organization.view
- organization.manage

### User
- user.view
- user.create
- user.update
- user.disable

### Role
- role.view
- role.manage

### Audit
- audit.view

### Settings
- settings.view
- settings.manage

## 4. Scope

Scope dapat berisi:
- branch
- division
- sub division

Contoh:
Division Head:
branch=Cirebon
division=IT

Maka seluruh query harus intersect dengan scope tersebut.

## 5. Matrix Ringkas

| Action | Super Admin | HR | Branch Head | Division Head | Sub Division Head | Auditor |
|---|---|---|---|---|---|---|
| View employee | All | All | Scope | Scope | Scope | Allowed scope |
| Create employee | Yes | Yes | Optional | Scope | Scope | No |
| Edit employee | Yes | Yes | Scope | Scope | Scope | No |
| Transfer | Yes | Yes | Scope | Request/limited | Request/limited | No |
| Incident create | Yes | Yes | Scope | Scope | Scope | No |
| Evaluate | Yes | Yes | Scope | Scope | Scope | No |
| Export | Yes | Yes | Scope | Scope | Scope | Limited |
| Manage role | Yes | Limited/No | No | No | No | No |
| Audit view | Yes | Limited | No | No | No | Yes/limited |

## 6. Policy Rules

Setiap resource wajib Policy.

Contoh:
`EmployeePolicy::view(User $user, Employee $employee)`

Policy harus memeriksa:
1. permission
2. target resource scope
3. special business constraints

## 7. Query Scope

Index endpoint tidak boleh:
```php
Employee::paginate();
```

Gunakan centralized scope.

## 8. Export

Export wajib menggunakan:
```text
requested filters ∩ authorized scope
```

## 9. Files

Download employee document juga wajib policy + scope.

## 10. Frontend

Frontend boleh menerima list permission untuk hide/show UI.

Tetapi backend tetap authority.

## 11. Role Change

Setelah role/permission berubah:
- invalidate permission cache
- optional invalidate session untuk perubahan kritikal

## 12. Deny by Default

Jika permission/scope tidak jelas:
**deny**.
