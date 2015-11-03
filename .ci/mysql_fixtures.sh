#!/usr/bin/env bash

echo "Configure MySQL test database"

mysql -u root -e 'CREATE DATABASE board;'