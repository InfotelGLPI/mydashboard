#!/usr/bin/env bash
# -------------------------------------------------------------------------
# Regenerate GPL headers for all PHP files of the accounts plugin.
#
# Usage (from any directory):
#   bash tools/regenerate_headers.sh [--dry-run]
#
# --dry-run  Show what would change without writing anything.
# -------------------------------------------------------------------------
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
HEADER_PHP="$SCRIPT_DIR/HEADER"
HEADER_TWIG="$SCRIPT_DIR/HEADER.twig"

for f in "$HEADER_PHP" "$HEADER_TWIG"; do
    if [[ ! -f "$f" ]]; then
        echo "Error: header file not found: $f"
        exit 1
    fi
done

php "$SCRIPT_DIR/regenerate_headers.php" "$PLUGIN_DIR" "$HEADER_PHP" "$HEADER_TWIG" "$@"
