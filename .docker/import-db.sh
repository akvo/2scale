#! /usr/bin/env sh
set -eu

# docker cp .docker/sql/tcakvo_2scale.sql 2scale_db_1:/2scale.sql

cat .docker/sql/tcakvo_2scale_test_dump07072025.sql | docker exec -i 2scale-db-1 mysql -u akvo --password=secret akvo;
