#!/usr/bin/env bash
#
# Pulls the latest upstream stub/source packages and regenerates
# symbols/**/*.json. Safe to run repeatedly - packages whose resolved
# upstream version(s) haven't changed are skipped (see symbols/versions.json).
#
# Usage:
#   bin/generate.sh                    # regenerate anything that changed upstream
#   bin/generate.sh --force            # regenerate everything, ignoring the version cache
#   bin/generate.sh --only=wordpress   # limit to a single package
#
# Requires PHP >= 8.1 and Composer on PATH. Does not touch git.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
BUILD_DIR="$REPO_ROOT/build"

FORCE=0
ONLY=""

for arg in "$@"; do
    case "$arg" in
        --force)
            FORCE=1
            ;;
        --only=*)
            ONLY="${arg#--only=}"
            ;;
        *)
            echo "Unknown option: $arg" >&2
            exit 1
            ;;
    esac
done

echo "==> Resolving build tooling and upstream packages to their latest allowed versions"
# A single `composer update` (rather than `install` followed by `update`) is
# deliberate: build/composer.lock is never committed, so on a fresh checkout
# `install` would already do a full resolve with no lock to speed it up -
# running `update` straight afterwards would just repeat that same work.
composer --working-dir="$BUILD_DIR" update --no-interaction --prefer-dist --no-progress

GEN_ARGS=("--repo-root=$REPO_ROOT")
[ "$FORCE" = "1" ] && GEN_ARGS+=("--force")
[ -n "$ONLY" ] && GEN_ARGS+=("--only=$ONLY")

echo "==> Running generator"
php "$BUILD_DIR/generate.php" "${GEN_ARGS[@]}"

echo "==> Done."
