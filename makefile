# Makefile for the ARIS Server
#
# Deploys to the prod servers. (Make sure they are in your ssh config)
#
# Some output is supressed, just remove the @ or dev/null redirects if troubleshooting.
#
OK_COLOR=\033[0;32m
INFO_COLOR=\033[1;36m
CLEAR=\033[m\017

arisprod1="root@neo.arisgames.org"
arisprod2="root@trinity.arisgames.org"
arisprod3="root@morpheus.arisgames.org"

help:
	@echo "Aris Server"
	@echo ""
	@echo "Targets:"
	@echo "        prod: push master branch to aris"
	@echo "         dev: push master branch to dev"
	@echo " cache_clear: trigger apc cache clear"
	@echo ""
	@echo "make [deploy|upgrade]"

DEV_CACHE_COMMAND=curl --silent localhost:80/server/resetAPC.php
DEV_CHECKOUT_COMMAND="cd /var/www/html/server/ && sudo git checkout master && sudo git pull && $(DEV_CACHE_COMMAND)"

CACHE_COMMAND=curl --silent localhost:81/server/resetAPC.php
CHECKOUT_COMMAND="cd /var/www/html/server/ && git checkout master && git pull && $(CACHE_COMMAND)"

DEV_UPGRADE_COMMAND=curl 'http://dev.arisgames.org/server/json.php/v2.db.upgrade' --silent --data '{}' | tr -d '\r\n'
UPGRADE_COMMAND=curl 'http://arisgames.org/server/json.php/v2.db.upgrade' --silent --data '{}' | tr -d '\r\n'

STATUS_COMMAND="cd /var/www/html/server/ && git fetch && git log -1 --date=short --pretty=format:'%Cred%h%Creset %Cgreen%cd%Creset %C(bold blue)%an%Creset%C(yellow)%d%Creset %s%Creset'"

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
	@ssh -t $(arisprod1) $(CHECKOUT_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Deploying to Production 2."
	@ssh -t $(arisprod2) $(CHECKOUT_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Deploying to Production 3."
	@ssh -t $(arisprod3) $(CHECKOUT_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"

cache_clear_dev:
	@echo "Clearing cache on Production 1."
	@ssh -t aris-dev $(DEV_CACHE_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"

cache_clear_prod:
	@echo "Clearing cache on Production 1."
	@ssh -t $(arisprod1) $(CACHE_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Clearing cache on Production 2."
	@ssh -t $(arisprod2) $(CACHE_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Clearing cache on Production 3."
	@ssh -t $(arisprod3) $(CACHE_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"

upgrade_dev:
	@echo "Upgrading Development$(INFO_COLOR)"
	@$(DEV_UPGRADE_COMMAND)
	@echo "$(CLEAR)"

upgrade_prod:
	@echo "Upgrading Production$(INFO_COLOR)"
	@$(UPGRADE_COMMAND)
	@echo "$(CLEAR)"

status:
	@echo "Commit on Production 1$(INFO_COLOR)"
	@ssh $(arisprod1) $(STATUS_COMMAND)
	@echo "$(CLEAR)"
	@echo "Commit on Production 2$(INFO_COLOR)"
	@ssh $(arisprod2) $(STATUS_COMMAND)
	@echo "$(CLEAR)"
	@echo "Commit on Production 3$(INFO_COLOR)"
	@ssh $(arisprod3) $(STATUS_COMMAND)
	@echo "$(CLEAR)"

cache_clear: cache_clear_prod
deploy: prod
upgrade: upgrade_prod

all: deploy upgrade
