#!/bin/sh
# Usage: scripts/release-notes.sh X.Y.Z
# Prints the CHANGELOG.md section of that version (used by the release workflow).
set -eu

cd "$(dirname "$0")/.."

awk -v v="$1" '
    /^## \[/ { if (found) exit; if (index($0, "[" v "]") == 4) { found = 1; next } }
    found { print }
' CHANGELOG.md | sed -e :a -e '/^\n*$/{$d;N;ba' -e '}'
