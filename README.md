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
