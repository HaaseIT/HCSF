HCSF - A multilingual CMS and Shopsystem
Copyright (C) 2014  Marcus Haase - mail@marcus.haase.name

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

Requirements:
Apache 2.x with mod_rewrite enabled
PHP 5.4.x

Dependencies:
TWIG 1.16.2 and up, see: http://twig.sensiolabs.org/ (install via composer)
PHPMailer 5.2.9 and up, see: https://github.com/PHPMailer/PHPMailer (install via composer)
Symfony YAML Component (installed via composer)

In the setup directory you will find scripts for setting up the database.

In the app/config directory you will find example config files.
Save a copy of each and replace the example in the filename with inc
like this: config.core.example.php -> config.core.inc.php

Rename the config.*.example.php to config.*.php and edit them to your needs.
Put the required libraries into their configured paths.
Run the db-scripts.sql on your configured database to init the database.
Configure user auth for the /_admin/ directory in the .htaccess file

For production use turn of display_errors at the beginning of app/init.php

The following directories must be writeable by the webserver:
web/_admin/orderlogs (log directory for orders)
web/_admin/ipnlogs/ (log directory for paypal transactions)
templatecache

at http://www.yourhost.tld/_admin/ you will find an info if these directories exist
and are writable