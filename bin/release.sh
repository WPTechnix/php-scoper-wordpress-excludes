#!/usr/bin/env bash
#
# Regenerates symbols/**/*.json (via generate.sh), commits any changes,
# then creates a semver release tag *only* when the actual symbol data
# (classes, functions, constants) differs from the last tag.  If only
# versions.json metadata changed (e.g. an upstream version bump with no
# new symbols), the commit is pushed without a tag.
#
# This is the single entry point used both locally by a maintainer and
# by .github/workflows/update.yml – the CI workflow does not duplicate
# any of this logic, it only invokes this script.
#
# Usage: bin/release.sh [--force] [--only=<package>]

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

"$SCRIPT_DIR/generate.sh" "$@"

git add -A

if git diff --cached --numstat | grep -qv '^0\t0\t'; then
    echo "==> Changes detected:"
    git diff --cached --stat
else
    if [ -z "$(git diff --cached --name-only)" ]; then
        echo "No changes detected. Nothing to commit."
    else
        echo "Only mode changes detected. Nothing to commit."
    fi
    exit 0
fi

git commit -m "chore: update generated exclusion symbols"

LAST_TAG="$(git tag --list 'v[0-9]*.[0-9]*.[0-9]*' \
    | sed 's/^v//' \
    | sort -t. -k1,1n -k2,2n -k3,3n \
    | tail -n1)"

SHOULD_TAG=true

if [ -n "$LAST_TAG" ]; then
    TAG="v$LAST_TAG"
    # Compare actual symbol data (not versions.json) against the last tag.
    if git diff --quiet "$TAG" HEAD -- symbols/ ':!symbols/versions.json'; then
        echo "Symbol data unchanged since $TAG. Skipping release tag."
        SHOULD_TAG=false
    fi
fi

if [ "$SHOULD_TAG" = true ]; then
    if [ -z "$LAST_TAG" ]; then
        NEXT_TAG="v0.1.0"
    else
        IFS='.' read -r MAJOR MINOR PATCH <<< "$LAST_TAG"
        NEXT_TAG="v${MAJOR}.${MINOR}.$((PATCH + 1))"
    fi

    echo "==> Tagging $NEXT_TAG"
    git tag -a "$NEXT_TAG" -m "Release $NEXT_TAG"
fi

git push origin HEAD

if [ "$SHOULD_TAG" = true ]; then
    git push origin "$NEXT_TAG"
    echo "==> Released $NEXT_TAG"
fi
