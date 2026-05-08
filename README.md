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

## Default local MySQL testing schema

Fresh install defaults are aligned to the currently known `mis.exported_DLEMarks` sample schema.

Default source/update table:

```text
mis.exported_DLEMarks
```

Default log reference field:

```text
srs_assessment_element_id
```

Default `procedure_parameter_fields` value:

```text
srs_course_id
srs_assessment_element_id
srs_student_id
mdl_grade_scale
mdl_grade
mdl_dn
```

The configured stored procedure signature must match the same order and count, followed by one OUT status parameter.

## Using a different schema

If the final source view uses a different shape, update the admin settings instead of changing code. For example, a SURF-style source view can be used by setting `source_to_read` to that view and replacing `procedure_parameter_fields` with the source columns in the exact order expected by the procedure.
