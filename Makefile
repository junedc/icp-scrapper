# =========================
# ICP Scrapper Makefile
# Uses the shared Docker workspace compose file
# =========================

.DEFAULT_GOAL := bash

COMPOSE_FILE=../docker/docker-compose.yml
DC=docker compose -f $(COMPOSE_FILE)

APP=icp-scrapper-app
NGINX=icp-scrapper-nginx
MYSQL=mysql
REDIS=redis

# -------------------------
# Core Docker controls
# -------------------------
ps:
	$(DC) ps

up:
	$(DC) up -d $(APP) $(NGINX)

build:
	$(DC) up -d --build $(APP) $(NGINX)

down:
	$(DC) stop $(APP) $(NGINX)

restart:
	$(DC) restart $(APP) $(NGINX)

logs:
	$(DC) logs -f $(APP) $(NGINX)

logs-app:
	$(DC) logs -f $(APP)

logs-nginx:
	$(DC) logs -f $(NGINX)

# -------------------------
# Shell access
# -------------------------
bash:
	$(DC) exec $(APP) sh -lc 'printf '\''alias pa="php artisan"\n'\'' >/tmp/.pa_aliases && ENV=/tmp/.pa_aliases exec sh -i'

shell: bash

bash-nginx:
	$(DC) exec $(NGINX) sh

bash-mysql:
	$(DC) exec $(MYSQL) sh

bash-redis:
	$(DC) exec $(REDIS) sh

# -------------------------
# Laravel helpers
# -------------------------
artisan:
	@if [ -z "$(CMD)" ]; then \
		echo "Usage: make artisan CMD='migrate:status'"; \
		exit 1; \
	fi
	$(DC) exec $(APP) php artisan $(CMD)

composer:
	@if [ -z "$(CMD)" ]; then \
		echo "Usage: make composer CMD='install'"; \
		exit 1; \
	fi
	$(DC) exec $(APP) composer $(CMD)

npm:
	@if [ -z "$(CMD)" ]; then \
		echo "Usage: make npm CMD='run dev'"; \
		exit 1; \
	fi
	$(DC) exec $(APP) npm $(CMD)

keygen:
	$(DC) exec $(APP) php artisan key:generate

migrate:
	$(DC) exec $(APP) php artisan migrate

migrate-fresh:
	$(DC) exec $(APP) php artisan migrate:fresh --seed

seed:
	$(DC) exec $(APP) php artisan db:seed

optimize:
	$(DC) exec $(APP) php artisan optimize

optimize-clear:
	$(DC) exec $(APP) php artisan optimize:clear

cache-clear:
	$(DC) exec $(APP) php artisan cache:clear

config-clear:
	$(DC) exec $(APP) php artisan config:clear

route-clear:
	$(DC) exec $(APP) php artisan route:clear

view-clear:
	$(DC) exec $(APP) php artisan view:clear

tinker:
	$(DC) exec $(APP) php artisan tinker

queue-work:
	$(DC) exec $(APP) php artisan queue:work

test:
	$(DC) exec $(APP) php artisan test --compact

pint:
	$(DC) exec $(APP) vendor/bin/pint --dirty --format=agent

# -------------------------
# Dependency helpers
# -------------------------
composer-install:
	$(DC) exec $(APP) composer install

composer-update:
	$(DC) exec $(APP) composer update

npm-install:
	$(DC) exec $(APP) npm install

npm-dev:
	$(DC) exec $(APP) npm run dev

npm-build:
	$(DC) exec $(APP) npm run build

# -------------------------
# Database / service helpers
# -------------------------
migrate-status:
	$(DC) exec $(APP) php artisan migrate:status

mysql:
	$(DC) exec $(MYSQL) mysql -uroot -p$${MYSQL_ROOT_PASSWORD}

redis-ping:
	$(DC) exec $(REDIS) redis-cli ping

# -------------------------
# Help
# -------------------------
help:
	@echo "Common:"
	@echo "  make up | build | down | restart | ps | logs"
	@echo ""
	@echo "Shell:"
	@echo "  make bash | shell | bash-nginx | bash-mysql | bash-redis"
	@echo ""
	@echo "Laravel:"
	@echo "  make artisan CMD='migrate:status'"
	@echo "  make composer CMD='install'"
	@echo "  make npm CMD='run dev'"
	@echo "  make keygen | migrate | migrate-fresh | seed | migrate-status"
	@echo "  make optimize | optimize-clear | cache-clear | config-clear | route-clear | view-clear"
	@echo "  make tinker | queue-work | test | pint"
	@echo ""
	@echo "Dependencies:"
	@echo "  make composer-install | composer-update | npm-install | npm-dev | npm-build"
	@echo ""
	@echo "Services:"
	@echo "  make mysql | redis-ping"
