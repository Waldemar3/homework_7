<?php


namespace App;


abstract class Table{
    private static function getPDO(){

        $host = "mysql";
        $dbname = "animal";
        $username = "root";
        $password = "qwerty";

        try {
            $pdo = new \PDO("mysql:host={$host};dbname={$dbname}", $username, $password);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            return $pdo;

        } catch (\PDOException $e) {
            echo 'Подключение не удалось ' . $e->getMessage();
        }
        return false;
    }

    public static function find(int $id){
        $pdo = self::getPDO();

        $table = static::class;

        $sql = "select * from {$table} where id = ?";

        $state = $pdo->prepare($sql);
        $state->execute([$id]);

        $information = $state->fetch(\PDO::FETCH_ASSOC);

        if($information) {
            $nameOfClass = new static();

            foreach ($information as $name => $info) {
                $nameOfClass->$name = $info;
            }

            return $nameOfClass;

        }
        throw new \InvalidArgumentException('Такой ячейки не существует');
    }

    public function delete(){
        $pdo = self::getPDO();

        $table = static::class;

        $sql = "delete from {$table} where id = ?";

        $state = $pdo->prepare($sql);
        $state->execute([$this->id]);
    }

    public function save(){
        $pdo = self::getPDO();

        $state = $pdo->prepare($this->updateInsert()['sql']);
        $state->execute($this->updateInsert()['values']);
    }

    private function updateInsert(){
        $pdo = self::getPDO();

        $table = static::class;

        $sql = [];

        $select = $pdo->prepare("select * from {$table} where id = ?");
        $select->execute([$this->id]);

        if($select->fetch() === false){
            $query = $pdo->query("desc {$table}");
            $structure = $query->fetchAll(\PDO::FETCH_COLUMN);

            $column = array_slice($structure, 1);
            $columnOfClass = array_values(array_flip(get_object_vars($this)));

            if(!empty(array_diff($column, $columnOfClass))){
                throw new \InvalidArgumentException('Вы указали не все ячейки или не все ячейки соотвествуют ячейкам в бд');
            }

            $fieldInsert = implode(', ', array_keys(get_object_vars($this)));
            $maskInsert = implode(', ', array_fill(0, count(get_object_vars($this)), '?'));

            $sql['sql'] = "insert into {$table} ({$fieldInsert}) values ({$maskInsert})";
            $sql['values'] = array_values(get_object_vars($this));
        }else{
            $maskUpdate = implode(' = ?, ', array_keys(get_object_vars($this))) . ' = ?';
            $valuesUpdate = array_values(get_object_vars($this));
            array_push($valuesUpdate, $this->id);

            $sql['sql'] = "update {$table} set {$maskUpdate} where id = ?";
            $sql['values'] = $valuesUpdate;
        }

        return $sql;
    }
}
