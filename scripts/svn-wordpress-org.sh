#!/usr/bin/env bash
#
# WordPress.org プラグイン SVN（plugins.svn.wordpress.org）へ反映するヘルパー。
#
# 前提: subversion（svn）と rsync が入っていること。
# 認証: 初回は対話式コミットで保存するか、環境変数 SVN_USERNAME / SVN_PASSWORD
#       （WordPress.org の「アプリケーションパスワード」）を指定。
#
# 使い方（リポジトリのルートで）:
#   ./scripts/svn-wordpress-org.sh readme-assets   … readme.txt と wordpress-org/ を trunk・assets・安定版タグへ
#   ./scripts/svn-wordpress-org.sh trunk           … プラグイン本体を trunk に同期（リリース前の更新用）
#   ./scripts/svn-wordpress-org.sh help
#
# 新バージョンのリリース例（コミット後、バージョンを上げたうえで）:
#   svn copy trunk tags/1.0.2 -m "Tag 1.0.2"
#   （Stable tag を readme と一致させること）

set -euo pipefail

usage() {
	sed -n '1,20p' "$0" | tail -n +2
	exit "${1:-0}"
}

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="${WPORG_SLUG:-navitto}"
SVN_URL="https://plugins.svn.wordpress.org/${SLUG}/"
SVN_DIR="${WPORG_SVN_DIR:-${ROOT}/../${SLUG}.svn-wporg}"
README_NAME="readme.txt"
ASSETS_DIR="wordpress-org"

ensure_tools() {
	command -v svn >/dev/null 2>&1 || {
		echo "エラー: svn が見つかりません。" >&2
		exit 1
	}
	command -v rsync >/dev/null 2>&1 || {
		echo "エラー: rsync が見つかりません。" >&2
		exit 1
	}
}

svn_checkout_or_update() {
	if [[ ! -d "${SVN_DIR}/.svn" ]]; then
		echo "SVN を取得します: ${SVN_URL} -> ${SVN_DIR}"
		mkdir -p "$(dirname "${SVN_DIR}")"
		svn checkout --depth immediates "${SVN_URL}" "${SVN_DIR}"
		cd "${SVN_DIR}"
		svn update --set-depth infinity assets
		svn update --set-depth infinity trunk
	else
		cd "${SVN_DIR}"
		svn update
	fi
}

svn_set_image_mime_props() {
	if compgen -G "assets/*.png" >/dev/null; then
		svn propset svn:mime-type "image/png" assets/*.png 2>/dev/null || true
	fi
	if compgen -G "assets/*.jpg" >/dev/null; then
		svn propset svn:mime-type "image/jpeg" assets/*.jpg 2>/dev/null || true
	fi
	if compgen -G "assets/*.svg" >/dev/null; then
		svn propset svn:mime-type "image/svg+xml" assets/*.svg 2>/dev/null || true
	fi
}

svn_remove_deleted() {
	deleted_lines="$(svn status | grep '^\!' || true)"
	if [[ -n "${deleted_lines}" ]]; then
		echo "${deleted_lines}" | sed 's/! *//' | xargs -I% svn rm --force %@
	fi
}

svn_commit_interactive_or_env() {
	local msg="$1"
	svn add . --force >/dev/null 2>&1 || true
	svn_remove_deleted
	svn update
	echo "--- svn status ---"
	svn status
	if [[ -z $(svn stat) ]]; then
		echo "変更なし。コミットしません。"
		return 0
	fi
	if [[ -n "${SVN_USERNAME:-}" ]] && [[ -n "${SVN_PASSWORD:-}" ]]; then
		svn commit -m "${msg}" --no-auth-cache --non-interactive --username "${SVN_USERNAME}" --password "${SVN_PASSWORD}"
	else
		echo "対話式でコミットします（ユーザー名・パスワードの入力が求められる場合があります）。"
		svn commit -m "${msg}"
	fi
}

cmd_readme_assets() {
	ensure_tools
	[[ -f "${ROOT}/${README_NAME}" ]] || {
		echo "エラー: ${ROOT}/${README_NAME} がありません。" >&2
		exit 1
	}
	[[ -d "${ROOT}/${ASSETS_DIR}" ]] || {
		echo "エラー: ${ROOT}/${ASSETS_DIR}/ がありません。" >&2
		exit 1
	}

	svn_checkout_or_update

	echo "readme を trunk にコピーします。"
	cp "${ROOT}/${README_NAME}" "trunk/${README_NAME}"

	echo "${ASSETS_DIR}/ を assets/ に同期します。"
	rsync -rc "${ROOT}/${ASSETS_DIR}/" assets/ --delete --delete-excluded
	svn_set_image_mime_props

	STABLE_TAG="$(grep -m 1 -E '^Stable tag:' "trunk/${README_NAME}" | tr -d '\r' | awk '{print $NF}')"
	if [[ -n "${STABLE_TAG}" ]] && svn info "^/${SLUG}/tags/${STABLE_TAG}" >/dev/null 2>&1; then
		echo "tags/${STABLE_TAG}/ の readme を更新します。"
		svn update --set-depth infinity "tags/${STABLE_TAG}"
		rsync -c "trunk/${README_NAME}" "tags/${STABLE_TAG}/"
	else
		echo "注意: Stable tag「${STABLE_TAG:-未設定}」用の tags/ が SVN にないため、tags/ はスキップしました。"
	fi

	if svn stat trunk | grep -qvi " trunk/${README_NAME}\$"; then
		echo "エラー: trunk に readme 以外の差分があります。中止します。" >&2
		exit 1
	fi

	svn_commit_interactive_or_env "Update readme and plugin directory assets (screenshots, icons, banner)."
	echo "完了。"
}

cmd_trunk() {
	ensure_tools
	svn_checkout_or_update

	echo "プラグインファイルを trunk に同期します（wordpress-org 等は除外）。"
	rsync -rc \
		--exclude ".git/" \
		--exclude ".github/" \
		--exclude "node_modules/" \
		--exclude "${ASSETS_DIR}/" \
		--exclude "scripts/" \
		--exclude ".DS_Store" \
		--exclude "README.md" \
		--exclude "package.json" \
		--exclude "package-lock.json" \
		--exclude "wordpress-org-assets.txt" \
		"${ROOT}/" "trunk/" --delete --delete-excluded

	svn_commit_interactive_or_env "Update plugin trunk from development copy."
	echo "完了。新バージョンを出す場合は svn copy trunk tags/X.Y.Z を実行してください。"
}

main() {
	local sub="${1:-help}"
	case "${sub}" in
	readme-assets) cmd_readme_assets ;;
	trunk) cmd_trunk ;;
	help | -h | --help) usage 0 ;;
	*)
		echo "不明なサブコマンド: ${sub}" >&2
		usage 1
		;;
	esac
}

main "$@"
