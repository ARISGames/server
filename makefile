host="fieldday-deploy@fieldday-web.ad.education.wisc.edu"
repo="/var/www/arisgames.org/server"
upgrade_url="http://arisgames.org/server/json.php/v2.db.upgrade"

deploy:
	@ssh -t $(host) cd $(repo) && git checkout master && git pull 1>/dev/null

upgrade:
	@curl $(upgrade_url) --silent --data '{}' | tr -d '\r\n'

