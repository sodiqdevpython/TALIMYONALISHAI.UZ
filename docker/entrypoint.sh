#!/bin/bash
set -e

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-edudirectionai_db}"
DB_USER="${DB_USER:-edudirectionai}"
DB_PASS="${DB_PASS:-changeme_app}"
DB_ROOT_PASS="${DB_ROOT_PASS:-changeme_root}"

echo "[entrypoint] Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
for i in $(seq 1 60); do
    if mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" >/dev/null 2>&1; then
        echo "[entrypoint] MySQL reachable."
        break
    fi
    sleep 2
done

# Base SQL avtomatik importlanadi MySQL init tomonidan (docker-entrypoint-initdb.d).
# Bu yerda faqat migration'larni yuramiz — root creds bilan.
NEED_MIGR=$(mysql -h "$DB_HOST" -P "$DB_PORT" -uroot -p"$DB_ROOT_PASS" -N -B \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_name='roles';" 2>/dev/null || echo 0)

if [ "${NEED_MIGR:-0}" != "0" ]; then
    for mig in /var/www/html/database/migrations/*.sql; do
        [ -f "$mig" ] || continue
        marker="/var/www/html/outputs/.migr_$(basename "$mig").done"
        if [ ! -f "$marker" ]; then
            echo "[entrypoint] Applying migration: $(basename "$mig")"
            if mysql -h "$DB_HOST" -P "$DB_PORT" -uroot -p"$DB_ROOT_PASS" "$DB_NAME" < "$mig"; then
                touch "$marker"
            else
                echo "[entrypoint] Migration $(basename "$mig") returned error (continuing)."
            fi
        fi
    done
else
    echo "[entrypoint] Base schema not present yet — skipping migrations."
fi

chown -R www-data:www-data /var/www/html/data /var/www/html/outputs 2>/dev/null || true

exec "$@"
