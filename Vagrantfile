# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|
  config.vm.box = "hashicorp/precise32"

  config.vm.network "forwarded_port", guest: 80, host: 10080

  # The repo is at /vagrant by default, but we need to adjust permissions
  config.vm.synced_folder "./", "/vagrant", id: "vagrant-root",
    owner: "vagrant",
    group: "www-data",
    mount_options: ["dmode=775,fmode=664"]

  config.vm.provision 'shell', inline: <<-SHELL
    apt-get update
    echo "mysql-server-5.5 mysql-server/root_password password root" | sudo debconf-set-selections
    echo "mysql-server-5.5 mysql-server/root_password_again password root" | sudo debconf-set-selections
    apt-get install -y lamp-server^
    ln -fs /vagrant /var/www/server
    touch /var/log/aris_error_log.txt
    chmod 777 /var/log/aris_error_log.txt
  SHELL

  config.vm.provision 'shell', privileged: false, inline: <<-SHELL
    cd /vagrant
    mysql --user=root --password=root -e "CREATE DATABASE arisv1"
    mysql --user=root --password=root arisv1 < migrations/0.sql
    mysql --user=root --password=root -e "CREATE DATABASE migration_db"
    mysql --user=root --password=root migration_db < services/migration/reset_database.sql
    mysql --user=root --password=root -e "CREATE DATABASE arisv2"
    mysql --user=root --password=root arisv2 < services/v2/db/upgrades_table.sql
    cp config.class.php.vagrant config.class.php
    wget --no-cache --spider "http://localhost/server/json.php/v2.db.upgrade" --post-data="{}"
  SHELL
end
