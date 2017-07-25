#!/usr/bin/env bash
TEST_DIRECTORY="_build"
RUNNER=`whoami`

cd ~/cdash

if [ -d "./$TEST_DIRECTORY" ]; then
    echo "Test directory, $TEST_DIRECTORY already exists."
    exit 1
fi

echo "Running tests as $RUNNER."
mkdir _build && cd _build
cmake -DCDASH_TESTING_RENAME_LOGS=true -DCDASH_DB_HOST=mysql -DCDASH_DIR_NAME=cdash -DCDASH_DB_LOGIN=root  ..
ctest --extra-verbose
