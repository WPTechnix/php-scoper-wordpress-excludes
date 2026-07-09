#!/usr/bin/env bash
#
# Regenerates symbols/**/*.json (via generate.sh) and, if anything changed,
# commits, tags the next semver patch version, and pushes both the commit
# and the tag. This is the single entry point used both locally by a
# maintainer and by .github/workflows/update.yml - the CI workflow does not
# duplicate any of this logic, it only invokes this script.
#
# Usage: bin/release.sh [--force] [--only=<package>]

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

"$SCRIPT_DIR/generate.sh" "$@"

if [ -z "$(git status --porcelain -- symbols/)" ]; then
    echo "No symbol changes detected. Nothing to release."
    exit 0
fi

echo "==> Changes detected under symbols/:"
git status --porcelain -- symbols/

git add symbols/
git commit -m "chore: update generated exclusion symbols"

LAST_TAG="$(git tag --list 'v[0-9]*.[0-9]*.[0-9]*' \
    | sed 's/^v//' \
    | sort -t. -k1,1n -k2,2n -k3,3n \
    | tail -n1)"

if [ -z "$LAST_TAG" ]; then
    NEXT_TAG="v0.1.0"
else
    IFS='.' read -r MAJOR MINOR PATCH <<< "$LAST_TAG"
    NEXT_TAG="v${MAJOR}.${MINOR}.$((PATCH + 1))"
fi

echo "==> Tagging $NEXT_TAG"
git tag -a "$NEXT_TAG" -m "Release $NEXT_TAG"

git push origin HEAD
git push origin "$NEXT_TAG"

echo "==> Released $NEXT_TAG"
