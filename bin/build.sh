#!/usr/bin/env bash
#
# Build an installable ratingstar-de-seal.zip without dev files.
#
# The archive contains a single top-level "ratingstar-de-seal/" folder, ready to be
# uploaded via Plugins → Add New → Upload Plugin. Dev files (.git, CI config,
# this script, README.md) are excluded; the WordPress.org readme is readme.txt.
#
# The ZIP is written to the parent directory of the plugin checkout so the
# build artifact never sits inside the working tree (keeps mirrors/checks
# clean).
#
set -euo pipefail

SLUG="ratingstar-de-seal"

# Move to the plugin root (this script lives in bin/).
cd "$(dirname "$0")/.."
ROOT="$(pwd)"
OUT="$(dirname "${ROOT}")/${SLUG}.zip"

BUILD_DIR="$(mktemp -d)"
DEST="${BUILD_DIR}/${SLUG}"
mkdir -p "${DEST}"

rsync -a "${ROOT}/" "${DEST}/" \
	--exclude='.git' \
	--exclude='.github' \
	--exclude='.gitignore' \
	--exclude='.distignore' \
	--exclude='.wordpress-org' \
	--exclude='bin' \
	--exclude='languages' \
	--exclude='node_modules' \
	--exclude='vendor' \
	--exclude='README.md' \
	--exclude='*.zip'

rm -f "${OUT}"
( cd "${BUILD_DIR}" && zip -rq "${OUT}" "${SLUG}" )
rm -rf "${BUILD_DIR}"

echo "Built: ${OUT}"
unzip -l "${OUT}"
