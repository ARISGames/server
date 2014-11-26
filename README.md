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
* Visit `<aris server>/json.php/v2.db.upgrade' to create all aris tables.
* Visit `<aris server>/services/v2/autocrud/index.html` to register a user and test out the API.
