# Запуск gendiff

gendiff:
	./bin/gendiff

# проверка проекта CodeSniffer'ом

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src bin config tests

# Запуск Тестов

test:
	composer exec --verbose phpunit tests -- --display-deprecations
