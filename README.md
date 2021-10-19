### .env

DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7"

db_user = пользователь
db_password = пароль
db_name = имя базы

### Установка (папка с проектом)

    composer install

### Запуск миграции

    php bin/console doctrine:migrations:migrate

### Запуск проекта

    php bin/console server:run