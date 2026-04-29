#!/usr/bin/env bash
# Copy readme.txt + wordpress-org/* to plugins.svn.wordpress.org (trunk readme, assets/, stable tag readme).
# Prereqs: svn, rsync
# Auth: export SVN_USERNAME / SVN_PASSWORD (WordPress.org username + application password from profile),
#       or rely on cached svn credentials from an interactive "svn commit" once.
#
# Usage (from repo root): ./scripts/deploy-wporg-readme-assets.sh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="${WPORG_SLUG:-navitto}"
SVN_URL="https://plugins.svn.wordpress.org/${SLUG}/"
SVN_DIR="${WPORG_SVN_DIR:-${ROOT}/../${SLUG}.svn-wporg}"
README_NAME="${WPORG_README_NAME:-readme.txt}"
ASSETS_DIR="${WPORG_ASSETS_DIR:-wordpress-org}"

if ! command -v svn >/dev/null 2>&1; then
	echo "Error: svn is not installed." >&2
	exit 1
fi
if ! command -v rsync >/dev/null 2>&1; then
	echo "Error: rsync is not installed." >&2
	exit 1
fi

if [[ ! -f "${ROOT}/${README_NAME}" ]]; then
	echo "Error: missing ${ROOT}/${README_NAME}" >&2
	exit 1
fi
if [[ ! -d "${ROOT}/${ASSETS_DIR}" ]]; then
	echo "Error: missing ${ROOT}/${ASSETS_DIR}" >&2
	exit 1
fi

if [[ ! -d "${SVN_DIR}/.svn" ]]; then
	echo "Checking out ${SVN_URL} -> ${SVN_DIR}"
	mkdir -p "$(dirname "${SVN_DIR}")"
	svn checkout --depth immediates "${SVN_URL}" "${SVN_DIR}"
	cd "${SVN_DIR}"
	svn update --set-depth infinity assets
	svn update --set-depth infinity trunk
else
	cd "${SVN_DIR}"
	svn update
fi

echo "Copying ${README_NAME} -> trunk/"
cp "${ROOT}/${README_NAME}" "trunk/${README_NAME}"

echo "Syncing ${ASSETS_DIR}/ -> assets/"
rsync -rc "${ROOT}/${ASSETS_DIR}/" assets/ --delete --delete-excluded

# https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/
if compgen -G "assets/*.png" >/dev/null; then
	svn propset svn:mime-type "image/png" assets/*.png || true
fi
if compgen -G "assets/*.jpg" >/dev/null; then
	svn propset svn:mime-type "image/jpeg" assets/*.jpg || true
fi
if compgen -G "assets/*.svg" >/dev/null; then
	svn propset svn:mime-type "image/svg+xml" assets/*.svg || true
fi

# Stable tag: copy readme only if tag exists (matches 10up action behavior)
STABLE_TAG="$(grep -m 1 -E '^Stable tag:' "trunk/${README_NAME}" | tr -d '\r' | awk '{print $NF}')"
if [[ -n "${STABLE_TAG}" ]] && svn info "^/${SLUG}/tags/${STABLE_TAG}" >/dev/null 2>&1; then
	echo "Updating readme in tags/${STABLE_TAG}/"
	svn update --set-depth infinity "tags/${STABLE_TAG}"
	rsync -c "trunk/${README_NAME}" "tags/${STABLE_TAG}/"
else
	echo "Stable tag ${STABLE_TAG:-'(none)'}: skip tags/ (not in SVN or empty)"
fi

svn add . --force >/dev/null 2>&1 || true
deleted_lines="$(svn status | grep '^\!' || true)"
if [[ -n "${deleted_lines}" ]]; then
	echo "${deleted_lines}" | sed 's/! *//' | xargs -I% svn rm --force %@
fi

svn update

echo "SVN status:"
svn status

if [[ -z $(svn stat) ]]; then
	echo "Nothing to commit."
	exit 0
fi

if svn stat trunk | grep -qvi " trunk/${README_NAME}\$"; then
	echo "Error: trunk has changes other than ${README_NAME}; aborting." >&2
	exit 1
fi

COMMIT_MSG="${WPORG_COMMIT_MSG:-Update readme and plugin assets}"

if [[ -n "${SVN_USERNAME:-}" ]] && [[ -n "${SVN_PASSWORD:-}" ]]; then
	svn commit -m "${COMMIT_MSG}" --no-auth-cache --non-interactive --username "${SVN_USERNAME}" --password "${SVN_PASSWORD}"
else
	echo "SVN_USERNAME/SVN_PASSWORD not set; running interactive commit..."
	svn commit -m "${COMMIT_MSG}"
fi

echo "Done."
