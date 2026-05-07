# local_results_transfer

Moodle local plugin for transferring prepared source rows to a configurable remote stored procedure.

## Current design

The plugin is schema-agnostic. It does not hard-code SURF fields or any stored-procedure-specific parameter list.

The admin config controls:

- Source DB connection
- Source table/view to read
- Source table to update
- ID field
- status/transferred field
- optional log reference field
- remote procedure DB connection
- remote procedure name
- ordered list of source fields to pass as procedure input parameters

The configured procedure is called with:

```text
<configured input parameters in order> + OUT status parameter
```

The OUT status parameter must return `0` for success. On success, the plugin stamps the configured status/transferred field with `UNIX_TIMESTAMP(NOW())`.

## Untransferred rows

For MySQL source testing, rows are selected when the configured status/transferred field is:

```sql
0 OR '0' OR NULL OR ''
```

## Local MySQL testing

For a 15-field mock test, configure `procedure_parameter_fields` as one field per line:

```text
associationId
role
startDateTime
expectedEndDateTime
actualEndDateTime
state
attempt
testComponentOfferingId
personId
resultState
resultPass
resultScore
resultDateTime
otherCodesSPR
otherCodesSubmissionState
```

For an older/simple table test, configure it as needed, for example:

```text
srs_course_id
srs_assessment_element_id
srs_student_id
mdl_grade_scale
mdl_grade
mdl_dn
```

The configured procedure signature must match the same order and count, followed by an OUT status parameter.
