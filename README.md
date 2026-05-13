# local_results_transfer

Moodle local plugin for transferring prepared results from an external MIS MySQL database/view to a configurable remote stored procedure.

## Current Plymouth-oriented defaults

The latest Plymouth testing update expects the transfer to read from the published results view and call:

`insertTestComponentOfferingAssociationStudentResult`

with ordered input parameters:

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
13. `STATUS` output parameter

The expected success value is:

`SUCCESS`

Any other output value is treated as failure and the source row is left retryable.

## Configuration pages

Main settings:

`Site administration → Plugins → Local plugins → Results Transfer`

Field mapping:

`Site administration → Plugins → Local plugins → Results Transfer field mapping`

The mapping page stores ordered procedure mapping as JSON in Moodle plugin config under:

`local_results_transfer/procedure_parameters_json`

## Mapping behaviour

- Input rows require a source column.
- Output rows do not require a source column.
- The `Order` value controls the stored procedure parameter order.
- The mapping is schema-agnostic and procedure-agnostic.
- The plugin passes values positionally to the stored procedure.

## Scheduled task

Run manually from Moodle root:

```bash
php admin/cli/scheduled_task.php --execute="\\local_results_transfer\\task\\transfer_task"
```

## Notes

The plugin supports both:

- `sqlsrv` target procedure calls for UAT/production
- `mysqli` target procedure calls for local development testing



## Plymouth/UAT defaults

The current Plymouth flow uses a datetime transfer field. Configure:

- Source table/view to read: `mis.published_TestComponentAssociationStudentResults`
- Source table to update: `mis.exported_grades`
- Transferred/status field: `grade_transferred`
- Transferred/status field type: `Datetime / NOW()`
- Remote procedure success value: `SUCCESS`

For datetime transfer fields, the plugin selects rows where the transfer field is `NULL`, blank, `0000-00-00 00:00:00`, `1970-01-01 00:00:00`, or `1970-01-01 01:00:00`, and stamps successful rows with `NOW()`.

The scheduled task reads the JSON mapping saved by the Results Transfer field mapping page (`procedure_parameters_json`). The legacy textarea is only used as fallback if no JSON mapping has been saved.
