# alliance

Необходимые шаги:
1. База данных MySQL. Создать пустую базу данных в кодировке "utf8_general_ci".
2. Разместить PHP файлы в корневой каталог веб-сервера
3. Заполнить config.php
4. API предоставляет два действия:
- Описание доступных действий API http://localhost/index.php
- Добавление новых данных о контактах http://localhost/index.php?act=save_contacts
- Получение данных о контактах по номеру телефона http://localhost/index.php?act=get_contacts&phone=xxxxxxxxxx
