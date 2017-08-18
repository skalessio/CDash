#!/bin/bash

cd ~/cdash
sudo /etc/init.d/apache2 restart
sudo ln -s /home/kitware/cdash/public /var/www/html/cdash
chmod a+rwx backup log public/rss public/upload
composer self-update --no-interaction
composer install --no-interaction --no-progress --prefer-dist
npm install
cp tests/circle/protractor.config.json node_modules/protractor/config.json
node_modules/.bin/webdriver-manager update
node_modules/.bin/webdriver-manager start
