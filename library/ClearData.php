<?php

class ClearData extends PDO {

    public function __construct() {
        parent::__construct(DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME, CL_USER, CL_PASS);
            PDO::setAttribute (PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}



    public function SelectData($sql, $data = array(), $fetchMode = PDO::FETCH_ASSOC) {
        $sth = $this->prepare($sql);

        foreach ($data as $key => $value) {
            $sth->bindValue(":$key", $value);
        }

        $sth->execute();
        return $sth->fetchAll($fetchMode);
    }


    public function TruncateData($table) {
        $sql_statement = "TRUNCATE TABLE $table";
        return $this->exec($sql_statement);
    }


}

?>
