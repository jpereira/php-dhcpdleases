#!/bin/sh
# Usage: scripts/bump.sh [major|minor|patch|X.Y.Z]
# Updates VERSION and moves "Unreleased" in CHANGELOG.md to the new version.
# Does not commit, tag or push.
set -eu

cd "$(dirname "$0")/.."

cur=$(cat VERSION)
case "${1:-patch}" in
    major) new=$(echo "$cur" | awk -F. '{printf "%d.0.0", $1+1}') ;;
    minor) new=$(echo "$cur" | awk -F. '{printf "%d.%d.0", $1, $2+1}') ;;
    patch) new=$(echo "$cur" | awk -F. '{printf "%d.%d.%d", $1, $2, $3+1}') ;;
    *.*.*) new=$1 ;;
    *) echo "usage: $0 [major|minor|patch|X.Y.Z]" >&2; exit 1 ;;
esac

echo "$new" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+$' || { echo "bad version: $new" >&2; exit 1; }

echo "$new" > VERSION
today=$(date +%Y-%m-%d)
tmp=$(mktemp)
awk -v v="$new" -v d="$today" '
    /^## \[Unreleased\]/ { print; print ""; print "## [" v "] - " d; next }
    { print }
' CHANGELOG.md > "$tmp" && mv "$tmp" CHANGELOG.md

echo "$cur -> $new"
echo "Next: review CHANGELOG.md, then: git commit -am \"Release v$new\" && git tag v$new"
