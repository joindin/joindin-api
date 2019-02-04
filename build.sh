#!/bin/bash
set -e

vendor/bin/parallel-lint --exclude vendor .
git diff origin/master... > diff.txt

vendor/bin/phpcs \
    --standard=tools/codesniffer/JoindInPSR2/ruleset.xml \
    --ignore=**/config.php,**/database.php,vendor,tools,tests/bootstrap.php \
    --extensions=php \
    --runtime-set ignore_warnings_on_exit true \
    -p \
    .

vendor/bin/phpunit
vendor/bin/diffFilter --phpunit diff.txt build/logs/clover.xml 80
