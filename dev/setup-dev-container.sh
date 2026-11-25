#!/usr/bin/env bash
# Symlink SchuWeb Sitemap (and its sibling plugin repos) into a Joomla installation.
#
# Usage:
#   inside the container:  /SchuWeb-Sitemap/dev/setup-dev-container.sh
#   on the host:           sudo dev/setup-dev-container.sh ~/container/sitemap-dev/j6/html
#
# Link targets are always the *container* mount paths (/SchuWeb-Sitemap...), so the
# links dangle on the host but work inside the PHP container. Safe to run repeatedly.
set -euo pipefail

WEBROOT="${1:-/var/www/html}"
SRC=/SchuWeb-Sitemap/src

[[ -f "$WEBROOT/configuration.php" || -f "$WEBROOT/index.php" ]] || {
    echo "Not a Joomla root: $WEBROOT" >&2
    exit 1
}
cd "$WEBROOT"

# link <target> <link path>: replace an existing link, never write through it
link() {
    local target=$1 name=$2
    if [[ -e "$name" && ! -L "$name" ]]; then
        echo "SKIP $name (real file/dir exists - installed via installer?)" >&2
        return
    fi
    mkdir -p "$(dirname "$name")"
    ln -sfn "$target" "$name"
    echo "$name -> $target"
}

link "$SRC/administrator/components/com_schuweb_sitemap" administrator/components/com_schuweb_sitemap
link "$SRC/components/com_schuweb_sitemap"               components/com_schuweb_sitemap
link "$SRC/libraries/schuweb_sitemap"                    libraries/schuweb_sitemap
link "$SRC/libraries/schuweb_sitemap/schuweb_sitemap.xml" administrator/manifests/libraries/schuweb_sitemap.xml
link "$SRC/media/com_schuweb_sitemap"                    media/com_schuweb_sitemap

# plugins/schuweb_sitemap must be a real directory with one link per plugin.
# If it is a link to the repo folder, other repos' setup scripts would write into the git tree.
if [[ -L plugins/schuweb_sitemap ]]; then
    rm plugins/schuweb_sitemap
fi
mkdir -p plugins/schuweb_sitemap

link "$SRC/plugins/schuweb_sitemap/content"   plugins/schuweb_sitemap/content
link "$SRC/plugins/schuweb_sitemap/newsfeeds" plugins/schuweb_sitemap/newsfeeds

# Sibling plugin repos (mounted at /SchuWeb-Sitemap-<Repo>, see podman-compose.yml)
link /SchuWeb-Sitemap-DPCalendar/src/dpcalendar          plugins/schuweb_sitemap/dpcalendar
link /SchuWeb-Sitemap-Kunena/src/kunena                  plugins/schuweb_sitemap/kunena
link /SchuWeb-Sitemap-Weblinks/src/weblinks              plugins/schuweb_sitemap/weblinks
link /SchuWeb-Sitemap-Zoo/src/zoo                        plugins/schuweb_sitemap/zoo
link /SchuWeb-Sitemap-Ignite-Gallery/src/ignitegallery   plugins/schuweb_sitemap/ignitegallery

# Task plugin: element is "schuwebsitemap"; remove the old wrong link name
if [[ -L plugins/task/schuweb_sitemap ]]; then
    rm plugins/task/schuweb_sitemap
fi
link "$SRC/plugins/task/schuwebsitemap" plugins/task/schuwebsitemap

# Language files: Joomla also finds them in the extension folders, these links are a convenience
for f in \
    components/com_schuweb_sitemap/language/en-GB/com_schuweb_sitemap.ini \
    plugins/schuweb_sitemap/content/language/en-GB/plg_schuweb_sitemap_content.ini \
    plugins/schuweb_sitemap/newsfeeds/language/en-GB/plg_schuweb_sitemap_newsfeeds.ini \
    plugins/schuweb_sitemap/newsfeeds/language/en-GB/plg_schuweb_sitemap_newsfeeds.sys.ini \
    plugins/task/schuwebsitemap/language/en-GB/plg_task_schuwebsitemap.ini \
    plugins/task/schuwebsitemap/language/en-GB/plg_task_schuwebsitemap.sys.ini; do
    link "$SRC/$f" "language/en-GB/$(basename "$f")"
done
for f in com_schuweb_sitemap.ini com_schuweb_sitemap.sys.ini; do
    link "$SRC/administrator/components/com_schuweb_sitemap/language/en-GB/$f" "administrator/language/en-GB/$f"
done

# Keep ownership consistent with the web root (links created as root otherwise)
owner=$(stat -c '%u:%g' .)
chown -h "$owner" \
    administrator/components/com_schuweb_sitemap components/com_schuweb_sitemap \
    libraries/schuweb_sitemap administrator/manifests/libraries/schuweb_sitemap.xml \
    media/com_schuweb_sitemap plugins/task/schuwebsitemap \
    language/en-GB/*schuweb* administrator/language/en-GB/*schuweb* 2>/dev/null || true
chown -h "$owner" plugins/schuweb_sitemap plugins/schuweb_sitemap/* 2>/dev/null || true

echo "Done: $WEBROOT"
