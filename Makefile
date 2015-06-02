# Makefile for the ARIS Server
#
# Deploys to the prod servers. (Make sure they are in your ssh config)
#
# Some output is supressed, just remove the @ or dev/null redirects if troubleshooting.
#
OK_COLOR=\033[0;32m
INFO_COLOR=\033[1;36m
CLEAR=\033[m\017

help:
	@echo "Aris Server"
	@echo ""
	@echo "Targets:"
	@echo "        prod: push master branch to aris"
	@echo "         dev: push master branch to dev"
	@echo " cache_clear: trigger apc cache clear"
	@echo ""
	@echo "make [deploy]"

DEV_CACHE_COMMAND=curl --silent localhost:80/server/resetAPC.php
DEV_CHECKOUT_COMMAND="cd /var/www/html/server/ && sudo git checkout master && sudo git pull && $(DEV_CACHE_COMMAND)"

CACHE_COMMAND=curl --silent localhost:81/server/resetAPC.php
CHECKOUT_COMMAND="cd /var/www/html/server/ && git checkout master && git pull && $(CACHE_COMMAND)"

DEV_MIGRATE_COMMAND=curl 'http://dev.arisgames.org/server/json.php/v2.db.upgrade' --silent --data '{}' | tr -d '\r\n'
MIGRATE_COMMAND=curl 'http://arisgames.org/server/json.php/v2.db.upgrade' --silent --data '{}' | tr -d '\r\n'

STATUS_COMMAND="cd /var/www/html/server/ && git log -1 --date=short --pretty=format:'%Cred%h%Creset %Cgreen%cd%Creset %C(bold blue)%an%Creset%C(yellow)%d%Creset %s%Creset'"

dev:
	@echo "Pushing to Github."
	@git push 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Deploying to Development."
	@ssh -t aris-dev $(CHECKOUT_COMMAND)
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"

prod:
	@echo "Pushing to Github."
	@git push 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Deploying to Production 1."
	@ssh -t aris-prod1 $(CHECKOUT_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Deploying to Production 2."
	@ssh -t aris-prod2 $(CHECKOUT_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Deploying to Production 3."
	@ssh -t aris-prod3 $(CHECKOUT_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"

cache_clear_dev:
	@echo "Clearing cache on Production 1."
	@ssh -t aris-dev $(DEV_CACHE_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"

cache_clear_prod:
	@echo "Clearing cache on Production 1."
	@ssh -t aris-prod1 $(CACHE_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Clearing cache on Production 2."
	@ssh -t aris-prod2 $(CACHE_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Clearing cache on Production 3."
	@ssh -t aris-prod3 $(CACHE_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"

migrate_dev:
	@echo "Migrating Development$(INFO_COLOR)"
	@$(DEV_MIGRATE_COMMAND)
	@echo "$(CLEAR)"

migrate_prod:
	@echo "Migrating Production$(INFO_COLOR)"
	@$(MIGRATE_COMMAND)
	@echo "$(CLEAR)"

status:
	@echo "Commit on Production 1$(INFO_COLOR)"
	@ssh aris-prod1 $(STATUS_COMMAND)
	@echo "$(CLEAR)"
	@echo "Commit on Production 2$(INFO_COLOR)"
	@ssh aris-prod2 $(STATUS_COMMAND)
	@echo "$(CLEAR)"
	@echo "Commit on Production 3$(INFO_COLOR)"
	@ssh aris-prod3 $(STATUS_COMMAND)
	@echo "$(CLEAR)"

cache_clear: cache_clear_prod
deploy: prod
migrate: migrate_prod

all: deploy migrate
