#!/usr/bin/env bash
#
# readme.txt と wordpress-org/ を WordPress.org の SVN（trunk・assets・Stable tag の readme）へ反映する。
#
# 使い方（プラグインのルートで）:
#   ./scripts/svn-wordpress-org.sh
#
# 環境変数（任意）:
#   WPORG_SVN_DIR  … チェックアウト先（既定: リポジトリのひとつ上の navitto.svn-wporg）
#   WPORG_SLUG     … 既定 navitto
#   SVN_USERNAME / SVN_PASSWORD … 非対話コミット用（WordPress.org の SVN 用パスワード）
#
# 認証: プロフィール https://profiles.wordpress.org/nsouta/profile/edit/group/3/?screen=svn-password

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="${WPORG_SLUG:-navitto}"
SVN_URL="https://plugins.svn.wordpress.org/${SLUG}/"
SVN_DIR="${WPORG_SVN_DIR:-${ROOT}/../${SLUG}.svn-wporg}"
README_NAME="readme.txt"
ASSETS_DIR="wordpress-org"

command -v svn >/dev/null 2>&1 || {
	echo "エラー: svn をインストールしてください。" >&2
	exit 1
}
command -v rsync >/dev/null 2>&1 || {
	echo "エラー: rsync が必要です。" >&2
	exit 1
}

[[ -f "${ROOT}/${README_NAME}" ]] || {
	echo "エラー: ${ROOT}/${README_NAME} がありません。" >&2
	exit 1
}
[[ -d "${ROOT}/${ASSETS_DIR}" ]] || {
	echo "エラー: ${ROOT}/${ASSETS_DIR}/ がありません。" >&2
	exit 1
}

if [[ ! -d "${SVN_DIR}/.svn" ]]; then
	echo "SVN を取得します: ${SVN_URL}"
	echo "  -> ${SVN_DIR}"
	mkdir -p "$(dirname "${SVN_DIR}")"
	svn checkout --depth immediates "${SVN_URL}" "${SVN_DIR}"
	cd "${SVN_DIR}"
	svn update --set-depth infinity assets
	svn update --set-depth infinity trunk
else
	cd "${SVN_DIR}"
	svn update
fi

echo "readme.txt -> trunk/"
cp "${ROOT}/${README_NAME}" "trunk/${README_NAME}"

echo "${ASSETS_DIR}/ -> assets/"
rsync -rc "${ROOT}/${ASSETS_DIR}/" assets/ --delete --delete-excluded

if compgen -G "assets/*.png" >/dev/null; then
	svn propset svn:mime-type "image/png" assets/*.png 2>/dev/null || true
fi
if compgen -G "assets/*.jpg" >/dev/null; then
	svn propset svn:mime-type "image/jpeg" assets/*.jpg 2>/dev/null || true
fi
if compgen -G "assets/*.svg" >/dev/null; then
	svn propset svn:mime-type "image/svg+xml" assets/*.svg 2>/dev/null || true
fi

STABLE_TAG="$(grep -m 1 -E '^Stable tag:' "trunk/${README_NAME}" | tr -d '\r' | awk '{print $NF}')"
if [[ -n "${STABLE_TAG}" ]] && svn info "^/${SLUG}/tags/${STABLE_TAG}" >/dev/null 2>&1; then
	echo "readme -> tags/${STABLE_TAG}/"
	svn update --set-depth infinity "tags/${STABLE_TAG}"
	rsync -c "trunk/${README_NAME}" "tags/${STABLE_TAG}/"
else
	echo "注意: tags/${STABLE_TAG:-?}/ が無いか Stable tag が読めないため、tags はスキップしました。"
fi

if svn stat trunk | grep -qvi " trunk/${README_NAME}\$"; then
	echo "エラー: trunk に readme 以外の変更があります。中止します。" >&2
	exit 1
fi

svn add . --force >/dev/null 2>&1 || true
deleted_lines="$(svn status | grep '^\!' || true)"
if [[ -n "${deleted_lines}" ]]; then
	echo "${deleted_lines}" | sed 's/! *//' | xargs -I% svn rm --force %@
fi
svn update

echo "--- svn status ---"
svn status

if [[ -z $(svn stat) ]]; then
	echo "差分なし。コミット不要です。"
	exit 0
fi

MSG="Update readme.txt and plugin directory assets."

if [[ -n "${SVN_USERNAME:-}" ]] && [[ -n "${SVN_PASSWORD:-}" ]]; then
	svn commit -m "${MSG}" --no-auth-cache --non-interactive --username "${SVN_USERNAME}" --password "${SVN_PASSWORD}"
else
	echo "コミットします（ユーザー名・パスワードの入力が求められることがあります）。"
	svn commit -m "${MSG}"
fi

echo "完了。"
