#!/usr/bin/env bash

dump_table_definitions () {
  if [[ $# -lt 5 ]]; then
    echo "dump_table_definitions missing arguments. Usage: dump_table_definitions <dump_directory> <prefix_index> <schema> <defaults_extra_file> <tables_array_to_dump>"
    return
  fi
#  The directory that table definitions are sent to
  local DUMP_DIR=$1
  shift
#  The prefix to apply to the number of the table in each file name.
# Each table gets the file format 1-$PREFIX_INDEX$INDEX_IN_ARRAY-$SCHEMA-$TABLE_NAME.sql
  local PREFIX_INDEX=$1
  shift
#  The schema of the table definitions to dump
  local SCHEMA=$1
  shift
#  A path to the extras file that contains login and connection info
  local DEFAULTS_EXTRA_FILE=$1
  shift
  local TABLES=("$@")

  for i in "${!TABLES[@]}"; do
    TABLE="${TABLES[i]}"
    FILE=$(printf '1-%d%02d-config-%s-%s.sql' "$PREFIX_INDEX" "$i" "$SCHEMA" "$TABLE")
    FULL_PATH=$(printf '%s/%s' "$DUMP_DIR" "$FILE")
    printf "Dumping %s to file %s\n" "$TABLE" "$FULL_PATH"
    mysqldump --defaults-extra-file="$DEFAULTS_EXTRA_FILE" "$SCHEMA" --tables "$TABLE" \
      -v \
      --no-data \
      --no-set-names \
      --no-tablespaces \
      --skip-comments \
      --skip-set-charset \
      --skip-disable-keys \
      --single-transaction > "$FULL_PATH"
#    add a command to create the schema if it doesn't exist
    sed -i "1s/^/CREATE SCHEMA IF NOT EXISTS $SCHEMA;\nUSE $SCHEMA;\n/" "$FULL_PATH"
  done

}
dump_tables() {
  if [[ $# -lt 5 ]]; then
    echo "dump_table_definitions missing arguments. Usage: dump_table_definitions <dump_directory> <prefix_index> <schema> <defaults_extra_file> <tables_array_to_dump>"
    return
  fi
#  The directory that table definitions are sent to
  local DUMP_DIR=$1
  shift
#  The prefix to apply to the number of the table in each file name.
# Each table gets the file format 1-$PREFIX_INDEX$INDEX_IN_ARRAY-$SCHEMA-$TABLE_NAME.sql
  local PREFIX_INDEX=$1
  shift
#  The schema of the table definitions to dump
  local SCHEMA=$1
  shift
#  A path to the extras file that contains login and connection info
  local DEFAULTS_EXTRA_FILE=$1
  shift
  local TABLES=("$@")

  for i in "${!TABLES[@]}"; do
    TABLE="${TABLES[i]}"
    FILE=$(printf '2-%d%02d-data-%s-%s.sql' "$PREFIX_INDEX" "$i" "$SCHEMA" "$TABLE")
    FULL_PATH=$(printf '%s/%s' "$DUMP_DIR" "$FILE")
    printf "Dumping %s to file %s\n" "$TABLE" "$FULL_PATH"
    mysqldump --defaults-extra-file="$DEFAULTS_EXTRA_FILE" "$SCHEMA" --tables "$TABLE" \
      --no-create-info \
      --no-set-names \
      --no-tablespaces \
      --skip-comments \
      --skip-set-charset \
      --skip-disable-keys \
      --single-transaction > "$FULL_PATH"
#    add a command to create the schema if it doesn't exist
    sed -i "1s/^/USE $SCHEMA;\n/" "$FULL_PATH"
  done
}