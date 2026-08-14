# Roadmap

Engineering roadmap for the UCSC Giving Functionality plugin, derived from the state of the repository as of **2026-08-04** (v0.5.8).

This is a technical roadmap, not a product plan. Items are grounded in open issues, in-flight pull requests, and observed characteristics of the codebase — it does not attempt to set feature direction, which is the University Advancement web team's call. Sequencing below reflects dependency order and risk, not committed dates.

---

## In flight

Nothing. The dual source of truth for fund type ([#3](https://github.com/ucsc/ucsc-giving-functionality/issues/3)) was the last open item; see [§1](#1-fund-type-has-one-source-of-truth-the-taxonomy).

### Recently landed

- [#3](https://github.com/ucsc/ucsc-giving-functionality/issues/3) — fund type now has exactly one writer. The ACF field `fund-type-term` is gone and the `fund-type` taxonomy has `show_ui` on, so the term is edited through WordPress's own panel and `handle_terms()` is the only thing writing it. See §1 for why this option was taken over the ACF one.
- [#131](https://github.com/ucsc/ucsc-giving-functionality/pull/131) ([#128](https://github.com/ucsc/ucsc-giving-functionality/issues/128)) — the PHPUnit harness described in §3. First automated behavioral coverage in the repo.
- [#130](https://github.com/ucsc/ucsc-giving-functionality/pull/130) ([#127](https://github.com/ucsc/ucsc-giving-functionality/issues/127)) — `ci.yml`, so pull requests are validated at all. Also declared the PHP floor (8.1), which had been recorded in neither `composer.json` nor the plugin header, and aligned the WPCS target version with `Requires at least`.
- [#125](https://github.com/ucsc/ucsc-giving-functionality/pull/125) ([#122](https://github.com/ucsc/ucsc-giving-functionality/issues/122)) — `CLAUDE.md` and this roadmap.
- [#121](https://github.com/ucsc/ucsc-giving-functionality/pull/121) — seven correctness and hygiene fixes: a `search_template` filter that had never applied, an unguarded `get_current_screen()` fatal, and a permalink filter that read loop state instead of its own `$post` argument. Also repaired `composer lint`, which failed outright with no path argument. Two of these now have regression tests, added in #131.
- [#124](https://github.com/ucsc/ucsc-giving-functionality/pull/124) — the plugin header version updater matched a single digit per segment, so it would have silently corrupted the `Version:` header at 0.5.10 and could not parse the `v*.*.*-rc.*` tags `release.yml` already triggers on.

---

## 1. Fund type has one source of truth: the taxonomy

**Resolved.** Issue #3 described the symptom — a Fund had to be saved twice for a type change to stick — but the cause was structural: *fund type was stored in two places at once.*

- The `fund-type` taxonomy held the term. It was registered with `show_in_rest = 1` but `show_ui = 0`, so the Block Editor loaded the post's term IDs into its entity state and wrote them back through `handle_terms()` on every save — while giving the editor no panel that would ever update that state. It round-tripped a stale value.
- The ACF field `fund-type-term` (`field_67cc7f695ce26`) was a taxonomy field with `save_terms = 1` and `load_terms = 1`, so ACF **also** read and wrote that same term. That was the control the editor actually showed.

Two systems owned the same value and wrote it on different passes of the save cycle, which is what produced the double-save.

### The decision

**The taxonomy is the single source of truth.** The ACF field is deleted and `show_ui = 1` on the taxonomy, so fund type is edited through WordPress's own panel — the one whose state the editor already round-trips. `handle_terms()` is the only writer left, and there is only one store.

The alternative — keeping the ACF field and setting `show_in_rest = 0` so the editor stops round-tripping — was implemented on a parallel branch and compared in the editor. It was not taken: it preserves the curated radio but leaves two stores in place, so it manages the ambiguity rather than removing it.

No data migration was needed. `load_terms = 1` meant `get_field( 'fund-type-term' )` already resolved through the taxonomy, so the taxonomy was the de facto store before this change as well as after it. The `fund-type-term` meta rows are left behind on existing Funds; nothing reads them and they can be cleaned up at leisure.

### What changed in code

`ucscgiving_link_filter()` reads `has_term( 'Standard', 'fund-type', $post->ID )` instead of `get_field()` + `get_term()`. That removed the `WP_Error` and `instanceof WP_Term` guards along with their tests, and with them the last need for the `WP_Term` and `WP_Error` doubles in `tests/doubles/` — the harness got smaller.

### Keeping the choice either/or

A fund is Standard *or* Priority, never both. The ACF radio enforced that structurally; WordPress renders a hierarchical taxonomy as a **checkbox list**, which does not. Two things close that gap.

**In the editor**, `lib/js/fund-type-radio.js` swaps the checkbox list for a `RadioControl` through the `editor.PostTaxonomyType` filter. That filter is the only supported route: core registers every taxonomy meta box with `__back_compat_meta_box => true` **unconditionally**, so a custom `meta_box_cb` is discarded by the block editor and would only ever appear in the classic editor. This is the plugin's **first browser JavaScript** — hand-written against the `wp.*` globals, no build step, no bundler, consistent with the non-goal below. Translatable strings are passed in from PHP via `wp_localize_script()` so the `ucscgiving` text domain stays where PHPCS enforces it.

**Everywhere else**, `ucscgiving_enforce_single_fund_type()` on `set_object_terms` trims fund-type to one term. Quick Edit and Bulk Edit still render checkboxes, and REST clients, WP-CLI and imports never touch the admin at all. `set_object_terms` is the hook rather than `save_post` because `WP_REST_Posts_Controller::update_item()` runs `wp_update_post()` — and therefore `save_post` — *before* `handle_terms()` writes the terms; it would inspect the terms before they existed. This is a normaliser over the single store, not a third writer arbitrating between two.

Also new to the admin, both by-products of `show_ui`: a **Fund Types** submenu for term management, and a fund-type control in Quick Edit.

`tests/php/FundTypeSourceOfTruthTest.php` asserts that no ACF field in the Fund Details group targets the `fund-type` taxonomy, since `acf-json/` is round-tripped through the ACF admin and adding one back would reinstate the second writer silently.

### Still hand-verified

Two things, both beyond a WordPress-stubbing suite:

- **The save cycle.** Nothing automated exercises `handle_terms()` — changing a Fund's type and saving once has to be checked against an install with ACF Pro and the `ucsc-2022` theme active. See §3.
- **The radio control.** There is no JavaScript test harness and none is proposed. `SingleFundTypeTest` covers the PHP normaliser, which is the half that guarantees the data; the editor affordance is checked by hand.

## 2. Reduce coupling to the `ucsc-2022` theme

The plugin is not currently installable on its own. Two hard dependencies on the theme, neither declared anywhere a site admin would see:

- The `base_url` ACF field lives on the **`ucsc-theme-options` options page registered by the theme**, not by this plugin. Without the theme active, `get_field( 'base_url', 'option' )` returns nothing and every external fund URL silently collapses to an empty or partial link.
- Block templates reference the theme's template parts by name (`header`, `breadcrumbs`, `footer`, `"theme":"ucsc-2022"`).

Options range from cheap to thorough:

- **Cheap:** detect the missing options page and surface an admin notice rather than failing silently.
- **Middle:** move `base_url` into this plugin's own settings page — which already exists but is display-only — so the plugin owns the value it depends on.
- **Thorough:** make template-part references resilient so the templates degrade rather than break under a different theme.

The middle option also gives the existing settings page a reason to exist beyond displaying the plugin description.

## 3. Test harness

**Largely done.** [#127](https://github.com/ucsc/ucsc-giving-functionality/pull/130) and [#128](https://github.com/ucsc/ucsc-giving-functionality/pull/131) landed on 2026-08-04. What exists now:

- **PR checks.** `.github/workflows/ci.yml` runs `composer lint` and `composer test` on every pull request, across PHP 8.1 and 8.4. Previously `release.yml` only fired on tags, so nothing validated a PR at all.
- **A PHPUnit suite** — 43 tests over `ucscgiving_link_filter()` (every branch), `ucscgiving_build_fund_url()`, `ucscgiving_fund_url()`, `ucscgiving_fund_search_template()`, `ucscgiving_create_fund_search_variation()`, `ucscgiving_enforce_single_fund_type()`, the two ACF JSON points, and the fund-type source-of-truth invariants from §1. Nothing covers `lib/js/fund-type-radio.js`; see §1.

### Why the tests stub WordPress rather than run inside it

This is the constraint the first version of this section missed, and it drove the whole shape of the harness.

`ucscgiving_fund_url()` reads `get_field( 'base_url', 'option' )` — an ACF **Pro** options page, registered by the `ucsc-2022` theme rather than by this plugin. Testing it against a real WordPress install therefore needs WordPress **plus** ACF Pro **plus** the theme. ACF Pro is commercial and not on WP.org, so it cannot be installed in GitHub Actions without a licence key held as a repository secret.

`tests/bootstrap.php` supplies WordPress stand-ins instead, so the suite runs on bare PHP and Composer — no WordPress, no database, no licence, no theme, and no bearing on how anyone runs WordPress locally. It follows the dual-mode bootstrap in [`ucsc/ucsc-blocks`](https://github.com/ucsc/ucsc-blocks): set `WP_TESTS_DIR` and it uses a real WordPress test suite instead.

### No `.wp-env.json`

An earlier version of this section recommended committing one. It should not be. [`ucsc/wp-dev.ucsc`](https://github.com/ucsc/wp-dev.ucsc) — a Docker WordPress environment with HTTPS and LDAP — is already the org's shared local environment, and team members use other setups besides. A committed `.wp-env.json` would be a second, competing definition of something already solved, and the unit suite needs no WordPress anyway. Local environment choice stays a per-developer matter.

### What is still hand-verified

The suite covers functions that are pure once WordPress is stubbed. Anything needing a running WordPress still has to be checked by hand against an install with ACF Pro and the `ucsc-2022` theme active:

- block template registration and the markup under `lib/templates/`,
- the ACF JSON save/load round-trip,
- the fund-type save cycle in §1.

### Org-wide context

`ucsc-blocks` has had a PHPUnit harness for some time that **no workflow ever runs**, and no sibling repo (`ucsc-communications-functionality`, `ucsc-events-functionality`, `ucsc-news-functionality`, `ucsc-www-functionality`) has a PR check at all. `ci.yml` here is deliberately repo-local for now, and is a good candidate to promote into `ucsc/actions` — which already hosts the shared release workflow — once it has run for a release cycle.

## 4. Harden packaging and templates

- ~~**Relative `require` in block templates.**~~ Done in [#134](https://github.com/ucsc/ucsc-giving-functionality/issues/134) — both calls are now `__DIR__`-rooted, and the unused `parts/post-query.php` was removed with them.
- **Template composition is split two ways.** *Needs a look before anything is decided.*

  | Template | Lines | Composition |
  |---|---|---|
  | `taxonomy-fund-type.php` | 11 | `require`s `parts/funds-search.php` + `parts/post-query-funds.php` |
  | `taxonomy-fund-theme.php` | 11 | same |
  | `taxonomy-area.php` | 74 | fully inlined |
  | `taxonomy-keyword.php` | 74 | fully inlined |
  | `archive-fund.php` | 74 | fully inlined |
  | `single-fund.php` | 30 | fully inlined |

  The two inlined taxonomy templates are near-identical to each other, so there is real duplication: a change to the archive layout currently has to be made in both, and neither picks up edits to `parts/`. Converting them to the parts pattern would remove that, but it changes rendered markup and needs visual QA, so it is not a mechanical cleanup.

  Worth noting the two are entangled: the inlined templates are also the ones carrying the hardcoded production media ([#133](https://github.com/ucsc/ucsc-giving-functionality/issues/133)) and the unpinned template-part references ([#136](https://github.com/ucsc/ucsc-giving-functionality/issues/136)). Whoever picks up the composition question should look at all three together rather than in isolation.

- ~~**Single URL-construction path.**~~ Done in [#135](https://github.com/ucsc/ucsc-giving-functionality/issues/135) — both callers now compose the URL through `ucscgiving_build_fund_url()`, with a test asserting they agree. The extraction surfaced that the two had *already* drifted: the binding dropped a designation code of `"0"` where the permalink filter kept it. Unified on the filter's behavior.

## 5. Maintenance

- **Dependabot alerts are all `development` scope.** Zero production-scope, and the shipped ZIP contains only `acf-json/`, `lib/`, `plugin.php` and docs — no `node_modules`. **Nothing here reaches a WordPress site.** The whole backlog is the `@wordpress/scripts` transitive tree, which exists solely to provide `plugin-zip`; the one JavaScript file the plugin ships is hand-written and has no build step. Housekeeping, not a security matter — and worth remembering before a raw alert count is read as risk.

  [#137](https://github.com/ucsc/ucsc-giving-functionality/issues/137) bumped `@wordpress/scripts` 30 → 34 and applied the non-breaking fixes, taking `npm audit` from 63 to 29 and clearing every critical. What remains is held behind `semver-major` bumps inside that tree — `adm-zip`, `markdown-it`/`linkify-it`, `minimatch`, and `webpack-dev-server` via `sockjs` — so it cannot be cleared without either forcing breaking upgrades or waiting for upstream. Not worth forcing, given zero production exposure.

  The durable fix is to stop depending on that tree at all: `plugin-zip` is the only thing used from it. A smaller dependency, or a short zip script, would remove the entire alert surface permanently. Worth considering the next time this backlog becomes annoying.
- **Keep `.github/copilot-instructions.md` in sync with `CLAUDE.md`.** The two cover much of the same ground for different agents, and the copilot file drifts more easily because nothing reads it during normal work. It documented `standard-version` for four months after a19905c replaced it with `commit-and-tag-version` on 2026-03-18; corrected in [#138](https://github.com/ucsc/ucsc-giving-functionality/issues/138). Worth a glance whenever a convention, command or dependency changes.
- **The daily-repo-status workflow has been removed**, and with it the bulk of the closed-issue history it generated (#94–#118). `.github/workflows/` now holds only `ci.yml` and `release.yml`. The `daily-status` and `report` labels are kept deliberately — 88 closed issues carry each, and deleting a label strips it from every issue, which would make that history unfilterable for no benefit.

---

## Non-goals

- **Integration tests against a real WordPress install.** Deliberate, not an oversight — see §3. The fund URL path depends on an ACF Pro options page registered by the `ucsc-2022` theme, so automating it in CI would mean holding a commercial licence key as a repository secret. The unit suite covers the logic; the rest is hand-verified.
- Rewriting the plugin as class-based / namespaced. The procedural structure is small and consistent; churn here would buy little.
- Registering the post type and taxonomies in PHP. `acf-json/` is the working source of truth and the ACF admin round-trips to it.
- Shipping a build step for front-end assets. The plugin ships no front-end JavaScript at all, and its one admin script (`lib/js/fund-type-radio.js`, §1) is written against the `wp.*` globals precisely so it needs no bundler. `@wordpress/scripts` is used only for `plugin-zip`.
- A JavaScript test suite. One hand-written admin file does not justify the toolchain; the invariant it expresses is covered in PHP by `SingleFundTypeTest`, which is the half that actually protects the data.
