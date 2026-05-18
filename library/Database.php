<?php

class Database extends PDO {
    private static ?self $instance = null;

    private function __construct() {
        parent::__construct(
            DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 30,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
            ]
        );
    }

    private function __clone() {}

    public function __wakeup() {
        throw new Exception('Cannot unserialize singleton Database');
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function InsertData($table, $data) {
        ksort($data);
        $fieldlog = null;
        $fieldNames = implode(', ', array_keys($data));
        $fieldInputs = ':' . implode(', :', array_keys($data));
        $sql_statement = "INSERT INTO $table
                    ($fieldNames)
            VALUES  ($fieldInputs)";
        $sth = $this->prepare($sql_statement);

        foreach ($data as $key => $value) {
            $sth->bindValue(":$key", $value);
            $fieldlog .= "$key = $value,";
        }

        $sth->execute();

        $now = date('Y-m-d H:i:s');
        if (isset($_SESSION['uid'])) {
            $user_id = $_SESSION['uid'];

            $logdata = array(
                'log_time' => $now,
                'table_name' => $table,
                'query_executed' => $sql_statement,
                'data_set' => $fieldlog,
                'user_id' => $user_id
            );
            // $this->DBOperationLog($logdata);
        }

        return true;
    }

    public function UpdateData($table, $data, $where) {
        ksort($data);
        $fieldlog = null;
        $fieldDetails = null;
        foreach ($data as $key => $value) {
            $fieldDetails .= "$key = :$key,";
            $fieldlog .= "$key = $value,";
        }

        $fieldDetails = rtrim($fieldDetails, ',');

        $sql_statement = "UPDATE $table SET $fieldDetails WHERE $where";

        $sth = $this->prepare($sql_statement);

        foreach ($data as $key => $value) {
            $sth->bindValue(":$key", $value);
        }

        $sth->execute();
        $now = date('Y-m-d H:i:s');
        if (isset($_SESSION['uid'])) {
            $user_id = $_SESSION['uid'];

            $logdata = array(
                'log_time' => $now,
                'table_name' => $table,
                'query_executed' => $sql_statement,
                'data_set' => $fieldlog,
                'user_id' => $user_id
            );
            // $this->DBOperationLog($logdata);
        }

        return true;
    }

    public function SelectData($sql, $data = array(), $fetchMode = PDO::FETCH_ASSOC) {
        $sth = $this->prepare($sql);

        foreach ($data as $key => $value) {
            $sth->bindValue(":$key", $value);
        }

        $sth->execute();
        return $sth->fetchAll($fetchMode);
    }

    public function DeleteData($table, $where, $limit = 1) {
        $sql_statement = "DELETE FROM $table WHERE $where LIMIT $limit";
        $result = $this->exec($sql_statement);

        if (isset($_SESSION['uid'])) {
            $now = date('Y-m-d H:i:s');
            $user_id = $_SESSION['uid'];
            $logdata = array(
                'log_time' => $now,
                'table_name' => $table,
                'query_executed' => $sql_statement,
                'user_id' => $user_id
            );
            // $this->DBOperationLog($logdata);
        }

        return $result;
    }

    public function TruncateData($table) {
        $sql_statement = "TRUNCATE TABLE $table";
        return $this->exec($sql_statement);
    }

    public function DBOperationLog($data) {
        $fieldNames = implode(', ', array_keys($data));
        $fieldInputs = ':' . implode(', :', array_keys($data));
        $sql_statement = "INSERT INTO palm_system_log
                    ($fieldNames)
            VALUES  ($fieldInputs)";
        $sth = $this->prepare($sql_statement);
        foreach ($data as $key => $value) {
            $sth->bindValue(":$key", $value);
        }
        $sth->execute();

        return true;
    }
}

?>