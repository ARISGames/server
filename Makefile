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
	@echo " cache_clear: trigger apc cache clear"
	@echo ""
	@echo "make [all|deploy|cache_clear]"

deploy:
	@echo "Pushing to Github."
	@git push 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Deploying to server 1."
	@ssh aris-prod1 "cd /var/www/html/server/ && git checkout master && git pull" 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Deploying to server 2."
	@ssh aris-prod2 "cd /var/www/html/server/ && git checkout master && git pull" 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"
	@echo "Deploying to server 3."
	@ssh aris-prod3 "cd /var/www/html/server/ && git checkout master && git pull" 1>/dev/null
	@echo "   $(OK_COLOR)(Done)$(CLEAR)"

cache_clear:

all: deploy cache_clear
