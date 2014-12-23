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
	@echo "      deploy: push master branch to aris"
	@echo "         dev: push master branch to dev"
	@echo " cache_clear: trigger apc cache clear"
	@echo ""
	@echo "make [all|deploy|cache_clear]"

DEV_CACHE_COMMAND=curl --silent localhost:80/server/resetAPC.php
DEV_CHECKOUT_COMMAND="cd /var/www/html/server/ && sudo git checkout master && sudo git pull && $(DEV_CACHE_COMMAND)"

CACHE_COMMAND=curl --silent localhost:81/server/resetAPC.php
CHECKOUT_COMMAND="cd /var/www/html/server/ && git checkout master && git pull && $(CACHE_COMMAND)"

dev:
	@echo "Pushing to Github."
	@git push 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Deploying to dev."
	@ssh -t aris-dev $(CHECKOUT_COMMAND)
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"

deploy:
	@echo "Pushing to Github."
	@git push 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Deploying to server 1."
	@ssh -t aris-prod1 $(CHECKOUT_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Deploying to server 2."
	@ssh -t aris-prod2 $(CHECKOUT_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Deploying to server 3."
	@ssh -t aris-prod3 $(CHECKOUT_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"

cache_clear:
	@echo "Clearing cache on server 1."
	@ssh -t aris-prod1 $(CACHE_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Clearing cache on server 2."
	@ssh -t aris-prod2 $(CACHE_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Clearing cache on server 3."
	@ssh -t aris-prod3 $(CACHE_COMMAND) 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"

all: deploy cache_clear
