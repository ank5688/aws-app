#!/bin/bash
# Usage: bash ~/aws-app/deploy.sh server1   or   bash ~/aws-app/deploy.sh server2
set -e
TIER=$1
if [ "$TIER" != "server1" ] && [ "$TIER" != "server2" ]; then
  echo "Usage: bash deploy.sh server1|server2"; exit 1
fi
cd ~/aws-app
git pull origin main
sudo rsync -a \
  --exclude 'setup.sh' --exclude '*.template' \
  --exclude 'test.php' --exclude '*.conf' \
  ~/aws-app/$TIER/ /var/www/html/
sudo chown -R www-data:www-data /var/www/html
if [ "$TIER" = "server2" ]; then
  sudo chmod 640 /var/www/html/includes/db.php /var/www/html/includes/fred.php
fi
echo "Deployed: $(git log -1 --oneline)"
