[![Build Status](https://travis-ci.org/HaaseIT/HCSF.svg?branch=master)](https://travis-ci.org/HaaseIT/HCSF)
[![Latest Stable Version](https://poser.pugx.org/haaseit/hcsf/version)](https://packagist.org/packages/haaseit/hcsf)
[![License](https://poser.pugx.org/haaseit/hcsf/license)](https://packagist.org/packages/haaseit/hcsf)
[![composer.lock available](https://poser.pugx.org/haaseit/hcsf/composerlock)](https://packagist.org/packages/haaseit/hcsf)
[![Code Climate](https://codeclimate.com/github/HaaseIT/HCSF/badges/gpa.svg)](https://codeclimate.com/github/HaaseIT/HCSF)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/e0829327-0c57-41a6-8b72-ded8870bcfe3/mini.png)](https://insight.sensiolabs.com/projects/e0829327-0c57-41a6-8b72-ded8870bcfe3)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/HaaseIT/HCSF/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/HaaseIT/HCSF/?branch=master)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/b3d0bd22543c430898c73a599825c255)](https://www.codacy.com/app/HaaseIT/HCSF?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=HaaseIT/HCSF&amp;utm_campaign=Badge_Grade)

HCSF - A multilingual CMS and Shopsystem
Copyright (C) 2014-2015  Marcus Haase - mail@marcus.haase.name

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

HCSF has moved to a package format, please clone [HCSF-Skeleton](https://github.com/HaaseIT/HCSF-Skeleton) and composer install to set up a new installation.

Requirements:
- Apache 2.x with mod_rewrite enabled, runs with mod_php and also php5-fpm/fastcgi. I'm pretty sure, it will run on other platforms as well.
- PHP 5.5.x and up with gd (for image processing on the fly), filter and bcmath extension enabled

Dependencies will be installed with composer.

In the setup directory you will find scripts for setting up the database.

In the config directory you will find the default config files.
To override the default config, create a copy of the respective file and name it like this:
-  secrets.yml -> secrets.local.yml

You only need to add the values you want to override in these files.

Put the required libraries into their configured paths.
Run the db-scripts.sql on your configured database to init the database.

For production use turn of display_errors at the beginning of app/init.php

The following directories must be writable by the webserver:
- hcsflogs (log directory)
- cache
at http://www.yourhost.tld/_admin/ you will find an info if these directories exist
and are writable

Set your encrypted admin password in secrets.local.yml.

You will find a tool to encrypt it at /_admin/index.html - As long as there are no users set
in config.scrts.yml, you can access this page (but not the other pages in the admin area)
without authenticating.

Add your custom views (templates) to /customviews (get default views at /src/views)
