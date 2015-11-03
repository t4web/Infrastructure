#!/usr/bin/env bash

echo "Configure MySQL test database"

mysql -u root -e 'CREATE USER \'board\'@\'localhost\' IDENTIFIED BY \'111\';'
mysql -u root -e 'GRANT ALL PRIVILEGES ON *.* TO \'board\'@\'localhost\';'
mysql -u root -e 'FLUSH PRIVILEGES;'
mysql -u root -e 'CREATE DATABASE board;'
sh -c "mysql -e 'CREATE TABLE board.tasks (
id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
name varchar(255) NOT NULL,
assignee varchar(2) NOT NULL,
status tinyint(1) NOT NULL,
type tinyint(1) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;'"
mysql -u root -e 'INSERT INTO board.tasks (id, name, assignee, status, type) VALUES (2, \'qq\', \'qq\', 1, 1), (3, \'194556\', \'45\', 0, 0), (4, \'Some name\', \'AA\', 0, 0);'
