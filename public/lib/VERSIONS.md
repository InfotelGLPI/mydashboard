# Third-party libraries shipped in `public/lib`

The plugin ships its front-end dependencies as pre-built files instead of resolving
them through npm: there is no JavaScript build step, and nothing in `node_modules`
is ever served.

`package.json` and `package-lock.json` at the root of the plugin pin the versions
listed below so that `npm audit` and Dependabot can see them. Installing them produces
no artefact the plugin uses -- they exist for the tooling, not for the build. Run
`npm run audit` to confront the shipped versions with the upstream advisories.

This file stays the reference for what is actually vendored: **every time a library
below is added, upgraded or removed, update the matching row, then the manifest**.
Two entries cannot be pinned there and are tracked here only:

- `diacritics.js` has no upstream version at all (single unversioned file);
- `jquery-fullscreen-plugin` 1.1.4 was never published on npm, which only carries
  1.0.0 and 1.1.5.

In each case pinning a plausible version would misstate what is actually shipped, which
in a manifest whose only purpose is security tracking is worse than the omission.

| Path | Library | Version | Licence | Upstream |
| --- | --- | --- | --- | --- |
| `circles/circles.min.js` | Circles | 0.0.6 | MIT | https://github.com/lugolabs/circles |
| `countUp.min.js` | countUp.js | 1.9.3 | MIT | https://github.com/inorganik/countUp.js |
| `countUp-jquery.js` | countUp.js jQuery adapter | 1.9.3 | MIT | https://github.com/inorganik/countUp.js |
| `datatables/` | DataTables bundle: DataTables 2.3.7, Buttons 3.2.6, ColReorder 2.1.2, JSZip 3.10.1, pdfmake 0.2.7 | see components | MIT | https://datatables.net/download/ |
| `diacritics.js` | diacritics (removeDiacritics) | n/a (single file, unversioned upstream) | MIT | https://github.com/andrewrk/node-diacritics |
| `echarts/theme/*.js` | ECharts themes | taken from the ECharts 6.1.0 distribution | Apache-2.0 | https://github.com/apache/echarts |
| `fuse.js` | Fuse.js | 6.6.2 | Apache-2.0 | https://github.com/krisk/Fuse |
| `gridstack/` | GridStack.js | 11.0.1 | MIT | https://github.com/gridstack/gridstack.js |
| `html2canvas.min.js` | html2canvas | 1.4.1 | MIT | https://github.com/niklasvh/html2canvas |
| `jquery-advanced-news-ticker/` | jQuery Advanced News Ticker | 1.0.11 | MIT | https://github.com/risq/jquery-advanced-news-ticker |
| `jquery-fullscreen-plugin/` | jQuery Fullscreen Plugin | 1.1.4 | MIT | https://github.com/kayahr/jquery-fullscreen-plugin |
| `jspdf.umd.js` | jsPDF | 4.2.1 | MIT | https://github.com/parallax/jsPDF |

`echarts/echarts.js` was removed: the engine now comes from the core, which serves it
at `lib/echarts.js` and is what `Menu::loadDashboard()` loads. A bundle of our own used to
be registered on every page of the central interface, and since the scripts of a plugin
are emitted after those of the core in `page_footer.html.twig`, it took over
`window.echarts` for the dashboards and the statistics of the core as well. Only the
themes stay here: the core ships none, and they are plain `echarts.registerTheme()` calls
that ECharts accepts across major versions. The `echarts` entry of `package.json` pins the
release those theme files were taken from, not the engine the plugin runs on.

`fileSaver.min.js` and `jquery-ui/` were removed: nothing called them. FileSaver was not
even loaded (the only `saveAs()` sat in a comment, and the `saveAsImage` of the ECharts
toolbox is unrelated). jQuery UI was loaded for an API no file uses: GridStack 11 carries
its own drag and resize and mentions jQuery nowhere, the DataTables bundle does not
reference it either, and the `ui-draggable-*`, `ui-resizable-*` and `ui-droppable-*`
classes GridStack does emit are its own, styled by its own stylesheet. It only ever
reached the dashboard page -- `Menu::loadDashboard()` loaded it inline, never
`ADD_JAVASCRIPT` -- so no other page could depend on it, and none of the plugins that
contribute widgets to the grid calls a jQuery UI widget from the code those widgets are
rendered by. The `.ui-sortable-*` and `.ui-state-disabled` rules of `css/mydashboard.css`
went with it, together with `css/hideinfo.css`, `css/info.css` and
`css/style_bootstrap_new.css`, which no code path loaded.

`circles/` stays, even though this plugin never calls `Circles.create()` itself. The
dashboard is a host: the widgets other plugins contribute are injected into its grid by
AJAX, and servicecatalog renders `indicator_circles_script.js.twig` into it, which expects
`Circles` on the page the host built. A library loaded by `Menu::loadDashboard()` serves
the whole page, widgets included, so a search restricted to this plugin says nothing about
whether one is needed.

`echarts/theme/tool/` was removed: it held the thumbnail generator upstream uses to
build the theme gallery (a servable `thumb.html` plus six sample option files), belonged
to no released ECharts version, was shipped by no upstream package, and was referenced by
nothing in the plugin. It had no business being served from `public/`.

`md-fuzzysearch.js` is **not** a third-party library: it is plugin code (the `md-` prefix
stands for mydashboard) that happens to live in this directory.

## Licence headers

Many of the files above start with the mydashboard GPL header, prepended years ago by a
licence-header run that did not exclude this directory. That header does **not** describe
those files: the licence that applies to each of them is the one in the table, and the
upstream notice usually still follows the GPL block inside the file.

ECharts, its themes and jsPDF no longer do: the upgrade to 6.1.0 and 4.2.1 re-downloaded
them from upstream, so those thirty-six files now carry their own Apache-2.0 and MIT
notices. That is the mechanism by which the stale headers get fixed -- one library at a
time, whenever it is upgraded -- rather than by rewriting vendored builds by hand.

`tools/regenerate_headers.php` excludes `public/lib` (along with `vendor`,
`node_modules`, `lib`, `dist` and `var`), so the situation cannot get worse for the
libraries still waiting their turn.
