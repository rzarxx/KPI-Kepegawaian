# DATABASE — KPI Kepegawaian

## 1. Database

MySQL 8.

Charset:
`utf8mb4`

Timezone aplikasi:
`Asia/Jakarta`

## 2. Principles

- FK wajib jika aman
- index filter penting
- unique identifier
- history tidak dihapus sembarangan
- use bigint IDs atau UUID sesuai keputusan implementasi
- timestamps
- nullable hanya jika business case mengizinkan

## 3. Core Tables

### users
- id
- name
- email unique
- password
- is_active
- last_login_at nullable
- timestamps

### branches
- id
- code unique
- name
- is_active
- timestamps

### divisions
- id
- branch_id nullable jika global division
- code
- name
- is_active
- timestamps

### sub_divisions
- id
- division_id
- code
- name
- is_active
- timestamps

### positions
- id
- code
- name
- level nullable
- is_active
- timestamps

### employees
- id
- employee_number unique
- national_id nullable
- full_name
- email nullable
- phone nullable
- birth_date nullable
- gender nullable
- join_date
- current_status
- exit_date nullable
- exit_reason nullable
- created_by
- timestamps

Catatan:
current_status hanya snapshot untuk query cepat.
Histori sebenarnya tetap tersimpan di assignments/status histories.

### employee_assignments
- id
- employee_id
- branch_id
- division_id
- sub_division_id nullable
- position_id nullable
- start_date
- end_date nullable
- status
- reason nullable
- created_by
- timestamps

Index:
- employee_id,start_date
- branch_id
- division_id
- sub_division_id
- status

### employee_status_histories
- id
- employee_id
- previous_status nullable
- new_status
- effective_date
- reason nullable
- actor_id
- timestamps

### employee_incidents
- id
- employee_id
- employee_assignment_id nullable
- incident_date
- category
- severity
- title
- description
- status
- resolution nullable
- reported_by
- resolved_by nullable
- resolved_at nullable
- timestamps

### performance_periods
- id
- name
- start_date
- end_date
- status
- is_active
- timestamps

Status:
- draft
- active
- review
- finalized
- closed

### evaluation_components
- id
- code unique
- name
- description nullable
- default_weight decimal
- measurement_type
- scoring_method
- is_auto_calculated
- is_active
- sort_order
- timestamps

### evaluation_component_rules
- id
- component_id
- rule_type
- min_value nullable
- max_value nullable
- score_value nullable
- config_json nullable
- is_active
- timestamps

### evaluation_criteria
- id
- name
- min_score
- max_score
- color_semantic
- sort_order
- timestamps

### employee_evaluations
- id
- employee_id
- assignment_id
- period_id
- evaluator_id
- total_score
- criteria_id nullable
- notes nullable
- status
- submitted_at nullable
- approved_at nullable
- finalized_at nullable
- timestamps

Unique suggestion:
(employee_id, period_id, evaluator_id) depending workflow.

### employee_evaluation_scores
- id
- evaluation_id
- component_id
- raw_score
- weight
- weighted_score
- note nullable
- source_type
- timestamps

Unique:
(evaluation_id, component_id)

### organizational_scopes
- id
- user_id
- branch_id nullable
- division_id nullable
- sub_division_id nullable
- scope_type
- is_active
- timestamps

### notifications
Laravel standard notifications table.

### audit_logs
- id
- actor_id nullable
- action
- auditable_type
- auditable_id
- old_values json nullable
- new_values json nullable
- context json nullable
- ip_address nullable
- user_agent nullable
- created_at

### export_logs
- id
- user_id
- export_type
- filters json
- scope_snapshot json
- row_count nullable
- status
- file_path nullable
- created_at
- completed_at nullable

## 4. Incident Categories

Configurable, initial:
- attendance
- late
- attitude
- sop_violation
- communication
- teamwork
- work_target
- responsibility
- discipline
- other

## 5. Status

Employee:
- ACTIVE
- PROBATION
- MUTATED
- RESIGNED
- TERMINATED
- INACTIVE

## 6. Data Integrity

- employee_number unique
- end_date >= start_date
- period end >= start
- component weight 0..100
- score 0..100 unless scoring method explicitly different
- finalized evaluation immutable except privileged correction flow
- no overlapping active assignments unless business rule allows

## 7. Deletion

Employee:
tidak hard delete.

Reference/master data:
gunakan `is_active`.

Audit:
append-only secara logis.

## 8. Query Optimization

Index:
- employee_number
- full_name optionally fulltext/index strategy
- assignment organization IDs
- incident status/severity/date
- period dates/status
- evaluation employee/period/status
- audit actor/date/action

## 9. Migration Rules

Setiap migration:
- nama jelas
- FK explicit
- index explicit
- rollback tersedia bila aman
- jangan destructive production tanpa backup
