<?php

Class Base {
    private $routers = [ // доступные действия
        'save_contacts',
        'get_contacts',
    ];
    private $MySQL; // доступ к БД

    public function __construct($config)
    {
        $this->MySQL = new MySQL($config['mysql']);
    }

    public function run()
    {
        header('Content-Type: application/json; charset=utf-8'); // возвращаем JSON
        $json_data = [ // дефолтные данные
            'status' => 'ok', // ok/error
            'message' => '', // сообщение об ошибке
        ];
        if(!isset($_GET['act'])) { // если не указан action
            $this->defaultAction();
        } elseif (!in_array($_GET['act'], $this->routers)) { // если запрос несуществующих действий
            header("HTTP/1.1 404 Not Found");
            $json_data['status'] = 'error';
            $json_data['message'] = '404 Not Found';
        } else {
            switch($_GET['act']) {
                case 'save_contacts':
                    $this->saveContactsAction($json_data);
                    break;
                case 'get_contacts':
                    $this->getContactsAction($json_data);
                    break;
            }
        }
        echo json_encode($json_data);
    }

    /**
     * Отображает стартовую страницу с описанием доступных методов API
     *
     */
    private function defaultAction()
    {
        header('Content-Type: text/html; charset=UTF-8'); // возвращаем HTML
        ob_get_clean(); ?>

<h1>Доступные дейстия API</h1>
<br>
<h2>Добавление контактных данных клиентов (пакетно)</h2>
<p>URI: <strong>index.php?act=save_contacts</strong></p>
<p>Method - POST</p>
<p>Возвращает json с результатами выполненых действий</p>
<h3>Входные данные</h3>
<code>
    <pre>
{
    source_id: (int), // id источника клиентов, для примера достаточно 1 и 2
    items: [
        {
            "name": (string),
            "phone": (string), // телефон в формате (+7)/(8)/()хххххххххх
            "email": (email)
        },
        ...
    ]
}
    </pre>
</code>

<h3>Результирующие данные</h3>
<code>
    <pre>
{
    [{
        "status": (string), // "ok"/"error" - успех/ошибка
        "message": (string) // сообщение об ошибке
        "data": [{
            "count": (int) // количество сохраненных данных
        }]
    }]
}
    </pre>
</code>
<br>
<h2>Выборка контактных данных клиентов по совпадению телефона</h2>
<p>URI: <strong>index.php?act=get_contacts&phone=хххххххххх</strong></p>
<p>Method - GET</p>
<p>Возвращает json с найденными данными</p>
<h3>Входные данные</h3>
<code>
    <pre>
phone: (int), // телефон в формате хххххххххх
    </pre>
</code>

<h3>Результирующие данные</h3>
<code>
    <pre>
{
    [{
        "status": (string), // "ok"/"error" - успех/ошибка
        "message": (string) // сообщение об ошибке
        "data": : [{
            "source_id": "1",
            "name": "Анна",
            "phone": "9001234453",
            "email": "mail1@gmail.com",
            "created_ts": "2022-02-26 01:43:30" // дата создания записи
        }]
    }]
}
    </pre>
</code>

        <?php $res = ob_get_contents();
        die($res);
    }

    /**
     * Сохраняет данные из POST в БД
     * params array $json_data - ссылка на возвращаемые данные для заполнения
     *
     */
    private function saveContactsAction(&$json_data)
    {
        $post_data = file_get_contents('php://input');
        $post_arr = json_decode($post_data, true);

        if(empty($post_arr['source_id']) || !is_int($post_arr['source_id'])) {
            $json_data['status'] = 'error';
            $json_data['message'] = 'Invalid data type "source_id"';
            return;
        }
        if(empty($post_arr['items']) || !is_array($post_arr['items'])) {
            $json_data['status'] = 'error';
            $json_data['message'] = 'Invalid data type "items"';
            return;
        }
        $added_cnt = 0; // подсчет добавленных данных
        // данные давностью меньше суток не добавляются в БД, но считаются добавленными
        // не считаются только данные, у которых неверные данные и их нельзя добавить в БД
        foreach($post_arr['items'] as $item) {
            $new_contact = [
                'source_id' => $post_arr['source_id']
            ];
            // валидация name
            if(empty($item['name'])) { // неверный формат имени
                continue;
            } else {
                $new_contact['name'] = $item['name'];
            }

            // валидация phone
            $matches = [];
            preg_match('/^(\+7|8)?(9[0-9]{9})$/', $item['phone'], $matches);
            if(empty($matches[2])) { // неверный формат телефона
                continue;
            } else {
                $new_contact['phone'] = $matches[2];
            }

            // валидация email
            $matches = [];
            preg_match('/^[A-z0-9.]+@[A-z0-9.]+$/', $item['email'], $matches);
            if(empty($matches[0])) { // неверный формат телефона
                continue;
            } else {
                $new_contact['email'] = $matches[0];
            }

            $this->MySQL->saveContact($new_contact);

            $added_cnt++;
        }

        $json_data['data'] = [
            'count' => $added_cnt
        ];
    }

    /**
     * Получает данные из БД по телефону
     * params array $json_data - ссылка на возвращаемые данные для заполнения
     *
     */
    private function getContactsAction(&$json_data)
    {
        if(empty($_GET['phone']) || !is_numeric($_GET['phone'])) {
            $json_data['status'] = 'error';
            $json_data['message'] = 'Invalid data type "phone"';
            return;
        }
        $phone = $_GET['phone'];

        $contacts = $this->MySQL->getContactsByPhone($phone);

        $json_data['data'] = $contacts;
    }
}
