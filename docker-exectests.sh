#!/usr/bin/env bash
TEST_DIRECTORY="_build"
RUNNER=`whoami`

cd ~/cdash

# ensures that out tests will not fail due to
# permission problems on this directory
sudo chmod 0777 public/rss/*.xml

if [ -d "./$TEST_DIRECTORY" ]; then
    echo "Test directory, $TEST_DIRECTORY already exists."
    exit 1
fi

echo "Running tests as $RUNNER."
mkdir _build && cd _build

cmake \
    -DCDASH_TESTING_RENAME_LOGS=true \
    -DCDASH_DB_HOST=mysql \
    -DCDASH_DIR_NAME=cdash \
    -DCDASH_DB_LOGIN=root  \
    -DCDASH_CONFIGURE_HTACCESS_FILE=ON \
    ..

chmod 0777 xdebugCoverage
ctest --extra-verbose
