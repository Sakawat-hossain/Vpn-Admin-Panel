#!/usr/bin/bash

#Stop script execution if any command fails
set -e

echo "===========Installing admin panel==============="
echo "Please wait"
cd ~ || exit
sudo apt -qq update
sudo apt -qq install -y sshpass supervisor

echo -e "\nConfiguring worker"
sudo cp laravel-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
#sudo supervisorctl start laravel-worker:*

set +e