# Getting Started

Please download or clone [HCSF-Skeleton](https://github.com/HaaseIT/HCSF-Skeleton) and do a composer install to set up a
new installation.

The directory `web` is the public facing directory, it can be renamed to fit your needs.

In the directory `web`, you will find a `.htaccess` file for the apache httpd server, if you use a different webserver,
you need to set up a ruleset to emulate the rules found there.

In the `config` directory of the skeleton you will find serveral yaml configuration files, please rename them as needed from
`*.dist.yml` to `*.yml`. These config files are loaded in addition to the default config files from the package, the
local config directives will always overwrite the package config.

Please see the associated pages for each configuration file:
[core.yml](./configfiles/core.yml.md),
[countries.yml](./configfiles/countries.yml.md),
[customer.yml](./configfiles/customer.yml.md),
[navigation.yml](./configfiles/navigation.yml.md),
[secrets.yml](./configfiles/secrets.yml.md) and
[shop.yml](./configfiles/shop.yml.md)

In the `setup` directory of the package you will find scripts for setting up the database: `create-tables.sql` will set
up the database structure for you and `starterset-data.sql` contains data to get you started. Run these scripts in the
database-client of your choice.

The following directories in the skeleton must be writable by the webserver: `hcsflogs`, `cache`.

### Administration

At `http://www.yourhost.tld/_admin/` you will find the [admin panel](./administration/README.md).

At the startpage of the admin panel you will find data if the requirements are met. Here you can also encrypt passwords
to use in the file `secrets.yml` to authenticate with the admin panel.

As long as there are no users set in `secrets.yml`, you can access this page (but not the other pages in the admin area)
without authenticating.

### Templates

In the skeleton, you will find a directory `customviews`. Please put your [TWIG](https://twig.sensiolabs.org/) templates
here to customize your site. Again, these will overwrite the package templates, which you can find in `src/views/`.

### Item images

This software is using the [Glide](http://glide.thephpleague.com/) image processing package. Right now it is mainly used
for item images. Put your item images in the folder `glideimagemaster` and into the subfolder `items` to use them as
setup in the package templates.

### How do things work / help, I want to do X?

You will find every feature of this software used somewhere in the starter-data and the default templates. Yes, it might
be tricky to find. Please just contact me at [mail@haase-it.com](mail@haase-it.com) if you cant find X on your own.
