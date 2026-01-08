docker.up:
	docker-compose -p wpgonecontrol up -d

docker.stop:
	docker-compose -p wpgonecontrol stop

docker.down:
	docker-compose -p wpgonecontrol down

docker.build.php:
	docker-compose -p wpgonecontrol up -d --build php

php.log:
	docker-compose -p wpgonecontrol exec php sh -c "tail -f /var/log/php-error.log"

clear.all:
	bash ./install/clear.sh

connect.php:
	docker-compose -p wpgonecontrol exec php bash

connect.nginx:
	docker-compose -p wpgonecontrol exec nginx sh

connect.php.root:
	docker-compose -p wpgonecontrol exec --user=root php bash
