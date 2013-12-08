# joindin-api

This is the API behind the joind.in website (the new version of it), the mobile applications, and many other consumers.  This project is a dependency for the majority of the other projects under the joind.in organization https://github.com/joindin


## Quick Start

 * point a new virtual host at the public directory, one level below this file.
 * copy `src/config.php.dist` to `src/config.php` in your joind.in installation.
 * copy `src/database.php.dist` to `src/database.php` in your joind.in installation; if you have the website project installed too, these files are the same and you may copy or symlink.
 * initialise, patch, and populate the database.

    scripts/patchdb.sh -t /path/to/joind.in -d joindin -u username -p password -i

(use the correct username and password)

If you are using Windows And/Or Git bash you may see an error regarding "o being an invalid option" when running step 6.

To fix this, you will need to visit http://gnuwin32.sourceforge.net/packages/grep.htm and download the binaries and dependencies zip files Extract the contents of the bin folder from the zip files to the bin folder of your Git install and restart Git Bash.

Alternatively in Windows, you can use the php verison of the helper script, `scripts/patchdb.php`:
```
    php scripts/patchdb.php -t c:\pathto\joind.in\ -d joindin -u mysqluser -p mysqlpassword -i
```

This should also work for git via the commandline (cmd.exe)
 * generate some sample data - instructions are in /tools/dbgen/README.md
 * the mbstring extension is required.

## How to use the API

Go to http://api.joind.in and click around!

There's more documentation here: http://joind.in/api/v2docs

## Tools and Tests

### API Tests

We have tests that make HTTP requests from the outside of the API, functional tests, if you will.

To run the frisby tests (frisby.js), you will first need to install node.js and
npm.  Then run:

        npm install -g frisby jasmine-node

I also found that I needed:

        export NODE_PATH=/usr/local/lib/node_modules

You should set the URL that the tests run against to be your local installation:

        export JOINDIN_API_BASE_URL=http://api.joindin.localhost

Then run the tests by going to `/src/tests/api_tests` and running:

        jasmine-node newapi_spec.js

### Unit Tests

There are some tests set up, which use PHPUnit; these can be found in the
tests directory.  There is a phing task
configured to run them - from the root directory simply run `phing phpunit` to run
the tests. Unfortunately, there will be no output about whether the tests passed
or failed from the phing target. A better way to test when you are developing is
to run the tests from within the tests directory by just typing
`phpunit`. The phpunit.xml in each directory will configure the bootstrap as well
as any files that should not be included.

### Database Patches

If you need to include a new patch, then create the SQL needed and add it to the next patch number in the `db` directory. You need to include a line that looks like this at the end of your script:

    INSERT INTO patch_history SET patch_number = 17;  

The number in that line should match the filename of your new patch number - check out the existing database patches in the project for examples.

### Coding Style

Please do your best to ensure that any code you contributed adheres to the
Joind.in coding style. This is roughly equivalent to the PEAR coding standard with
a couple of rules added or taken out. You can run php codesniffer using phing on an
individual file like so:

    phing phpcs-human -Dfilename.php

This will run codesniffer on any file within the regular source for Joind.in or the
API-v2 source. Wildcards work as does specifying part of the path in case the
filename alone results in sniffing more files than you wanted.

To see a summary of the codesniff errors and warnings across the entire project, run

    phing phpcs-human-summary


