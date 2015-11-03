#!/usr/bin/env bash

echo "Configure MySQL test database"

mysql -u root -e 'CREATE USER \'board\'@\'localhost\' IDENTIFIED BY \'111\';'
mysql -u root -e 'GRANT ALL PRIVILEGES ON *.* TO \'board\'@\'localhost\';'
mysql -u root -e 'FLUSH PRIVILEGES;'
mysql -u root -e 'CREATE DATABASE board;'
mysql -u root -e 'CREATE TABLE IF NOT EXISTS `tasks` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(255) NOT NULL,
`assignee` varchar(2) NOT NULL,
`status` tinyint(1) NOT NULL,
`type` tinyint(1) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;'