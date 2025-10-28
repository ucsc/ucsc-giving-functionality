# UCSC Giving Functionality Plugin - AI Coding Instructions

## Project Overview
This is a WordPress plugin that creates a "Fund" custom post type for UC Santa Cruz's giving website. The core architecture revolves around connecting funds to external donation forms via URL construction and custom block bindings.

## Critical Architecture Patterns

### Block Bindings Integration
- Uses WordPress 6.5+ Block Bindings API in `lib/functions/general.php`
- `ucscgiving_register_fund_url_block_binding()` registers custom source `ucscgiving/fund-url`
- Concatenates ACF option `base_url` + post meta `designation` for dynamic fund URLs
- Template `single-fund.php` uses `{"source":"ucscgiving/fund-url"}` binding on button URLs

### Fund Type Logic (Standard vs Priority)
- Standard funds: Redirect directly to external form (see `ucscgiving_link_filter()`)
- Priority funds: Display single post template with content + "Give" button
- Fund type determined by `fund-type-term` ACF field referencing taxonomy

### ACF JSON Management
- All ACF configurations stored in `/acf-json/` directory
- Plugin sets custom save/load points in `plugin.php` to keep fields with plugin
- Post type and taxonomies defined via ACF JSON files, not PHP registration

## Key Development Workflows

### Version Management
```bash
npm run release     # Creates new version with standard-version
npm run dryrun      # Preview version changes
npm run zip         # Package for distribution
```

### Code Standards
```bash
composer run lint           # Check PHP code standards (WPCS)
composer run lint-fix      # Auto-fix PHP code standards
```

### Template System
- Block templates registered via `ucscgiving_register_block_templates()`
- Templates in `/lib/templates/` use WordPress block markup
- Custom search block variation for fund-specific search results

## Project-Specific Conventions

### Naming Patterns
- All functions prefixed with `ucscgiving_`
- Text domain: `ucscgiving`
- Plugin directory constant: `UCSCGIVING_PLUGIN_DIR`

### File Organization
- Core plugin logic in `plugin.php` (includes, constants, ACF hooks)
- General functions in `lib/functions/general.php`
- Settings/admin in `lib/functions/settings.php`
- Block templates in `lib/templates/`
- ACF configurations in `acf-json/`

### External Dependencies
- Requires Advanced Custom Fields Pro plugin
- WordPress 6.5+ for Block Bindings API
- Uses standard-version for semantic versioning

## Template Integration Points
- Block templates return content via `ucscgiving_get_template_content()`
- Templates use WordPress block syntax with custom bindings
- Search results redirect to archive template for fund post type
- Single fund template uses hero banner with ACF-bound button

## Critical Files for Changes
- `plugin.php`: Core hooks, ACF integration, template registration
- `lib/functions/general.php`: Block bindings, search variations, URL logic
- `lib/templates/single-fund.php`: Priority fund display template
- `acf-json/`: Field definitions and post type configuration