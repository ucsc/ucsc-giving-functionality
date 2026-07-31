# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer install      # PHP dev deps (PHPCS + WordPress Coding Standards)
npm install           # Node dev deps (@wordpress/scripts, commit-and-tag-version)

composer lint         # PHPCS across the repo
composer lint-fix     # PHPCBF auto-fix

npm run zip           # Build distributable ZIP (wp-scripts plugin-zip)
npm run dryrun        # Preview version bump + changelog
npm run release       # Tag a release and update CHANGELOG.md
```

There is **no automated test suite** — no PHPUnit, no wp-env config, no JS tests. `composer lint` is the only mechanical gate. Validate behavior by hand against a WordPress install with ACF Pro and the `ucsc-2022` theme active.

## Repository shape

This is a **standalone plugin repo**, not a site tree. There is no `wp-content/`, so tooling that scans for `wp-content/plugins/*` reports zero plugins here. The bootstrap is `plugin.php` at the repo root — not a file named after the plugin.

The code is **procedural, not class-based**: `plugin.php` plus two files in `lib/functions/`. There are no namespaces, no autoloader, and no loader class. Everything is a top-level function registered with `add_action`/`add_filter` at file scope. Composer is dev-only (PHPCS); `vendor/` is never shipped or loaded at runtime.

## External dependencies that are easy to miss

Both of these live outside this repo and the plugin is substantially non-functional without them:

- **ACF Pro** — declared via the `Requires Plugins: advanced-custom-fields-pro` header. Not on WP.org, so it cannot be installed automatically. All fund metadata flows through `get_field()`.
- **The `ucsc-2022` theme** — two separate couplings:
  - The `base_url` ACF field lives on the **`ucsc-theme-options` options page, which the theme registers**, not this plugin. Without the theme active, `get_field( 'base_url', 'option' )` returns nothing and every external fund URL silently collapses.
  - Block templates reference the theme's template parts by name (`header`, `breadcrumbs`, `footer`, `theme":"ucsc-2022"`).

## Architecture

### Where the data model lives

`acf-json/` is the **source of truth** for the `fund` post type, the four taxonomies (`area`, `fund-type`, `fund-theme`, `keyword`), and the field groups. None of it is registered in PHP. Field changes are made in the ACF admin UI and auto-export back to `acf-json/` via the save/load point filters in `plugin.php`. Editing these JSON files by hand is possible but the field `key` values (`field_*`, `group_*`) are referential — do not renumber them.

### The fund linking model

This is the core behavior. Funds carry a `fund-type` term and a `designation` post meta value:

- **`Standard` fund type** → `ucscgiving_link_filter()` (on `post_type_link`) replaces the permalink with `base_url . designation`, sending visitors straight to the external giving form.
- **Anything else** (the README calls these "Priority") → keeps its normal permalink and renders `single-fund.php`, where a **block binding** (`ucscgiving/fund-url`, registered in `general.php`) supplies the same computed URL to the "Give" button.

So the same URL is computed by two different paths — `ucscgiving_fund_url()` for the binding, `ucscgiving_link_filter()` for the permalink. Changes to URL construction must be made in both or they will drift.

`fund-type-term` is an ACF taxonomy field with `return_format = id`, so it yields a term ID that needs `get_term()` to resolve.

### Templates are block markup, not PHP

`lib/templates/*.php` are block-comment markup (`<!-- wp:… -->`) with a couple of `require` statements, not PHP source. They are read through `ucscgiving_get_template_content()` (output buffering) and handed to `register_block_template()` in `plugin.php`.

Two consequences:
- The `require 'parts/…'` calls inside them use **relative paths**, which resolve only because PHP falls back to the calling file's directory. Fragile — prefer `__DIR__` if touching them.
- PHPCS file-docblock sniffs are excluded for `lib/templates/` in `.phpcs.xml.dist`, since a file docblock there is meaningless.

## Conventions

- **Prefix everything `ucscgiving_`** — functions, hooks, globals. PHPCS enforces this via `WordPress.NamingConventions.PrefixAllGlobals`.
- **Text domain is `ucscgiving`** — enforced by PHPCS; a missing domain is a lint error.
- PHPCS ruleset is `WordPress-Extra` + `WordPress-Docs`. The scan is **restricted to `extensions="php"`** because WPCS 3.x dropped its JS/CSS sniffs — do not re-widen it or `.css`/`.js` files will produce noise.
- WordPress tabs for indentation in PHP, despite the `.editorconfig` (which is 4-space and applies mainly to non-PHP files).

## Releases

Releases are driven by **conventional commits** — `commit-and-tag-version` reads them to decide the bump, so the commit prefix is functional, not cosmetic (`fix:` → patch, `feat:` → minor, `chore:` → no bump).

`npm run release` bumps `package.json`, `package-lock.json`, and the `Version:` header in `plugin.php` (via the custom updater `wp-plugin-version-updater.js`). Pushing a `v*.*.*` tag triggers `.github/workflows/release.yml`, which delegates to the shared `ucsc/actions` workflow and publishes `ucsc-giving-functionality-plugin.zip`.

Only paths in the `files` array of `package.json` ship in the ZIP: `acf-json/`, `lib/`, `plugin.php`, `README.md`, `CHANGELOG.md`, `LICENSE`.

The updater parses the header with a single regex covering multi-digit segments and an optional prerelease suffix, and throws rather than writing if the header is missing — keep both `readVersion` and `writeVersion` on that shared pattern so they cannot diverge.

## Related

`.github/copilot-instructions.md` covers much of the same ground for GitHub Copilot. Keep the two roughly in sync when conventions change; note it still refers to `standard-version`, which was replaced by `commit-and-tag-version`.
