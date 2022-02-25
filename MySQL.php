<?php

class MySQL {
    private $pdo;

    // подключение к БД
    public function __construct($config)
    {
        if(empty($config['host'])) {
            die("<b>" . __METHOD__ . "() error:</b> empty host name in config");
        }
        if(empty($config['dbname'])) {
            die("<b>" . __METHOD__ . "() error:</b> empty database name in config");
        }
        if(empty($config['user'])) {
            die("<b>" . __METHOD__ . "() error:</b> empty user name in config");
        }
        /*if(empty($config['password'])) {
            die("<b>" . __METHOD__ . "() error:</b> empty password in config");
        }*/

        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_COMPRESS => true,
            ];
            $this->pdo = new PDO("mysql:charset=utf8mb4;host=" . $config['host'] . ";dbname=" . $config['dbname'], $config['user'], $config['password'], $options);
            $this->checkDB();
        } catch(PDOException $e) {
            die("<b>" . __METHOD__ . "() connection failed:</b>" . $e->getMessage());
        }
    }

    /**
     * Подготавливает и выполняет SQL-запрос
     * params string $sql - SQL-запрос
     * $params array $params - параметры SQL-запроса
     *
     */
    public function doQuery($sql, $params = array())
    {
        $e = null;
        if(empty($sql)) {
            die('<b>' . __METHOD__ . '() error sql execution:</b> empty $sql query');
        }
        if(!is_array($params)) {
            die('<b>' . __METHOD__ . '() error sql execution:</b> type of $params must be array');
        }

        $stm = $this->pdo->prepare($sql);
        try {
            foreach($params as $k => $v) {
                if(is_int($v)) {
                    $stm->bindValue($k, $v, PDO::PARAM_INT);
                } else {
                    $stm->bindValue($k, $v, PDO::PARAM_STR);
                }
            }
            $res = $stm->execute();

            if(!$stm instanceof PDOStatement || $res === false) { // если запрос не выполнен
                die('<b>' . __METHOD__ . '() error sql execution:</b> unknown error');
            }
            return $stm;
        } catch (Throwable $e) {

        } catch (Exception $e) {

        }

        if(!is_null($e)) {
            die('<b>' . __METHOD__ . '() error sql execution:</b> '. $e->getMessage());
        }
    }

    /**
     * Проверяет наличие нужной таблицы, при отсутствии создает ее
     *
     */
    private function checkDB()
    {
        $sql = "SELECT * FROM `information_schema`.`tables` WHERE `table_name` = 'contacts';";
        $stm = $this->doQuery($sql);

        if($stm->rowCount() == 0) {
            $sql = "CREATE TABLE `contacts` (
                    	`id` INT(11) NOT NULL AUTO_INCREMENT,
                    	`source_id` INT(11) NOT NULL,
                    	`name` VARCHAR(30) NOT NULL,
                    	`phone` CHAR(10) NOT NULL,
                    	`email` VARCHAR(20) NOT NULL,
                    	`created_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    	PRIMARY KEY (`id`),
                    	INDEX `phone_source_id` (`phone`, `source_id`)
                    )
                    COMMENT='Таблица контактов'
                    COLLATE='utf8_general_ci'
                    ENGINE=InnoDB;";
            $this->doQuery($sql);
        }
    }

    /**
     * Сохраняет контакт в БД
     * params array $data - массив данных для сохранения
     * return boolean true/false - результат сохранения данных
     *
     */
    public function saveContact(array $data)
    {
        // проверим наличие контакта с таким телефоном от этого источника с давностью меньше суток
        $sql = "SELECT * FROM `contacts` WHERE `source_id` = :source_id AND `phone` = :phone AND `created_ts` >= DATE_SUB(NOW(), INTERVAL 1 DAY);";
        $stm = $this->doQuery($sql, [
            'source_id' => $data['source_id'],
            'phone' => $data['phone']
        ]);

        if($stm->rowCount() == 0) { // если не нашли таких данных давностью меньше суток, то добавим этот контакт
            $sql = "INSERT INTO `contacts` (`source_id`, `name`, `phone`, `email`) VALUES (:source_id, :name, :phone, :email);";
            $this->doQuery($sql, $data);
        }

        return true;
    }

    /**
     * Получает контакты по телефону
     * return array - данные из БД
     *
     */
    public function getContactsByPhone(string $phone)
    {
        $sql = "SELECT `source_id`, `name`, `phone`, `email`, `created_ts` FROM `contacts` WHERE `phone` = :phone ORDER BY `created_ts`;";
        $stm = $this->doQuery($sql, [
            'phone' => $phone
        ]);
        return $stm->fetchAll();
    }
}
