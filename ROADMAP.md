# Roadmap

Engineering roadmap for the UCSC Giving Functionality plugin, derived from the state of the repository as of **2026-08-04** (v0.5.8).

This is a technical roadmap, not a product plan. Items are grounded in open issues, in-flight pull requests, and observed characteristics of the codebase — it does not attempt to set feature direction, which is the University Advancement web team's call. Sequencing below reflects dependency order and risk, not committed dates.

---

## In flight

| Item | Tracking | State |
|---|---|---|
| Dual source of truth for fund type | [#3](https://github.com/ucsc/ucsc-giving-functionality/issues/3) | Open, **needs a decision** — see [§1](#1-resolve-the-dual-source-of-truth-for-fund-type) |

### Needs a decision

**#3 needs an owner to pick a data model.** The issue has been rewritten around the root cause rather than the double-save symptom; §1 below is the decision it is waiting on. PR [#21](https://github.com/ucsc/ucsc-giving-functionality/pull/21) — a Copilot draft that added a third writer on `rest_after_insert_fund` to correct the other two — was **closed unmerged** on 2026-08-04. It had gone stale and conflicted with [#121](https://github.com/ucsc/ucsc-giving-functionality/pull/121), which rewrote `ucscgiving_link_filter()` in the same file, and it treated the symptom rather than the dual ownership.

### Recently landed

- [#131](https://github.com/ucsc/ucsc-giving-functionality/pull/131) ([#128](https://github.com/ucsc/ucsc-giving-functionality/issues/128)) — the PHPUnit harness described in §3. First automated behavioral coverage in the repo.
- [#130](https://github.com/ucsc/ucsc-giving-functionality/pull/130) ([#127](https://github.com/ucsc/ucsc-giving-functionality/issues/127)) — `ci.yml`, so pull requests are validated at all. Also declared the PHP floor (8.1), which had been recorded in neither `composer.json` nor the plugin header, and aligned the WPCS target version with `Requires at least`.
- [#125](https://github.com/ucsc/ucsc-giving-functionality/pull/125) ([#122](https://github.com/ucsc/ucsc-giving-functionality/issues/122)) — `CLAUDE.md` and this roadmap.
- [#121](https://github.com/ucsc/ucsc-giving-functionality/pull/121) — seven correctness and hygiene fixes: a `search_template` filter that had never applied, an unguarded `get_current_screen()` fatal, and a permalink filter that read loop state instead of its own `$post` argument. Also repaired `composer lint`, which failed outright with no path argument. Two of these now have regression tests, added in #131.
- [#124](https://github.com/ucsc/ucsc-giving-functionality/pull/124) — the plugin header version updater matched a single digit per segment, so it would have silently corrupted the `Version:` header at 0.5.10 and could not parse the `v*.*.*-rc.*` tags `release.yml` already triggers on.

---

## 1. Resolve the dual source of truth for fund type

**The highest-value architectural item.** Issue #3 describes the symptom — a Fund must be saved twice for a type change to stick — but the cause is structural: *fund type is stored in two places at once.*

- The `fund-type` taxonomy holds the term. It is registered with `show_in_rest = 1` but `show_ui = 0`, so the Block Editor loads the post's term IDs into its entity state and writes them back through `handle_terms()` on every save — while giving the editor no panel that would ever update that state. It round-trips a stale value.
- The ACF field `fund-type-term` (`field_67cc7f695ce26`) is a taxonomy field with `save_terms = 1` and `load_terms = 1`, so ACF **also** reads and writes that same term. This is the control the editor actually sees.

Two systems own the same value and write it on different passes of the save cycle, which is what produces the double-save.

The read path is entangled too. `load_terms = 1` means `get_field( 'fund-type-term' )` in `ucscgiving_link_filter()` resolves through the **taxonomy**, not through the `fund-type-term` meta row — so the two stores can diverge silently, and fund linking follows whichever writer won the last save.

Worth deciding explicitly:

- **Taxonomy as the single source of truth** — drop `save_terms`/`load_terms`, or drop the ACF field entirely and set `show_ui = 1` to edit the term through the standard taxonomy panel. Simplest model, and it matches what the read path already effectively does; requires a migration path for existing posts and a rewrite of the `get_field()` reads.
- **ACF field as the single source of truth** — keep the field and set `show_in_rest = 0` on the taxonomy so the editor stops round-tripping stale term state, leaving ACF's `save_terms` as the only writer. Keeps the curated editor UX, but leaves two stores in place.

The first is the better default unless the sidebar radio UX is non-negotiable. Either way this should be settled before more logic is layered on top of fund type.

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
- **A PHPUnit suite** — 24 tests over `ucscgiving_link_filter()` (all six branches), `ucscgiving_fund_url()`, `ucscgiving_fund_search_template()`, `ucscgiving_create_fund_search_variation()` and the two ACF JSON points.

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

- **Relative `require` in block templates.** `lib/templates/*.php` use `require 'parts/…'`, which resolves only because PHP falls back to the calling file's directory. Change to `__DIR__`-relative paths. Low effort, removes a latent break.
- **Single URL-construction path.** `ucscgiving_fund_url()` (block binding) and `ucscgiving_link_filter()` (permalink) build the same URL independently. Extract one helper so they cannot diverge. Both now have characterization tests (§3), so the extraction can be made and shown to preserve behavior — this is the cheapest remaining item.

## 5. Maintenance

- **Dependabot: 104 open alerts, all `development` scope.** Zero production-scope alerts, and the shipped ZIP contains only `acf-json/`, `lib/`, `plugin.php`, and docs — no `node_modules`. **Nothing here reaches a WordPress site.** The cluster is the `@wordpress/scripts` transitive tree (axios, webpack-dev-server, node-forge, handlebars). Bumping `@wordpress/scripts` should clear most of it. Real, but a housekeeping item rather than a security incident.
- **`.github/copilot-instructions.md` has drifted** — it still documents `standard-version`, replaced by `commit-and-tag-version` in a19905c. Keep it in sync with `CLAUDE.md` when conventions change.
- **Automated daily-repo-status issues** accounted for most of the closed-issue history (#94–#118). If that workflow is still active, consider whether the signal justifies the noise in the issue tracker.

---

## Non-goals

- **Integration tests against a real WordPress install.** Deliberate, not an oversight — see §3. The fund URL path depends on an ACF Pro options page registered by the `ucsc-2022` theme, so automating it in CI would mean holding a commercial licence key as a repository secret. The unit suite covers the logic; the rest is hand-verified.
- Rewriting the plugin as class-based / namespaced. The procedural structure is small and consistent; churn here would buy little.
- Registering the post type and taxonomies in PHP. `acf-json/` is the working source of truth and the ACF admin round-trips to it.
- Shipping a build step for front-end assets. The plugin ships no JavaScript, and `@wordpress/scripts` is used only for `plugin-zip`.
