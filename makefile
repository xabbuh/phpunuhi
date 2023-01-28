# Makefile  Project

.PHONY: help
.DEFAULT_GOAL := help


#------------------------------------------------------------------------------------------------

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

#------------------------------------------------------------------------------------------------

install: ## Installs all prod dependencies
	composer install --no-dev

dev: ## Installs all dev dependencies
	composer install --ignore-platform-req=ext-intl

clean: ## Clears all dependencies
	rm -rf vendor/*

#------------------------------------------------------------------------------------------------

csfix: ## Starts the PHP CS Fixer
	PHP_CS_FIXER_IGNORE_ENV=1 php ./vendor/bin/php-cs-fixer fix --config=./.php_cs.php --dry-run

stan: ## Starts the PHPStan Analyser
	php ./vendor/bin/phpstan analyse --memory-limit 1G -c ./.phpstan.neon

phpunit: ## Runs all tests
	XDEBUG_MODE=coverage php ./vendor/bin/phpunit --configuration=./.phpunit.xml -v --coverage-html ./.reports/phpunit/coverage

phpinsights: ## Starts PHPInsights
	@php -d memory_limit=2000M vendor/bin/phpinsights --no-interaction

#------------------------------------------------------------------------------------------------

pr: ## Runs and prepares everything for a pull request
	PHP_CS_FIXER_IGNORE_ENV=1 php ./vendor/bin/php-cs-fixer fix --config=./.php_cs.php
	@make phpunit -B
	@make stan -B

#------------------------------------------------------------------------------------------------

build: ## Builds PHPUnuhi and creates phpunuhi.phar
	@make install -B
	@echo "===================================================================="
	@echo "verifying if phar files can be created....phar.readonly has to be OFF"
	@php -i | grep phar.readonly
	@php -i | grep "Loaded Configuration"
	@cd scripts && php build.php

release: ## Create a ZIP file in the build folder
	cd build && zip phpunuhi.zip phpunuhi.phar
	cd build && rm -rf phpunuhi.phar
