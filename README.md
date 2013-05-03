# joindin-api

This is the API behind the joind.in website (the new version of it), the mobile applications, and many other consumers.  This project is a dependency for the majority of the other projects under the joind.in organization https://github.com/joindin


## Quick Start

 * point a new virtual host at the public directory, one level below this file.
 * copy `src/database.php.dist` to `src/database.php` in your joind.in installation; if you have the website project installed too, these files are the same and you may copy or symlink.
 * initialise, patch, and populate the database.

    scripts/patchdb.sh -t /path/to/joind.in -d joindin -u username -p password -i
(use the correct username and password)

If you are using Windows And/Or Git bash you may see an error regarding "o being an invalid option" when running step 6.

To fix this, you will need to visit http://gnuwin32.sourceforge.net/packages/grep.htm and download the binaries and dependencies zip files Extract the contents of the bin folder from the zip files to the bin folder of your Git install and restart Git Bash.

This should also work for git via the commandline (cmd.exe)
 * generate some sample data - instructions are in /tools/dbgen/README.md
 * the mbstring extension is required.

## How to use the API

Go to http://api.joind.in and click around!

There's more documentation here: http://joind.in/api/v2docs
