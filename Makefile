APP_CONTAINER=docker-compose exec -T php sh -c

dockerize:
	@echo "Installing and starting project..."
	@docker-compose down
	@docker network inspect enodeus >/dev/null 2>&1 || docker network create --driver bridge enodeus
	@cp .env.example .env
	@docker-compose up -d --build
	@$(APP_CONTAINER) "composer install --no-interaction;"

start-up:
	@docker-compose down
	@docker network inspect test-net >/dev/null 2>&1 || docker network create --driver bridge test-net
	@docker-compose up -d

# make test ARGS="tests/Feature/Endpoints/Graphql/TodoCreateTest.php"
test:
	@$(APP_CONTAINER) "XDEBUG_MODE=coverage vendor/bin/phpunit tests --coverage-text $(ARGS)"

generate-coverage:
	@$(APP_CONTAINER) "php -dxdebug.mode=coverage ./vendor/phpunit/phpunit/phpunit --coverage-html ./public/build/coverage-report tests --stop-on-failure; \
	chmod -R 777 .";

fix-permissions:
	@echo "Fixing file permissions..."
	@$(APP_CONTAINER)  "chmod -R 777 ."
	@echo "Done."