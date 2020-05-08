#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
# shellcheck source=./mysqldump_functions.sh
source "$DIR"/mysqldump_functions.sh

TOE_SCHEMA=toe
TOE_TABLES=('bus' 'country' 'event' 'feedback' 'member' 'password_request' 'question' 'region' 'route' 'route_archive' 'team' 'user' 'user_role' 'zone')
TOE_INDEX=1
# create this file to connect to the docker instance
TOE_EXTRAS_FILE=~/.mysql/defaults_extra_files/toe_docker
# shellcheck source=./mysql/data
DATA_DIR="$DIR"/mysql/data

dump_table_definitions $DATA_DIR $TOE_INDEX $TOE_SCHEMA $TOE_EXTRAS_FILE "${TOE_TABLES[@]}"
dump_tables $DATA_DIR $TOE_INDEX $TOE_SCHEMA $TOE_EXTRAS_FILE "${TOE_TABLES[@]}"

