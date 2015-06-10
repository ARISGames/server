ARIS
====

Requirements
------------

* php 5.3
* mysql

Install
-------

* Copy `config.class.php.template` to `config.class.php`
* Modify to point to your mysql databases and app directories.
* Import `migrations/0.sql` into your v1 database.
* Import `services/migration/reset_database.sql` into your migration database.
* Import `services/v2/db/upgrades_table.sql` into your v2 database.
* Visit `<aris server>/services/v2/autocrud/index.html`
* Use autocrud to GET `db.upgrade' to create all aris tables.
* Register a user in the database and play with the API!

Errata
------

* For OSX, xampp version 1.7.3 (an older download) works as of (2/10/2015)
* If the json does not parse, the server is returning deprecated php warnings. Disable by setting `error_reporting  =  E_ALL & ~E_NOTICE & ~E_DEPRECATED` in your `php.ini`
* Make sure the web server has permission to write to the `v2_gamedata_folder` and that it is an absolute path.

Vagrant
-------

You can also use [Vagrant](https://www.vagrantup.com/) to run the server inside a virtual machine.

* Run `vagrant up` from the repo root to create and set up the machine.

* The repo is shared inside the machine at both `/vagrant` and `/var/www/server`.

* Port 10080 on the host machine is forwarded to 80 on the guest, so for example
  you can access the autocrud page at <http://localhost:10080/server/services/v2/autocrud/>

* Use the following info to access the database by SSH tunneling into the virtual machine:

  * Host: `127.0.0.1`
  * Username: `root`
  * Password: `root`
  * Database: `arisv2`
  * SSH host: `127.0.0.1`
  * SSH user: `vagrant`
  * SSH password: `vagrant`
  * SSH port: `2222`
