#!/usr/bin/env bash
set -Eeuo pipefail

BACKUP_DIR="${BACKUP_DIR:-/opt/unibook/backups}"
TIMESTAMP="$(date +'%Y-%m-%d_%H-%M-%S')"
BACKUP_FILE="${BACKUP_DIR}/unibook_${TIMESTAMP}.sql"

mkdir -p "$BACKUP_DIR"

if docker exec unibook-db sh -c 'exec mysqldump --no-tablespaces -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' > "$BACKUP_FILE"; then
    if [[ -s "$BACKUP_FILE" ]]; then
        find "$BACKUP_DIR" -type f -name 'unibook_*.sql' -mtime +7 -delete
        echo "Backup created: $BACKUP_FILE"
    else
        rm -f "$BACKUP_FILE"
        echo "ERROR: Backup file is empty." >&2
        exit 1
    fi
else
    rm -f "$BACKUP_FILE"
    echo "ERROR: Database backup failed." >&2
    exit 1
fi
