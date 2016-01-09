# joindin-api

This is the API behind the joind.in website (the new version of it), the mobile applications, and many other consumers.  This project is a dependency for the majority of the other projects under the joind.in organization https://github.com/joindin

### Welcome

Joind.in welcomes all contributors regardless of your ability or experience. We especially welcome
you if you are new to Open Source development and will provide a helping hand. To ensure that
everyone understands what we expect from our community, our projects have a [Contributor Code of
Conduct](CODE_OF_CONDUCT.md) and by participating in the development of joind.in you agree to abide
by its terms.

## Quick start with Vagrant

To get you going without much hassle we created a vagrant-setup. To use it [fork the joindin-vm](https://github.com/joindin/joindin-vm) repository and follow the instructions in there.

This VM will load all three Joind.in projects (joind.in, joindin-vm and joindin-web2).

## How to use the API

Go to http://api.joind.in and click around!

There's more documentation here: http://joindin.github.io/joindin-api/ - it's powered by the content of the ``gh-pages`` branch on this repo, patches very welcome there also!

## Tools and Tests

### API Tests

We have tests that make HTTP requests from the outside of the API, functional tests, if you will.

To run the frisby tests (frisby.js), you will first need to install node.js and
npm on you computer.  Then run:

        cd tests/frisby
        npm install

To run the tests on your computer against your Vagrant VM, from the `tests/frisby` directory run:

        npm test

We also have a set of "destructive" tests, these create, edit and delete data as well as just reading it.  These aren't safe to run on a live platform, but are very valuable in testing.  Before you run them, you will need to run this query against your database:

        insert into oauth_consumers (consumer_key, consumer_secret, user_id, enable_password_grant) values ('0000', '1111', '1', '1');

Then run:

        npm run test_write


#### Proxying the frisy tests

If you want to proxy the frisby tests via Charles or another proxy, then export `HTTP_PROXY` first:

    export HTTP_PROXY=http://localhost:8888

You can now run `npm run test_write` or `npm test` as required and all the network requests will go via the proxy.


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

#### Inline Documentation

For inline documentation we recommend the use of PHPDoc-compatible documentation
blocks - for more information, see the draft [PHP FIG PSR-5 PHPDoc Standard](https://github.com/phpDocumentor/fig-standards/blob/master/proposed/phpdoc.md).

Note that we do not use the ```@author``` tag.

### Generating the API Docs

The API docs can be found here: [http://joindin.github.io/joindin-api/](http://joindin.github.io/joindin-api/).  Their source code lives on the ``gh-pages`` branch, and changes should be submitted in pull requests against that branch.

Docs are written in markdown and rendered by [Jekyll](http://jekyllrb.com/), a ruby gem.  You can test this locally by doing the following

* Get set up - the best instructions are GitHub's own and they keep these up to date: [https://help.github.com/articles/using-jekyll-with-pages](https://help.github.com/articles/using-jekyll-with-pages).

* Generate the site: ``jekyll serve``.  This will output the URL of where you can access your local copy of the docs.  For me that's [http://localhost:4000/joindin-api/](http://localhost:4000/joindin-api/) (and the trailing slash does matter!)

## Global .gitignore

git has the capability to define a global gitignore file , which means you can 
set up rules on your machine to ignore everything you don't want to include in 
your commits. This works not only for this project, but for all your other
projects too.

You can define the gitignore file with a command that looks like this, where the 
last argument is the file that holds the patterns to ignore: 

    $ git config --global core.excludesfile ~/.gitignore_global

Octocat gives [a good starting point](https://gist.github.com/octocat/9257657) for 
what to include, but you can also ignore the files used by your editor:

    # Eclipse
    .classpath
    .project
    .settings/
    
    # Intellij
    .idea/
    *.iml
    *.iws
        
    # Maven
    log/
    target/

    # Netbeans
    nbproject/private/

For more info on ignoring files, [github has an excellent help page](https://help.github.com/articles/ignoring-files/).
