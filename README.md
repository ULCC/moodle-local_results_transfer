# local_results_transfer

Moodle local plugin for transferring prepared results from an external MIS database/view to a configurable remote stored procedure.

The plugin is intentionally:

- schema-agnostic
- procedure-agnostic
- driver-agnostic
- fully admin-configurable

It supports configurable source mappings, configurable stored procedure parameter mappings, configurable success handling, retry handling, and configurable transfer tracking.

---

# Current Plymouth-oriented defaults

The latest Plymouth testing update expects the transfer to read from the published results view and call:

```text
insertTestComponentOfferingAssociationStudentResult
```

with ordered parameters:

1. `associationId`
2. `role`
3. `state`
4. `attempt`
5. `testComponentOfferingId`
6. `personId`
7. `resultState`
8. `resultPass`
9. `resultScore`
10. `resultDateTime`
11. `otherCodesSPR`
12. `otherCodesSubmissionState`
13. `STATUS` OUTPUT parameter

The expected success value is:

```text
SUCCESS
```

Any other returned value is treated as failure and the source row remains retryable.

---

# Current Architecture

```text
local_srs_webservice
    ↓
mis.exported_grades
    ↓
mis.published_TestComponentAssociationStudentResults
    ↓
local_results_transfer
    ↓
Remote stored procedure
```

The plugin owns only the second-hop transfer process.

---

# Configuration Pages

## Main Settings

```text
Site administration
→ Plugins
→ Local plugins
→ Results Transfer
```

## Field Mapping

Accessed from the main Results Transfer settings page via:

```text
Open field mapping configuration
```

The mapping page stores ordered procedure mapping as JSON in Moodle config:

```text
local_results_transfer/procedure_parameters_json
```

---

# Mapping Behaviour

- Input rows require a source column.
- Output rows do not require a source column.
- Output rows automatically disable source-column editing.
- The `Order` value controls stored procedure parameter order.
- Parameters are sorted automatically before execution.
- Mapping is schema-agnostic and procedure-agnostic.
- Procedure parameters are passed positionally.

---

# Driver Support

The plugin currently supports:

| Driver | Purpose |
|---|---|
| `mysqli` | Source database and local development testing |
| `sqlsrv` | Optional MSSQL/sqlsrv support |
| `odbc` | Preferred Plymouth production/UAT approach |

---

# ODBC Support

The plugin now supports:

```text
php8.1-odbc
+
Microsoft ODBC Driver 18 for SQL Server
```

via:

```text
odbc_target_driver
```

This avoids requiring:

```text
sqlsrv.so
```

and aligns with Debian/Ubuntu packaged-extension management.

## Important ODBC Note

For ODBC mode, the stored procedure should also return:

```sql
SELECT @STATUS AS status;
```

so the plugin can read the returned status row cleanly through ODBC.

---

# Plymouth / UAT Defaults

Current Plymouth-oriented configuration:

| Setting | Value |
|---|---|
| Source table/view to read | `mis.published_TestComponentAssociationStudentResults` |
| Source table to update | `mis.exported_grades` |
| Transferred/status field | `grade_transferred` |
| Transfer field type | `Datetime / NOW()` |
| Remote procedure success value | `SUCCESS` |

---

# Datetime Transfer Handling

For datetime transfer fields, the plugin treats the following values as "not transferred":

- `NULL`
- blank
- `0000-00-00 00:00:00`
- `1970-01-01 00:00:00`
- `1970-01-01 01:00:00`

Successful rows are stamped using:

```sql
NOW()
```

---

# Scheduled Task

Run manually from Moodle root:

```bash
php admin/cli/scheduled_task.php --execute="\\local_results_transfer\\task\\transfer_task"
```

Default cadence:

```text
Every 15 minutes
```

via Moodle scheduled tasks.

---

# Runtime Behaviour

The scheduled task:

1. Connects to source database
2. Selects untransferred rows
3. Builds ordered procedure parameters dynamically
4. Calls configured stored procedure
5. Processes returned SUCCESS/FAIL value
6. Marks successful rows as transferred
7. Leaves failed rows retryable
8. Logs all activity through `mtrace()`

---

# Dynamic Mapping Runtime

The scheduled task reads mapping configuration from:

```text
procedure_parameters_json
```

The legacy textarea configuration is only used as fallback if no JSON mapping exists.

---

# Local Development / Simulation

The plugin supports fully local simulation using:

- local MySQL/MariaDB
- mock source tables/views
- mock stored procedures
- configurable mappings
- configurable retry handling

This allows end-to-end testing without requiring client infrastructure access.

---

# Current Status

Validated locally:

- Plugin installation
- Configurable source DB
- Configurable target DB
- Dynamic parameter mapping
- Ordered parameter execution
- SUCCESS / FAIL handling
- Retry handling
- Datetime transfer tracking
- Dynamic JSON mapping runtime
- End-to-end transfer simulation
- ODBC-compatible architecture

---
