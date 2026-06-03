# установка зависимостей

install:
	composer install

# проверка проекта Composer'ом

validate:
	composer validate

# проверка проекта CodeSniffer'ом

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src bin config tests

# Запуск Тестов

test:
	composer exec --verbose phpunit tests -- --display-deprecations
