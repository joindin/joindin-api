#!/bin/bash -x

phpcs \
    --standard=tools/codesniffer/JoindInPSR2/ruleset.xml \
    --ignore=**/config.php,**/database.php,vendor \
    --extensions=php \
    -p \
    .

cd tests
phpunit
