<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/classes/form/mapping_form.php');

admin_externalpage_setup('local_results_transfer_mapping');

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url(new moodle_url('/local/results_transfer/mapping.php'));
$PAGE->set_title(get_string('mappingpage', 'local_results_transfer'));
$PAGE->set_heading(get_string('mappingpage', 'local_results_transfer'));

$PAGE->requires->js_init_code("
require(['jquery'], function($) {

    function getIndexFromName(name) {
        var match = name.match(/\\[(\\d+)\\]/);
        return match ? match[1] : null;
    }

    function toggleMappingRows() {
        $('select[name^=\"param_direction\"]').each(function() {
            var directionSelect = $(this);
            var index = getIndexFromName(directionSelect.attr('name'));

            if (index === null) {
                return;
            }

            var sourceInput = $('input[name=\"source_column[' + index + ']\"]');

            if (!sourceInput.length) {
                return;
            }

            if (directionSelect.val() === 'out') {
                sourceInput.val('');
                sourceInput.prop('readonly', true);
                sourceInput.addClass('bg-light text-muted');
                sourceInput.attr('placeholder', 'Not required for output parameter');
            } else {
                sourceInput.prop('readonly', false);
                sourceInput.removeClass('bg-light text-muted');
                sourceInput.attr('placeholder', '');
            }
        });
    }

    toggleMappingRows();

    $(document).on('change', 'select[name^=\"param_direction\"]', function() {
        toggleMappingRows();
    });
});
");

$mform = new \local_results_transfer\form\mapping_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/settings.php', ['section' => 'local_results_transfer']));
}

if ($data = $mform->get_data()) {
    $rows = [];

    if (!empty($data->param_name) && is_array($data->param_name)) {
        foreach ($data->param_name as $i => $name) {
            $name = trim((string)$name);

            if ($name === '') {
                continue;
            }

            $direction = $data->param_direction[$i] ?? 'in';
            $sourcecolumn = trim((string)($data->source_column[$i] ?? ''));

            if ($direction === 'out') {
                $sourcecolumn = '';
            }

            $rows[] = [
                'sortorder' => (int)($data->sortorder[$i] ?? ($i + 1)),
                'direction' => $direction,
                'param_name' => $name,
                'source_column' => $sourcecolumn,
                'data_type' => trim((string)($data->data_type[$i] ?? 'varchar')),
            ];
        }
    }

    usort($rows, static function(array $a, array $b): int {
        return ($a['sortorder'] <=> $b['sortorder']);
    });

    $normalised = [];
    foreach ($rows as $index => $row) {
        $row['sortorder'] = $index + 1;
        $normalised[] = $row;
    }

    set_config(
        'procedure_parameters_json',
        json_encode($normalised, JSON_PRETTY_PRINT),
        'local_results_transfer'
    );

    redirect(
        new moodle_url('/local/results_transfer/mapping.php'),
        get_string('mappingsaved', 'local_results_transfer'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mappingpage', 'local_results_transfer'));

echo html_writer::tag(
    'p',
    get_string('mappingpage_desc', 'local_results_transfer'),
    ['class' => 'alert alert-info']
);

$mform->display();

echo $OUTPUT->footer();
