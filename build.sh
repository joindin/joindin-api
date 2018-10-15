#!/bin/bash
set -e
phpcs \
    --standard=tools/codesniffer/JoindInPSR2/ruleset.xml \
    --ignore=**/config.php,**/database.php,vendor,tools,tests/bootstrap.php \
    --extensions=php \
    --runtime-set ignore_warnings_on_exit true \
    -p \
    .

cd tests
phpunit
