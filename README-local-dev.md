# Quick start with the PHP dev server

The API will run happily under the [PHP development server](http://php.net/manual/en/features.commandline.webserver.php).  You will need to have a MySQL running with the Joind.in schema availble, referenced from the src/database.php file (see below about initialising the DB).  Note that the web2 site will also run under the built-in webserver, but will need to be on a different listening port.

To run the API on http://localhost:8080/, do the following:
```
cd src/public
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

## Supporting Elasticsearch

The new search is supported by elasticsearch. To install and populate, follow the instruction following:

First off, elasticsearch relies on java. 

```
sudo apt-get install openjdk-7-jre
```

Next, let's add the necessary keys and add ES repositories to apt so we can install from the package manager, then install.

```
wget -qO - https://packages.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
echo "deb http://packages.elastic.co/elasticsearch/1.5/debian stable main" | sudo tee -a /etc/apt/sources.list
sudo apt-get update && sudo apt-get install elasticsearch
```

If you wish to run elasticsearch on VM boot, run:

```
sudo update-rc.d elasticsearch defaults 95 10
```

Finally for install, start the service.

```
sudo service elasticsearch start
```

Now that we're all installed we need to import the current data available to us. We need the SDK.

```
curl -sS https://getcomposer.org/installer | php
./composer.phar install --no-dev
```

Now we're all set up and ready for data. Running the following should put everything in place automatically for you.

```
php scripts/reindex-all.php
```

Go ahead and start your searches.

