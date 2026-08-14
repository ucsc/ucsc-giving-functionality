# UCSC Giving Functionality Plugin - AI Coding Agent Instructions

## Overview
WordPress plugin that creates a custom post type "Fund" for the UCSC Giving website, with external linking to donation forms. Funds can be "Priority" (single post pages) or "Standard" (direct external links).

## Repository Information
- **Repository**: ucsc/ucsc-giving-functionality
- **Default Branch**: main
- **GitHub URL**: https://github.com/ucsc/ucsc-giving-functionality
- **npm package name**: `ucsc-giving-functionality-plugin` (defined in `package.json`)

## Local Development Setup

### Install Dependencies
```bash
composer install       # Install PHP dev dependencies (PHPCS + WordPress Coding Standards)
npm install            # Install Node dev dependencies (@wordpress/scripts, commit-and-tag-version)
```

## Architecture & Key Components

### Core Plugin Structure
- **Main file**: `plugin.php` - Entry point with template registration and ACF JSON configuration
- **Functions**: Split into `lib/functions/general.php` (block bindings, search variations) and `lib/functions/settings.php` (admin settings page)
- **Templates**: `lib/templates/` contains block-based templates for single funds and taxonomy archives
- **Template Parts**: `lib/templates/parts/` contains reusable template partials (`funds-search.php`, `post-query-funds.php`), pulled in with `__DIR__`-relative `require`
- **Styles**: `lib/css/admin-settings.css` - Admin settings page styles
- **Scripts**: `lib/js/fund-type-radio.js` - the only browser JavaScript. Hand-written against the `wp.*` globals, no build step and no bundler; nothing lints it, so match the surrounding style by hand. Both this and the CSS are enqueued from `plugin.php`, because `plugin_dir_url( __FILE__ )` only resolves correctly from there.
- **ACF Configuration**: `acf-json/` directory stores Advanced Custom Fields configuration as JSON

### Block Bindings Integration
The plugin leverages WordPress 6.5+ Block Bindings API:
```php
// In general.php - Custom block binding for dynamic fund URLs
register_block_bindings_source('ucscgiving/fund-url', array(
    'label'              => __( 'Fund URL', 'ucscgiving' ),
    'get_value_callback' => 'ucscgiving_fund_url'
));
```
Used in templates: `"bindings":{"url":{"source":"ucscgiving/fund-url"}}`

### Template System
Block templates are registered programmatically in `plugin.php`:
- Templates stored as PHP files in `lib/templates/`
- Content loaded via `ucscgiving_get_template_content()` callback
- Covers: single-fund, archive-fund, and taxonomy archives (area, fund-theme, fund-type, keyword)

### Custom Post Type & Taxonomies
Configured via ACF JSON files in `acf-json/`:
- **Post Type**: `fund` with custom labels and permalink structure (`post_type_67b609ae42d72.json`)
- **Taxonomies**: area, fund-theme, fund-type, keyword — all attached to the `fund` post type
- **`fund-type` owns fund type outright** (#3): `show_ui: 1` and `show_in_rest: 1`, edited through the standard taxonomy panel. Do not add an ACF taxonomy field targeting `fund-type` — that reintroduces the second writer behind the old double-save bug.
- **A fund is Standard or Priority, never both.** `lib/js/fund-type-radio.js` makes the editor panel a radio via the `editor.PostTaxonomyType` filter (`meta_box_cb` does not work — the block editor discards taxonomy meta boxes unconditionally), and `ucscgiving_enforce_single_fund_type()` on `set_object_terms` normalises Quick Edit, Bulk Edit, REST and WP-CLI writes. Do not move that to `save_post`: the REST controller fires it before `handle_terms()` writes the terms.
- **Field Groups**: Fund designation code, button text (`group_67b76c100ca57.json`, `group_67c4adfce3d52.json`)
- **Meta Fields**: `designation` (post meta), `button_text` (post meta), `base_url` (global ACF option)

## Development Workflows

### Version Management
Uses `commit-and-tag-version` for automated releases, driven by **conventional commits** — the commit prefix is functional, not cosmetic (`fix:` → patch, `feat:` → minor, `chore:`/`ci:`/`docs:` → no bump):
```bash
npm run release        # Create new version tag and update CHANGELOG.md
npm run dryrun         # Preview version bump and changelog changes
```
The custom updater `wp-plugin-version-updater.js` bumps the `Version:` header in `plugin.php` in addition to `package.json` and `package-lock.json`.

### Code Standards
```bash
composer lint          # Run PHPCS (WordPress Coding Standards)
composer lint-fix      # Auto-fix code issues with PHPCBF
```
PHPCS is configured in `.phpcs.xml.dist`:
- Ruleset: `WordPress-Extra` + `WordPress-Docs`
- **Text domain**: `ucscgiving`
- **Global prefix**: `ucscgiving` (all globals must use this prefix)
- Vendor and node_modules directories are excluded

### Build & Package
```bash
npm run zip           # Create distributable plugin ZIP via @wordpress/scripts
```
Files listed in the `"files"` array in `package.json` are included: `acf-json/`, `lib/`, `plugin.php`, `README.md`, `CHANGELOG.md`, `LICENSE`. `package.json` itself is also included — npm-packlist always ships it regardless of `files`. CI asserts the ZIP contents on every pull request.

## Plugin-Specific Patterns

### Naming Conventions
- All PHP functions, hooks, and global variables must be prefixed with `ucscgiving_`
- Text domain for all translatable strings: `ucscgiving`
- Block binding source name: `ucscgiving/fund-url`

### External Link Strategy
- **Standard funds**: Permalinks redirected to external donation forms via `ucscgiving_link_filter()` (hooked to `post_type_link`)
- **Priority funds**: Custom single page with dynamic "Give" button using block bindings
- Fund URLs constructed: `base_url` (ACF global option) + `designation` (post meta field `designation`), composed in one place by `ucscgiving_build_fund_url()`
- Fund type is read with `has_term( 'Standard', 'fund-type', $post->ID )` — straight from the taxonomy, which is the only place it is stored

### Search Functionality
Custom search block variation scoped to Fund post type:
```php
// Creates "Fund Search" block variation in general.php
$variations[] = array(
    'name'       => 'fund-search',
    'attributes' => array(
        'query'       => array('post_type' => 'fund'),
        'placeholder' => __( 'Search Funds', 'ucscgiving' ),
    ),
);
```
Fund search results are returned using the archive template via `ucscgiving_fund_search_template()` (hooked to `search_template`).

### ACF Integration Points
- JSON save/load paths configured in `plugin.php` to store in `acf-json/` within plugin directory
- Settings page at WordPress Settings > "UCSC Giving Functionality" (slug: `ucsc-giving-functionality-settings`)
- Global options field group for base donation URL
- Per-fund designation codes and button text stored as ACF fields

## Key Files for Common Tasks
- **Adding functionality**: `lib/functions/general.php`
- **Admin features**: `lib/functions/settings.php`
- **Template modifications**: `lib/templates/*.php`
- **Template parts (reusable blocks)**: `lib/templates/parts/`
- **Field changes**: Manage via ACF admin (auto-exports to `acf-json/`)
- **Styling**: Admin CSS in `lib/css/admin-settings.css`
- **Block editor behavior**: `lib/js/fund-type-radio.js`, enqueued from `plugin.php`
- **Version bumping**: `wp-plugin-version-updater.js` + `package.json` `commit-and-tag-version` config

## Dependencies
- WordPress 6.5+ (required for Block Bindings API via `register_block_bindings_source`)
- Advanced Custom Fields Pro (declared plugin dependency via `Requires Plugins` header)
- UCSC-2022 theme (template parts referenced in block templates)

## Testing
`composer test` runs a PHPUnit suite; `composer lint` runs PHPCS. Both run on every pull request via `.github/workflows/ci.yml` on PHP 8.1 and 8.4.

The suite stubs WordPress (`tests/bootstrap.php`) rather than running inside it, so it covers only functions that are pure once WordPress is stubbed. Everything else still needs manual validation:
- Test both Priority and Standard fund types for correct linking behavior
- Verify block bindings render correct URLs in the block editor and on the front end
- Check the "Fund Search" block variation appears in the block inserter
- Validate ACF fields save correctly and that values appear in templates
- Confirm the Fund Type panel renders as radio buttons and a type change sticks on a single save — `lib/js/fund-type-radio.js` has no automated coverage
- Run `composer lint` and `composer test` before committing; CI runs both and will fail the PR otherwise