# Quick start with the PHP dev server

The API will run happily under the [PHP development server](http://php.net/manual/en/features.commandline.webserver.php).  You will need to have a MySQL running with the Joind.in schema availble, referenced from the src/database.php file (see below about initialising the DB).  Note that the web2 site will also run under the built-in webserver, but will need to be on a different listening port.

To run the API on http://localhost:8080/, do the following:
```
cd public
export JOINDIN_API_BASE_URL=http://localhost:8081
php -S localhost:8081 index.php
```

## Initialising the database

As you are not using the Vagrant setup, you will need to provide your own MySQL database and configure it in src/database.php.  Once there is an empty database and a username/password, you can setup the tables by running the patch script at scripts/patchdb.sh with the "-i" (initialise DB) option.

```
cd scripts
./patchdb.sh -t ../ -d <DB name> -u <DB username> -p <DB password> -i
```

If you already have a development DB, but you want to patch it up to the latest structure, then run the same command except omit the "-i" option.

```
cd scripts
./patchdb.sh -t ../ -d <DB name> -u <DB username> -p <DB password>
```
