#!/usr/bin/env bash
# Build a SchuWeb Sitemap (or sibling plugin repo) package with Phing 3 in a container.
#
# Usage (from any repo inside ~/Git):
#   ~/Git/SchuWeb-Sitemap/dev/build.sh X.Y.Z            # builds the repo you are in
#   ~/Git/SchuWeb-Sitemap/dev/build.sh X.Y.Z <repo-dir> # builds the given repo
#
# Output: ~/Git/packages/SchuWeb_Sitemap/ (builddir in builder.xml is ../packages/...)
set -euo pipefail

VERSION="${1:?usage: build.sh X.Y.Z [repo-dir]}"
REPO="$(git -C "${2:-.}" rev-parse --show-toplevel)"
PARENT="$(dirname "$REPO")"
NAME="$(basename "$REPO")"

PHING_VERSION=3.1.2
IMAGE="localhost/schuweb-phing:${PHING_VERSION}"
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

[[ -f "$REPO/builder.xml" ]] || { echo "No builder.xml in $REPO" >&2; exit 1; }

if [[ "$VERSION" != "0.0.0" ]] && grep -rqs __BUMP_VERSION__ "$REPO/src"; then
    echo "WARNING: __BUMP_VERSION__ still present in $NAME/src - replace it with $VERSION before a release:" >&2
    grep -rn __BUMP_VERSION__ "$REPO/src" >&2
fi

if ! podman image exists "$IMAGE"; then
    podman build --build-arg PHING_VERSION="$PHING_VERSION" -t "$IMAGE" "$HERE/build"
fi

mkdir -p "$PARENT/packages"
# Mount the parent dir so builder.xml can reach ../packages; keep-id => output owned by you
podman run --rm --userns=keep-id \
    -v "$PARENT:/work" -w "/work/$NAME" \
    "$IMAGE" -f builder.xml -Dbuild.version="$VERSION"
