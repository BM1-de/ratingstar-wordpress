#!/usr/bin/env bash
#
# Build an installable ratingstar.zip without dev files.
#
# The archive contains a single top-level "ratingstar/" folder, ready to be
# uploaded via Plugins → Add New → Upload Plugin. Dev files (.git, CI config,
# this script, README.md) are excluded; the WordPress.org readme is readme.txt.
#
set -euo pipefail

SLUG="ratingstar"

# Move to the plugin root (this script lives in bin/).
cd "$(dirname "$0")/.."
ROOT="$(pwd)"
OUT="${ROOT}/${SLUG}.zip"

BUILD_DIR="$(mktemp -d)"
DEST="${BUILD_DIR}/${SLUG}"
mkdir -p "${DEST}"

rsync -a "${ROOT}/" "${DEST}/" \
	--exclude='.git' \
	--exclude='.github' \
	--exclude='.gitignore' \
	--exclude='.distignore' \
	--exclude='bin' \
	--exclude='node_modules' \
	--exclude='vendor' \
	--exclude='README.md' \
	--exclude='*.zip'

rm -f "${OUT}"
( cd "${BUILD_DIR}" && zip -rq "${OUT}" "${SLUG}" )
rm -rf "${BUILD_DIR}"

echo "Built: ${OUT}"
unzip -l "${OUT}"
