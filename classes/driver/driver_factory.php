<?php
// This file is part of Moodle - http://moodle.org/

namespace local_results_transfer\driver;

/**
 * Driver factory.
 *
 * @package    local_results_transfer
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class driver_factory {
    /**
     * Create a driver by DB type.
     *
     * @param string $type Driver type.
     * @return driver_interface
     */
    public static function create(string $type): driver_interface {
        if ($type === 'mysqli') {
            return new mysqli_source_driver();
        }
        if ($type === 'sqlsrv') {
            return new sqlsrv_target_driver();
        }
        throw new \moodle_exception('Unsupported driver type: ' . s($type));
    }
}
