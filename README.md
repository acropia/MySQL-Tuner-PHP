MySQL-Tuner-PHP
===============

MySQL-Tuner-PHP is a reporting script to easily view statistics and configuration options use can use to tune / optimize
your MySQL or MariaDB database server.


Requirements
------------
- Any PHP enabled webserver (e.g. Apache, NGINX, IIS)
- PHP 7.3+
- PHP PDO and PDO for MySQL - https://www.php.net/manual/en/ref.pdo-mysql.php


Installation
------------
You can easily download the PHP-script by using a CLI web client like `wget`:

```bash
cd /var/www/yoursite
wget https://raw.githubusercontent.com/acropia/MySQL-Tuner-PHP/master/mt.php
```

or use `git` client:

```bash
cd /var/www/yoursite
git clone https://github.com/acropia/MySQL-Tuner-PHP.git .
```


Configuration
-------------
Currently the script uses three Environment Variables for injecting database credentials:
- `MTP_USER` - MySQL user name
- `MTP_PASS` - MySQL password
- `MTP_HOST` - MySQL hostname


Usage
-----
Visit the PHP-script `mt.php` by a web browser via your website (or run the script remote from a seperate PHP-server):
- http(s)://yoursite.tld/mt.php

The web server will connect to the given database server and will execute various queries to the database server. When
all results are gathered, it will render a Bootstrap 4 based webpage with a report of all findings.


Author
------
- Jan Bouma (acropia), acropia@gmail.com


Contact
-------
Acropia
https://acropia.nl
https://github.com/acropia