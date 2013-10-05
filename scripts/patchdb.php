<?php
/**
 *
 * Sets up the joindin database and runs the
 * patches for poor people running Windows who
 * can't persuade patchdb.sh to work without
 * monkeying around getting grep to work.
 *
 *
 * Usage:
 * php -f patchdb.php -t c:\wamp\path\to\joindin-api\  -d joindindatabasename -u mysqluser -p mysqlpassword -i
 *
 */

$options = getopt("t:d:u:p:i::");



// No options? Let's help out...
if (!$options || count($options) == 0) {
    
    echo <<<HELPTEXT



Joind.in database initialiser help script 

Command line options:

    -t   Required.  Path to the joind.in directory

    -d   Database name to user.  Defaults to `joindin`.

    -u   Required.  Database user to use to run the updates

    -p   Password for the database user.

    -i   Whether to run the init_db.sql to set things up

For example:

    php -f patchdb.php -t c:\wamp\pathto\joindin-api\ -d joindindatabasename -u mysqluser -p mysqlpassword -i



HELPTEXT;

    exit;
}



// Some sanity checks:
if (!array_key_exists('u', $options) || $options['u'] == "") {
    echo "Please provide a mysql user name";
    exit;
}
if (!array_key_exists('p', $options)) {
    $options['p'] = "";
}
if (!array_key_exists('t', $options) || $options['t'] == "") {
    echo "Please provide the directory that contains joind.in db updates";
    exit;
}
if (!array_key_exists('d', $options)) {
    $options['d'] = 'joindin';
}

if (!is_dir($options['t'])) {
    echo "The patch directory doesn't appear to exist";
    exit;
}


$options['t'] .= DIRECTORY_SEPARATOR . 'db';


///////////////////////////////////////////////
// Test connecting to mysql
///////////////////////////////////////////////
$hasMysql = @exec('mysql --version');
if (!$hasMysql) {
    echo "Could not find mysql executable, sorry.";
    exit;
}


$baseMysqlCmd = "mysql -u{$options['u']} " 
    . ($options['p'] ? "-p{$options['p']} " : "")
    . "{$options['d']}";


///////////////////////////////////////////////
// Try getting the max patch_level so far

exec($baseMysqlCmd . ' -r '
    . '-e "select max(patch_number) as num from `' . $options['d'] . '`.patch_history',
    $res
);

$maxPatchNum = 0;
if ($res && array_key_exists(1, $res) && $res[1] > 0) {
    $maxPatchNum = $res[1];
} else if (is_array($res) && count($res) === 0) {
    $maxPatchNum = false;
}

if ($maxPatchNum === false && !array_key_exists('i', $options)) {
    echo "It doesn't look like you've initialised your db.  Exiting now.";
    exit;
}


//////////////////////////////////////////////
// Initialise db
//////////////////////////////////////////////
if (array_key_exists('i', $options)) {

    if (!file_exists($options['t'] . DIRECTORY_SEPARATOR . 'init_db.sql')) {
        echo "Couldn't find the init_db.sql file to initialise db";
        exit;
    }
    

    echo "Initialising DB";
    exec($baseMysqlCmd . " < " . $options['t'] . DIRECTORY_SEPARATOR . 'init_db.sql');
    echo " ... done\n";
}


/////////////////////////////////////////////
// Do some patching
/////////////////////////////////////////////

// First, look through the directory for patch123.sql files
// and get all the {123} numbers, so we can run them all 
// in order.
$matchedNums = array();
if ($dh = opendir($options['t'])) {
    while (($file = readdir($dh)) !== false) {
        
        preg_match("/patch([\d]+)\.sql/", $file, $matches);
        if ($matches && array_key_exists(1, $matches) && $matches[1] > $maxPatchNum) {
            $matchedNums[] = (int)$matches[1];
        }

    }
    closedir($dh);
}


// Now we've got them, run the patches
sort($matchedNums);

echo "Applying patches... ";

foreach ($matchedNums as $patchNum) {
    echo $patchNum . ", ";
    exec($baseMysqlCmd . " < " . $options['t'] . DIRECTORY_SEPARATOR . 'patch' . $patchNum . '.sql');
}

echo "\nAll done\n";







