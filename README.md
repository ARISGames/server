ARIS
====

Requirements
------------

* php 5.3
* mysql

Install
-------

* Copy `config.class.php.template` to `config.class.php`
* Modify to point to your mysql database.
* Import `services/v2/db/upgrades_table.sql` into your database.
* Visit `<aris server>/services/v2/autocrud/index.html`
* Use autocrud to GET `db.upgrade' to create all aris tables.
* Register a user in the database and play with the API!

Errata
------

* For OSX, xampp version 1.7.3 (an older download) works as of (2/10/2015)
* If the json does not parse, the server is returning deprecated php warnings. Disable by setting `error_reporting  =  E_ALL & ~E_NOTICE & ~E_DEPRECATED` in your `php.ini`
* Make sure the web server has permission to write to the `v2_gamedata_folder` and that it is an absolute path.
