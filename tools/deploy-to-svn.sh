#!/bin/bash

if [ $# -eq 0 ]; then
	echo 'Usage: `./deploy-to-svn.sh <tag | HEAD>`'
	exit 1
fi

SVN_SLUG="codemirror-wp"

GIT_DIR=$(dirname "$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )" )
SVN_DIR="/tmp/$SVN_SLUG"
TARGET=$1

cd $GIT_DIR

# Make sure we don't have uncommitted changes.
if [[ -n $( git status -s --porcelain ) ]]; then
	echo "Uncommitted changes found."
	echo "Please deal with them and try again clean."
	exit 1
fi

if [ "$TARGET" != "HEAD" ]; then
	# Make sure we're trying to deploy something that's been tagged. Don't deploy non-tagged.
	if [ -z $( git tag | grep "^$TARGET$" ) ]; then
		echo "Tag $TARGET not found in git repository."
		echo "Please try again with a valid tag."
		exit 1
	fi
else
	read -p "You are about to deploy a change from an unstable state 'HEAD'. This should only be done to update string typos for translators. Are you sure? [y/N]" -n 1 -r
	if [[ $REPLY != "y" && $REPLY != "Y" ]]
	then
		exit 1
	fi
fi

git checkout $TARGET

# Prep a home to drop our new files in. Just make it in /tmp so we can start fresh each time.
rm -rf $SVN_DIR

echo "Checking out SVN shallowly to $SVN_DIR"
svn -q checkout "http://plugins.svn.wordpress.org/$SVN_SLUG/" --depth=empty $SVN_DIR
echo "Done!"

cd $SVN_DIR

echo "Checking out SVN trunk to $SVN_DIR/trunk"
svn -q up trunk
echo "Done!"

echo "Checking out SVN tags shallowly to $SVN_DIR/tags"
svn -q up tags --depth=empty
echo "Done!"

echo "Deleting everything in trunk except for .svn directories"
for file in $(find $SVN_DIR/trunk/* -not -path "*.svn*"); do
	rm $file 2>/dev/null
done
echo "Done!"

echo "Rsync'ing everything over from Git except for .git stuffs"
rsync -r --exclude='*.git*' $GIT_DIR/* $SVN_DIR/trunk
echo "Done!"

echo "Purging paths included in .svnignore"
# check .svnignore
for file in $( cat "$GIT_DIR/.svnignore" 2>/dev/null ); do
	rm -rf $SVN_DIR/trunk/$file
done
echo "Done!"

if [ "$TARGET" != "HEAD" ]; then
	# Tag the release.
	# svn cp trunk "tags/$TARGET"

	# Change stable tag in the tag itself, and commit (tags shouldn't be modified after comitted)
	perl -pi -e "s/Stable tag: .*/Stable tag: $TARGET/" tags/$TARGET/readme.txt
	perl -pi -e "s/Stable tag: .*/Stable tag: $TARGET/" trunk/readme.txt
fi

echo "Now you just need to 'cd $SVN_DIR && svn ci'"