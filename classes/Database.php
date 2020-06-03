<?php

class Database
{

    private static $instance = null;
    private $pdo, $query, $error = false, $results, $count;



    private function __construct()
    {
        try { // Выявляем ошибки
            $this->pdo = new PDO("mysql:host=" . Config::get('mysql.host') . ";dbname=" . Config::get('mysql.database'), Config::get('mysql.username'), Config::get('mysql.password'));
        }catch (PDOException $exception) { // Выявляем ошибки
            die($exception->getMessage());
        }
    }



    // вызовом метода - getInstance(), вызываем __construct() который подключает к БД
    public static function getInstance()
    {
        // Исключаем повторный вызов БД
        // !isset - если не существует
        if(!isset(self::$instance))
        {
            self::$instance = new Database; // то создаем екземпляр класса Database, что вызывает __construct()
        }

        return self::$instance;
    }



    // в $params = [] попадут параметры из sql запроса
    // count - удостоверимся что $params не пустой - имеет значение
    public function query($sql, $params = [])
    {
        $this->error = false;
        $this->query = $this->pdo->prepare($sql);

        if(count($params)) {
            $i = 1;
            foreach($params as $param) {
                $this->query->bindValue($i, $param);
                $i++;
            }
        }

        if(!$this->query->execute()) {
            $this->error = true;
        }else {
            $this->results = $this->query->fetchAll(PDO::FETCH_OBJ);
            $this->count = $this->query->rowCount();
        }

        return $this;
    }



    public function error()
    {
        return $this->error;
    }

    public function results()
    {
        return $this->results;
    }

    public function count()
    {
        return $this->count;
    }

    public function get($table, $where = [])
    {
        return $this->action('SELECT *', $table, $where);
    }

    public function delete($table, $where = [])
    {
        return $this->action('DELETE', $table, $where);
    }



    // if(count($where) === 3) если 3 элемента в $where
    // если в массиве $operators есть $operator
    // in_array возвращает true или false в зависимости от того нашла ли эта функция значение $operator в массиве $operators
    public function action($action, $table, $where = [])
    {
        if(count($where) === 3) {

            $operators = ['=', '>', '<', '>=', '<='];

            $field = $where[0];
            $operator = $where[1];
            $value = $where[2];

            if(in_array($operator, $operators)) {

                $sql = "{$action} FROM {$table} WHERE {$field} {$operator} ?";
                if(!$this->query($sql, [$value])->error()) { //true если есть ошибка
                    return $this; // возвращаем обьект
                }
            }
        }

        return false;
    }



// "INSERT INTO users () VALUES ()";
// "INSERT INTO users ('username', 'password') VALUES ('marlin', 'password')";
// "INSERT INTO users ('username', 'password') VALUES (?, ?)";
// array_keys($fields) -- выбираем из массива ключи
// (implode("', '", $keys)) -- implode создает строку с разделителем который прописан вначале
// var_dump(array_keys($fields));die();

    // $keys = array_keys($fields);
    // var_dump('`' . implode('`, `', $keys) . '`'); die();
    public function insert($table, $fields = [])
    {
        $values = '';
        foreach($fields as $field) {
            $values .= "?,";
        }
        $val = rtrim($values, ',');

        $sql = "INSERT INTO {$table} (" . '`' . implode('`, `', array_keys($fields)) . '`' . ") VALUES ({$val})";

        if(!$this->query($sql, $fields)->error()) {
            return true;
        }
        return false;

    }



    public function update($table, $id, $fields = [])
    {
        $set = '';
        foreach($fields as $key => $field) {
            $set .= "{$key} = ?,"; // username = ?, password = ?,
        }

        $set = rtrim($set, ','); // username = ?, password = ?

        $sql = "UPDATE {$table} SET {$set} WHERE id = {$id}";

        if(!$this->query($sql, $fields)->error()){
            return true;
        }

        return false;
    }

    public function first()
    {
        return $this->results()[0];
    }
}
