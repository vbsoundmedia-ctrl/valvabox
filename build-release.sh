#!/usr/bin/env bash
# Builds the cPanel-ready installation ZIP: dist/valvabox-<version>.zip
# Usage: ./build-release.sh [version]
set -euo pipefail
VERSION="${1:-1.0.0}"
ROOT="$(cd "$(dirname "$0")" && pwd)"
BUILD="$(mktemp -d)"
OUT="$ROOT/dist/valvabox-$VERSION.zip"

echo "→ Copying app"
rsync -a "$ROOT/platform/" "$BUILD/valvabox/" \
  --exclude vendor --exclude node_modules --exclude tests --exclude phpunit.xml --exclude '.phpunit*' \
  --exclude .env --exclude .env.backup --exclude '.git*' --exclude .editorconfig --exclude .gitattributes \
  --exclude 'storage/logs/*.log' --exclude 'storage/framework/cache/data/*' --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*.php' --exclude 'storage/app/private/*' --exclude 'storage/app/tmp' \
  --exclude 'storage/app/installed.lock' --exclude 'bootstrap/cache/*.php' --exclude 'database/*.sqlite'
# keep the empty storage folders Laravel needs
for d in storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache; do
  mkdir -p "$BUILD/valvabox/$d"; touch "$BUILD/valvabox/$d/.gitkeep"
done

echo "→ Installing PHP dependencies (no dev)"
(cd "$BUILD/valvabox" && composer install --prefer-dist --no-dev --optimize-autoloader --no-interaction --no-progress --quiet --no-scripts \
  && composer dump-autoload --optimize --no-dev --quiet && php artisan package:discover --ansi >/dev/null)

find "$BUILD/valvabox/vendor" -type d -name .git -prune -exec rm -rf {} +
cp "$ROOT/INSTALL.md" "$BUILD/valvabox/INSTALL.md"
chmod -R u+rwX,go+rX "$BUILD/valvabox"
chmod -R 775 "$BUILD/valvabox/storage" "$BUILD/valvabox/bootstrap/cache"

echo "→ Zipping"
mkdir -p "$ROOT/dist"; rm -f "$OUT"
(cd "$BUILD/valvabox" && zip -qr -X "$OUT" . -x '*.DS_Store')
rm -rf "$BUILD"
echo "✓ $OUT ($(du -h "$OUT" | cut -f1))"
