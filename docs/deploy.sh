#!/bin/bash -ex
git checkout $TRAVIS_BRANCH

openssl aes-256-cbc -K $encrypted_9472126ed793_key -iv $encrypted_9472126ed793_iv -in docs/ghp-id_rsa.enc -out ghp-id_rsa -d
chmod 0400 ghp-id_rsa
IDENTITY_FILE="`pwd`/ghp-id_rsa"
export GIT_SSH_COMMAND="ssh -i $IDENTITY_FILE"

sudo pip install -r docs/requirements.txt
composer config minimum-stability dev
composer require --prefer-stable drutiny/acquia drutiny/sumologic drutiny/http drutiny/plugin-drupal-7 drutiny/plugin-drupal-8 drutiny/cloudflare

./bin/build_docs
mkdocs build --clean

if [ -d ghp ]; then
  rm -rf ghp;
fi

git clone git@github.com:drutiny/drutiny.github.io.git ghp
if [ -d "ghp/$TRAVIS_BRANCH" ]; then
	rm -rf "ghp/$TRAVIS_BRANCH"
fi
mkdir -p ghp/$TRAVIS_BRANCH
rsync -av docs_html/ ghp/$TRAVIS_BRANCH/

# Hack to support backwards compatibility.
if [ "$TRAVIS_BRANCH" == "2.2.x" ]; then
  if [ ! -d ghp/2.2.x/api ] && [ ! -L ghp/2.2.x/api ] && [ -d ghp/2.2.x ]; then
    pushd ghp/2.2.x
    ln -s ../2.2.2/api
    popd
  fi
fi

cd ghp
git config user.name "Travis CI"
git config user.email "drutiny@travis.ci"

UPDATES=`git status --porcelain $TRAVIS_BRANCH/`

if [ "$UPDATES" != "" ]; then
	git add $TRAVIS_BRANCH/
	git commit -m "Deploy from Travis CI"
	git push
fi
