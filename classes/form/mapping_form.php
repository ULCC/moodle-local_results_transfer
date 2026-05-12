<?php
namespace local_results_transfer\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

class mapping_form extends \moodleform {

    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('html', '<div class="alert alert-secondary">');
        $mform->addElement('html', get_string('mappinginstructions', 'local_results_transfer'));
        $mform->addElement('html', '</div>');

        $existingjson = get_config('local_results_transfer', 'procedure_parameters_json');
        $rows = [];

        if (!empty($existingjson)) {
            $decoded = json_decode($existingjson, true);
            if (is_array($decoded)) {
              $rows = $decoded;

              usort($rows, static function(array $a, array $b): int {
                  return (($a['sortorder'] ?? 0) <=> ($b['sortorder'] ?? 0));
              });
            }
        }

        if (empty($rows)) {
            $rows = [
                [
                    'sortorder' => 1,
                    'direction' => 'in',
                    'param_name' => 'srs_course_id',
                    'source_column' => 'srs_course_id',
                    'data_type' => 'varchar',
                ],
                [
                    'sortorder' => 2,
                    'direction' => 'in',
                    'param_name' => 'srs_assessment_element_id',
                    'source_column' => 'srs_assessment_element_id',
                    'data_type' => 'varchar',
                ],
                [
                    'sortorder' => 3,
                    'direction' => 'in',
                    'param_name' => 'srs_student_id',
                    'source_column' => 'srs_student_id',
                    'data_type' => 'varchar',
                ],
                [
                    'sortorder' => 4,
                    'direction' => 'in',
                    'param_name' => 'mdl_grade_scale',
                    'source_column' => 'mdl_grade_scale',
                    'data_type' => 'varchar',
                ],
                [
                    'sortorder' => 5,
                    'direction' => 'in',
                    'param_name' => 'mdl_grade',
                    'source_column' => 'mdl_grade',
                    'data_type' => 'varchar',
                ],
                [
                    'sortorder' => 6,
                    'direction' => 'in',
                    'param_name' => 'mdl_dn',
                    'source_column' => 'mdl_dn',
                    'data_type' => 'varchar',
                ],
                [
                    'sortorder' => 7,
                    'direction' => 'out',
                    'param_name' => 'status',
                    'source_column' => '',
                    'data_type' => 'varchar',
                ],
            ];
        }

        $repeatarray = [];

        $repeatarray[] = $mform->createElement('text', 'sortorder', get_string('sortorder', 'local_results_transfer'), [
            'size' => 4,
        ]);

        $repeatarray[] = $mform->createElement('select', 'param_direction', get_string('direction', 'local_results_transfer'), [
            'in' => get_string('inputparam', 'local_results_transfer'),
            'out' => get_string('outputparam', 'local_results_transfer'),
        ]);

        $repeatarray[] = $mform->createElement('text', 'param_name', get_string('parametername', 'local_results_transfer'), [
            'size' => 32,
        ]);

        $repeatarray[] = $mform->createElement('text', 'source_column', get_string('sourcecolumn', 'local_results_transfer'), [
            'size' => 32,
        ]);

        $repeatarray[] = $mform->createElement('select', 'data_type', get_string('datatype', 'local_results_transfer'), [
            'varchar' => 'VARCHAR',
            'nvarchar' => 'NVARCHAR',
            'int' => 'INT',
            'decimal' => 'DECIMAL',
            'datetime' => 'DATETIME',
            'text' => 'TEXT',
        ]);

        $repeatarray[] = $mform->createElement('static', 'mappingnote', '', get_string('mappingrownote', 'local_results_transfer'));

        $repeateloptions = [];

        $repeateloptions['sortorder']['type'] = PARAM_INT;
        $repeateloptions['param_name']['type'] = PARAM_TEXT;
        $repeateloptions['source_column']['type'] = PARAM_TEXT;
        $repeateloptions['data_type']['type'] = PARAM_ALPHA;
        $repeateloptions['param_direction']['type'] = PARAM_ALPHA;

        $repeatcount = max(10, count($rows) + 3);

        $this->repeat_elements(
            $repeatarray,
            $repeatcount,
            $repeateloptions,
            'mapping_repeats',
            'mapping_add_fields',
            3,
            get_string('addmoreparameters', 'local_results_transfer'),
            true
        );

        $defaults = [
            'sortorder' => [],
            'param_direction' => [],
            'param_name' => [],
            'source_column' => [],
            'data_type' => [],
        ];

        foreach ($rows as $i => $row) {
            $defaults['sortorder'][$i] = $row['sortorder'] ?? ($i + 1);
            $defaults['param_direction'][$i] = $row['direction'] ?? 'in';
            $defaults['param_name'][$i] = $row['param_name'] ?? '';
            $defaults['source_column'][$i] = $row['source_column'] ?? '';
            $defaults['data_type'][$i] = $row['data_type'] ?? 'varchar';
        }

        $this->set_data($defaults);

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $hasinput = false;
        $hasoutput = false;
        $usedorders = [];

        if (!empty($data['param_name']) && is_array($data['param_name'])) {
            foreach ($data['param_name'] as $i => $name) {
                $name = trim((string)$name);

                if ($name === '') {
                    continue;
                }

                $direction = $data['param_direction'][$i] ?? 'in';
                $sourcecolumn = trim((string)($data['source_column'][$i] ?? ''));
                $sortorder = (int)($data['sortorder'][$i] ?? 0);

                if ($sortorder <= 0) {
                    $errors["sortorder[$i]"] = get_string('sortorderrequired', 'local_results_transfer');
                } else if (isset($usedorders[$sortorder])) {
                    $errors["sortorder[$i]"] = get_string('sortorderduplicate', 'local_results_transfer');
                } else {
                    $usedorders[$sortorder] = true;
                }

                if ($direction === 'in') {
                    $hasinput = true;

                    if ($sourcecolumn === '') {
                        $errors["source_column[$i]"] = get_string('sourcecolumnrequired', 'local_results_transfer');
                    }
                }

                if ($direction === 'out') {
                    $hasoutput = true;
                }
            }
        }

        if (!$hasinput) {
            $errors['param_name[0]'] = get_string('atleastoneinputrequired', 'local_results_transfer');
        }

        if (!$hasoutput) {
            $errors['param_name[0]'] = get_string('atleastoneoutputrequired', 'local_results_transfer');
        }

        return $errors;
    }
}
