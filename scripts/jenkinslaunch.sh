#!/bin/bash

if [ -z $TARGETBASE ]
then
	echo "Please specify TARGETBASE in the environment, eg /var/www/joind.in"
	exit 1
fi
#TARGETBASE=/var/www/joind.in

if [ -z $DBNAME ]
then
	echo "Please specify DBNAME in the environment, eg joindin"
	exit 1
fi
#DBNAME=joindin

TARGET=${TARGETBASE}/${BUILD_NUMBER}
export TARGET


if [ -z $GITHUB_REPO ]
then
	GITHUB_REPO=joindin-api
fi

if [ -z $GITHUB_USER ]
then
	GITHUB_USER=joindin
fi

if [ -z $BRANCH ]
then
	BRANCH=master
fi
LAUNCHREF=remotes/deployremote/$BRANCH

sg web -c "
mkdir -p $TARGET \
 ; git remote set-url deployremote https://github.com/$GITHUB_USER/$GITHUB_REPO.git \
&& git fetch deployremote \
&& git archive $LAUNCHREF | tar xC $TARGET \
&& composer -o --prefer-dist --no-dev --no-progress --working-dir=$TARGET install \
&& (echo $TARGET ; echo $LAUNCHREF) > $TARGET/src/release.txt \
&& ln -s $TARGETBASE/config.php $TARGET/src/config.php \
&& ln -s $TARGETBASE/database.php $TARGET/src/database.php \
&& ln -s $TARGET $TARGETBASE/www.new \
&& ./scripts/patchdb.sh -t '$TARGET' $DBNAME \
&& mv -Tf $TARGETBASE/www.new $TARGETBASE/www
"

