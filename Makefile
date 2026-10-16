PHP        ?= php
PHP_IMAGE  ?= php:8.4-cli

.DEFAULT_GOAL := help

.PHONY: help test test-docker example

help: ## Mostra esta ajuda
	@grep -E '^[a-z-]+:.*## ' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  %-14s %s\n", $$1, $$2}'

test: ## Roda os testes (usa php local, ou docker se nao tiver php)
	@if command -v $(PHP) >/dev/null 2>&1; then $(PHP) tests/run.php; else $(MAKE) --no-print-directory test-docker; fi

test-docker: ## Roda os testes num container $(PHP_IMAGE)
	@tar c --exclude=.git . | docker run --rm -i $(PHP_IMAGE) sh -c 'mkdir /app && tar x -C /app && cd /app && php tests/run.php'

example: ## Roda o exemplo JSON com o dhcpd.leases.sample
	@$(PHP) examples/json.php dhcpd.leases.sample
