<?php
// This file is part of Moodle - http://moodle.org/

namespace local_results_transfer\driver;

/**
 * MySQLi source driver. Also supports MySQL procedure calls for local dev testing.
 *
 * @package    local_results_transfer
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mysqli_source_driver implements driver_interface {
    /** @var \mysqli|null */
    private $conn = null;

    /**
     * Connect to MySQL.
     *
     * @param \stdClass $config Connection config.
     */
    public function connect(\stdClass $config): void {
        $host = $config->host ?? 'localhost';
        $port = (int)($config->port ?? 3306);
        $name = $config->name ?? '';
        $user = $config->user ?? '';
        $pass = $config->pass ?? '';

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->conn = new \mysqli($host, $user, $pass, $name, $port);
        $this->conn->set_charset('utf8mb4');
    }

    /**
     * Disconnect.
     */
    public function disconnect(): void {
        if ($this->conn instanceof \mysqli) {
            $this->conn->close();
            $this->conn = null;
        }
    }

    /**
     * Execute setup SQL.
     *
     * @param string $sql SQL.
     */
    public function execute_setup_sql(string $sql): void {
        $this->require_connection();
        if (trim($sql) === '') {
            return;
        }
        $this->conn->multi_query($sql);
        do {
            if ($result = $this->conn->store_result()) {
                $result->free();
            }
        } while ($this->conn->more_results() && $this->conn->next_result());
    }

    /**
     * Fetch untransferred rows.
     *
     * @param string $table Table/view.
     * @param string $idfield ID field.
     * @param string $transferredfield Transferred field.
     * @return \Generator
     */
    public function fetch_untransferred(string $table, string $idfield, string $transferredfield): \Generator {
        $this->require_connection();
        $table = self::quote_identifier_path($table);
        $idfield = self::quote_identifier($idfield);
        $transferredfield = self::quote_identifier($transferredfield);

        $sql = "SELECT * FROM {$table} WHERE ({$transferredfield} = 0 OR {$transferredfield} = '0' OR {$transferredfield} IS NULL OR {$transferredfield} = '') ORDER BY {$idfield}";
        $result = $this->conn->query($sql);
        try {
            while ($row = $result->fetch_object()) {
                yield $row;
            }
        } finally {
            $result->free();
        }
    }

    /**
     * Call a MySQL stored procedure. Intended for local development testing.
     *
     * @param string $procname Procedure name.
     * @param array $inparams Ordered input params.
     * @return string
     */
    public function call_procedure(string $procname, array $inparams): string {
        $this->require_connection();
        $procname = self::quote_identifier_path($procname);
        $this->conn->query("SET @out_status = ''");

        $placeholders = [];
        if (!empty($inparams)) {
            $placeholders = array_fill(0, count($inparams), '?');
        }
        $placeholders[] = '@out_status';

        $stmt = $this->conn->prepare("CALL {$procname}(" . implode(',', $placeholders) . ")");

        if (!empty($inparams)) {
            // MySQLi bind_param needs references.
            $types = str_repeat('s', count($inparams));
            $values = [];
            foreach ($inparams as $param) {
                $values[] = $param;
            }
            $refs = [];
            $refs[] = &$types;
            foreach ($values as $key => $value) {
                $refs[] = &$values[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }

        $stmt->execute();
        $stmt->close();

        while ($this->conn->more_results() && $this->conn->next_result()) {
            if ($result = $this->conn->store_result()) {
                $result->free();
            }
        }

        $result = $this->conn->query('SELECT @out_status AS status');
        $row = $result->fetch_assoc();
        $result->free();

        return (string)($row['status'] ?? '');
    }

    /**
     * Mark a row as transferred.
     *
     * @param string $table Table name.
     * @param string $idfield ID field.
     * @param mixed $idvalue ID value.
     * @param string $transferredfield Transferred field.
     */
    public function mark_transferred(string $table, string $idfield, $idvalue, string $transferredfield): void {
        $this->require_connection();
        $table = self::quote_identifier_path($table);
        $idfield = self::quote_identifier($idfield);
        $transferredfield = self::quote_identifier($transferredfield);

        $stmt = $this->conn->prepare("UPDATE {$table} SET {$transferredfield} = UNIX_TIMESTAMP(NOW()) WHERE {$idfield} = ?");
        $id = (string)$idvalue;
        $stmt->bind_param('s', $id);
        $stmt->execute();
        if ($stmt->affected_rows < 1) {
            $stmt->close();
            throw new \moodle_exception('No row was updated when marking transferred.');
        }
        $stmt->close();
    }

    /**
     * Ensure connection exists.
     */
    private function require_connection(): void {
        if (!$this->conn instanceof \mysqli) {
            throw new \moodle_exception('MySQL connection is not open.');
        }
    }

    /**
     * Quote a single identifier.
     *
     * @param string $identifier Identifier.
     * @return string
     */
    private static function quote_identifier(string $identifier): string {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new \moodle_exception('Invalid SQL identifier: ' . s($identifier));
        }
        return '`' . $identifier . '`';
    }

    /**
     * Quote schema-qualified identifier path.
     *
     * @param string $path Identifier path.
     * @return string
     */
    private static function quote_identifier_path(string $path): string {
        $parts = explode('.', $path);
        return implode('.', array_map([self::class, 'quote_identifier'], $parts));
    }
}
