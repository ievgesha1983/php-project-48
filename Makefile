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

# Формирование файла покрытия тестами

test-coverage:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-clover=build/logs/clover.xml

# Вывод покрытия тестами на экран

test-coverage-text:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-text

