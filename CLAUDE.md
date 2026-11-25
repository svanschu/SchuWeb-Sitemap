# SchuWeb Sitemap

Sitemap generator for Joomla (HTML sitemap view + static XML / news / image sitemaps).
Released as one package (`pkg_schuweb_sitemap`) that must run on **Joomla 4, 5 and 6** at the same time.

## Repository layout

```
src/
  pkg_schuweb_sitemap.xml, install.script.php   package manifest + package install script
  extension.script.php                          component install script
  administrator/components/com_schuweb_sitemap  backend (manifest schuweb_sitemap.xml, sql/, forms/, src/, tmpl/)
  components/com_schuweb_sitemap                frontend (src/Model, src/Event, src/View, tmpl/)
  libraries/schuweb_sitemap                     small library (Button/DefaultButton)
  media/com_schuweb_sitemap                     css/js/images
  plugins/schuweb_sitemap/{content,newsfeeds}   bundled sitemap plugins
  plugins/task/schuwebsitemap                   scheduler task: regenerates the XML files
builder.xml      Phing build (creates package zip)
update.xml       Joomla update server feed (one <update> per Joomla major: 6, 5, 4)
changelog.xml    Joomla changelog feed (<fix>/<addition>/<change>/<remove>/<note>)
dev/             container dev setup script
```

Namespaces: component `SchuWeb\Component\Sitemap\{Site,Administrator}`, plugins `SchuWeb\Plugin\SchuWeb_Sitemap\<Name>`.

### Separate plugin repositories (siblings in `~/Git`, same layout: `src/<element>/`, `builder.xml`, `update.xml`, `changelog.xml`, `dev/`)

| Repo | Plugin element | Status |
|------|----------------|--------|
| SchuWeb-Sitemap-DPCalendar | dpcalendar | active |
| SchuWeb-Sitemap-Kunena | kunena | active |
| SchuWeb-Sitemap-Weblinks | weblinks | active |
| SchuWeb-Sitemap-Zoo | zoo | active |
| SchuWeb-Sitemap-Ignite-Gallery | ignitegallery | active |
| SchuWeb-Sitemap-Virtuemart | – | on hold, not adapted to the event API; do not touch unless asked |
| SchuWeb-Sitemap-SobiPro | – | retired (SobiPro discontinued end of 2026) |

## Architecture

- Frontend `Site\Model\SitemapModel` loads the menus of a sitemap and builds a node tree
  (`getNodes()` → `getMenus()` → `getSubNodes()`).
- Extensions add content via plugins in group **`schuweb_sitemap`** (`CMSPlugin` + `SubscriberInterface`).
  Two events are dispatched on the model's dispatcher:
  - `onGetMenus` → `Site\Event\MenuItemPrepareEvent` (`getMenuItem()`): adjust a menu item before it is used.
  - `onGetTree` → `Site\Event\TreePrepareEvent` (`getSitemap()`, `getNode()`): append child nodes for a menu item.
  - `onGetImages` → `Site\Event\ImagesPrepareEvent` (`getText()`, result-aware, array results). This one is dispatched
    by the **content plugin** (not the model), for each article's text in image sitemap mode. Gallery plugins
    (e.g. Ignite Gallery) answer it with the images they find embedded in the article.
- Plugins add children by building `\stdClass` nodes (`id`, `uid`, `name`, `link`, `priority`, `changefreq`,
  `modified`, `expandible`, `browserNav`, optional `images`/`newsItem`) and attaching them as
  `$parent->subnodes->$uid = $node`. `uid` = parent uid + type letter + id (e.g. `c12`, `i34`).
- Plugins must check the output mode on the sitemap model: `isXmlsitemap()`, `isNewssitemap()`, `isImagesitemap()`.
  They must only add items that are public and published in XML mode (access level / publish dates / language).
- `Site\Model\SitemapXmlModel` renders the tree with `XMLWriter` and writes static files to `JPATH_SITE`:
  `sitemap.xml` (default sitemap) or `<name>.xml`, plus the `_news` / `_images` suffixes. It is triggered from the backend
  toolbar (`sitemaps.createxml*`) and from the task plugin.
- The legacy `SchuWeb_Sitemap_<element>::getTree()` / `prepareMenuItem()` static class path in `SitemapModel` is
  marked `@deprecated`, to be removed with extension v6. New code uses the events only.

## Compatibility rules (J4 + J5 + J6, PHP 8.1 – 8.5)

- Only use APIs that exist in **all three** Joomla versions without the backward-compat plugins:
  - no `Factory::getDbo()` (use `Factory::getContainer()->get(DatabaseInterface::class)` or `DatabaseAwareTrait`)
  - no `CMSObject` (one left in admin `SitemapModel.php` – known), no `J*` legacy class aliases
  - prefer `$app->getInput()` / `$app->getIdentity()` over deprecated `$app->input` / `Factory::getUser()`
  - when unsure, check the deprecation notes in the J6 core source (see below)
- Make nullable parameters explicit (`?Type $x = null`); implicit nullable is deprecated since PHP 8.4.
- Avoid dynamic properties on classes (deprecated in 8.2) – `\stdClass` nodes are fine.
- Joomla core source for checking what an API looks like in each version:
  `~/container/sitemap-dev/{j4,j5,j6}/html/libraries/src/`.
- Code style: PSR-12, 4 spaces. Every PHP file starts with the docblock header
  (`@version sw.build.version`, copyright, GPL-3.0-or-later, author) and `\defined('_JEXEC') or die;`.

## Build placeholders

`sw.build.version`, `sw.build.date`, `sw.build.dstamp` are replaced by Phing at build time.
**Never replace them with real values in the source**: manifests, docblocks and the package file list depend on them.

`__BUMP_VERSION__` is different: use it in `@since` tags of **new** classes and methods while developing.
It is not touched by the build. Replace it in the code with the exact release version just before a release.

## Language files

INI files exist in en-GB (source of truth) plus cs-CZ, es-ES, fa-IR, fr-FR, nl-NL, ru-RU.
When adding a string, add it to en-GB; also register new language files in the matching manifest `<languages>` block.

## Dev environment (podman)

`~/container/sitemap-dev/podman-compose.yml`. The containers are **not** always running; ask before starting them.
The stack runs **rootful**: `sudo podman-compose up -d db-j5 php-j5` (the data dirs are owned by host uid 999/33).
Rootless podman fails on the DB data dir, and Claude cannot `sudo`, so ask the user to start or stop it.
With the DB port published, Claude can still query it from the host: `mariadb -h127.0.0.1 -P43011 -ujdev -pjdevpw jdevdb`.

| Joomla | Web | MariaDB (host) | Xdebug port | Web root on host |
|--------|-----|----------------|-------------|------------------|
| J4 | http://localhost:43000 | 43001 | 9003 | `~/container/sitemap-dev/j4/html` |
| J5 | http://localhost:43010 | 43011 | 9004 | `~/container/sitemap-dev/j5/html` |
| J6 | http://localhost:43020 | 43021 | 9005 | `~/container/sitemap-dev/j6/html` |

- DB: `jdevdb`, user `jdev` / `jdevpw` (dev only). DB host inside the network: `db-j4|db-j5|db-j6`.
- Every repo is mounted into the PHP containers at `/<RepoName>` (e.g. `/SchuWeb-Sitemap`); the Joomla trees contain
  symlinks to those paths, so source edits are live. `dev/setup-dev-container.sh` creates **all** links, including the
  sibling plugin repos. Run it inside the container (no argument) or on the host as `sudo dev/setup-dev-container.sh <webroot>`.
  It is safe to run again. The links point at container paths and therefore dangle on the host.
  `plugins/schuweb_sitemap` must be a real directory with one link per plugin. A link to the whole folder makes other
  scripts write into the git tree.
- Container service names: `php-j4|php-j5|php-j6`, e.g. `podman-compose exec php-j6 bash` from `~/container/sitemap-dev`.
- Joomla logs: `<webroot>/administrator/logs/`.
- Xdebug launch configs (VS Code, PHP Debug) live in `<webroot>/.vscode/launch.json` per Joomla version.
- `~/container/sitemap-install-test` uses the same ports, without source mounts. It is for installing built zips
  (fresh install + update from the previous release). Only one of the two stacks can run at a time.

## Build & release

- Build: `dev/build.sh X.Y.Z` (from the repo root). It runs Phing 3 in a podman container
  (`dev/build/Containerfile`, PHP 8.3 CLI, pinned Phing version, image `localhost/schuweb-phing:<ver>` built on first use).
  It also works for the plugin repos: `../SchuWeb-Sitemap/dev/build.sh X.Y.Z` from inside a plugin repo.
  Output: `~/Git/packages/SchuWeb_Sitemap/pkg_schuweb_sitemap_X.Y.Z_<YYYYMMDD>.zip`.
  It warns if `__BUMP_VERSION__` is still present. `0.0.0` is fine for test builds; delete the zip afterwards.
- Branches: development on `next`, releases merged to `main` (tag `X.Y.Z`). The update feeds are served from `main`.
- Before building: replace all `__BUMP_VERSION__` with `X.Y.Z` (`grep -rn __BUMP_VERSION__ src`).
- Release commit ("update update.xml and changelog for X.Y.Z"):
  - `changelog.xml`: new `<changelog>` block at the top.
  - `update.xml`: bump version, infourl, download URL
    `https://github.com/svanschu/SchuWeb-Sitemap/releases/download/X.Y.Z/pkg_schuweb_sitemap_X.Y.Z_<YYYYMMDD>.zip`
    and `<sha512>` of the built zip in **each** targetplatform block (6, 5, 4).
- Before release: install test + update test from previous version in `sitemap-install-test`, JED checker.

## Verifying changes

There are no automated tests. At minimum:
- `php -l` on changed files.
- If containers run: load the HTML sitemap and regenerate the XML on J4, J5 and J6. Check that the XML is well-formed
  (`xmllint --noout`) and that the Joomla logs have no new warnings or deprecations.
