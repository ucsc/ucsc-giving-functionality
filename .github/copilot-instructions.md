# UCSC Giving Functionality Plugin - AI Coding Agent Instructions

## Overview
WordPress plugin that creates a custom post type "Fund" for the UCSC Giving website, with external linking to donation forms. Funds can be "Priority" (single post pages) or "Standard" (direct external links).

## Architecture & Key Components

### Core Plugin Structure
- **Main file**: `plugin.php` - Entry point with template registration and ACF JSON configuration
- **Functions**: Split into `lib/functions/general.php` (block bindings, search variations) and `lib/functions/settings.php` (admin pages)
- **Templates**: `lib/templates/` contains block-based templates for single funds and taxonomy archives
- **ACF Configuration**: `acf-json/` directory stores Advanced Custom Fields configuration as JSON

### Block Bindings Integration
The plugin leverages WordPress 6.5+ Block Bindings API:
```php
// In general.php - Custom block binding for dynamic fund URLs
register_block_bindings_source('ucscgiving/fund-url', array(
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
Configured via ACF JSON files:
- **Post Type**: `fund` with custom labels and permalink structure
- **Taxonomies**: area, fund-theme, fund-type, keyword (all attached to fund post type)
- **Meta Fields**: Fund designation code, button text, base URL (global option)

## Development Workflows

### Version Management
Uses `standard-version` for automated releases:
```bash
npm run release        # Create new version tag
npm run dryrun         # Preview changes
```
Custom version updater in `wp-plugin-version-updater.js` updates plugin header.

### Code Standards
```bash
composer lint          # Run PHPCS (WordPress Coding Standards)
composer lint-fix      # Auto-fix code issues with PHPCBF
```

### Build & Package
```bash
npm run zip           # Create plugin ZIP via @wordpress/scripts
```
Includes only files specified in `package.json` "files" array.

## Plugin-Specific Patterns

### External Link Strategy
- **Standard funds**: Permalinks redirected to external donation forms via `ucscgiving_link_filter()`
- **Priority funds**: Custom single page with dynamic "Give" button using block bindings
- Fund URLs constructed: `base_url` (ACF option) + `designation` (post meta)

### Search Functionality
Custom search block variation scoped to Fund post type:
```php
// Creates "Fund Search" block variation in general.php
$variations[] = array(
    'name' => 'fund-search',
    'attributes' => array('query' => array('post_type' => 'fund'))
);
```

### ACF Integration Points
- JSON save/load paths configured in `plugin.php` to store in plugin directory
- Settings page at WordPress Settings > "UCSC Giving Functionality"
- Global options field for base donation URL
- Per-fund designation codes and button text

## Key Files for Common Tasks
- **Adding functionality**: `lib/functions/general.php`
- **Admin features**: `lib/functions/settings.php`
- **Template modifications**: `lib/templates/*.php`
- **Field changes**: Manage via ACF admin (exports to `acf-json/`)
- **Styling**: Admin CSS in `lib/css/admin-settings.css`

## Dependencies
- WordPress 6.5+ (required for Block Bindings API)
- Advanced Custom Fields Pro (declared dependency)
- UCSC-2022 theme (template parts referenced in templates)

## Testing Considerations
- Test both Priority and Standard fund types for correct linking behavior
- Verify block bindings work in block editor
- Check fund search variation appears in inserter
- Validate ACF fields save correctly and appear in templates