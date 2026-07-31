# Roadmap

Engineering roadmap for the UCSC Giving Functionality plugin, derived from the state of the repository as of **2026-07-31** (v0.5.8).

This is a technical roadmap, not a product plan. Items are grounded in open issues, in-flight pull requests, and observed characteristics of the codebase — it does not attempt to set feature direction, which is the University Advancement web team's call. Sequencing below reflects dependency order and risk, not committed dates.

---

## In flight

| Item | Tracking | State |
|---|---|---|
| Double-save on Fund type change | [#3](https://github.com/ucsc/ucsc-giving-functionality/issues/3) | PR [#21](https://github.com/ucsc/ucsc-giving-functionality/pull/21) draft, **conflicting** |
| `CLAUDE.md` / `ROADMAP.md` project context | [#122](https://github.com/ucsc/ucsc-giving-functionality/issues/122) | This change |

### Needs a decision

**PR #21 needs a rebase, or replacing.** It is a draft last touched 2026-03-18 and now conflicts with `main`: it edits `lib/functions/general.php`, and [#121](https://github.com/ucsc/ucsc-giving-functionality/pull/121) rewrote `ucscgiving_link_filter()` in that same file. Decide whether to rebase it or supersede it — see the next section, because the underlying problem is likely better solved at the data-model level than in the editor.

### Recently landed

- [#121](https://github.com/ucsc/ucsc-giving-functionality/pull/121) — seven correctness and hygiene fixes: a `search_template` filter that had never applied, an unguarded `get_current_screen()` fatal, and a permalink filter that read loop state instead of its own `$post` argument. Also repaired `composer lint`, which failed outright with no path argument.
- [#124](https://github.com/ucsc/ucsc-giving-functionality/pull/124) — the plugin header version updater matched a single digit per segment, so it would have silently corrupted the `Version:` header at 0.5.10 and could not parse the `v*.*.*-rc.*` tags `release.yml` already triggers on.

---

## 1. Resolve the dual source of truth for fund type

**The highest-value architectural item.** Issue #3 describes the symptom — a Fund must be saved twice for a type change to stick — but the cause is structural: *fund type is stored in two places at once.*

- The `fund-type` taxonomy holds the term.
- The ACF field `fund-type-term` (`field_67cc7f695ce26`) is a taxonomy field with `save_terms = 1` and `load_terms = 1`, so ACF **also** reads and writes that same term.

Two systems own the same value and write it on different passes of the save cycle, which is what produces the double-save. Downstream code inherits the ambiguity: `ucscgiving_link_filter()` reads the ACF field and resolves it with `get_term()`, so the linking behavior depends on which writer won.

Worth deciding explicitly:

- **Taxonomy as the single source of truth** — drop `save_terms`/`load_terms`, or drop the ACF field entirely and edit the term through the standard taxonomy UI. Simplest model; requires a migration path for existing posts and a rewrite of the `get_field()` reads.
- **ACF field as the single source of truth** — keep the field, make the taxonomy purely derived. Keeps the curated editor UX.

Either way this should be settled before more logic is layered on top of fund type, and it likely subsumes PR #21.

## 2. Reduce coupling to the `ucsc-2022` theme

The plugin is not currently installable on its own. Two hard dependencies on the theme, neither declared anywhere a site admin would see:

- The `base_url` ACF field lives on the **`ucsc-theme-options` options page registered by the theme**, not by this plugin. Without the theme active, `get_field( 'base_url', 'option' )` returns nothing and every external fund URL silently collapses to an empty or partial link.
- Block templates reference the theme's template parts by name (`header`, `breadcrumbs`, `footer`, `"theme":"ucsc-2022"`).

Options range from cheap to thorough:

- **Cheap:** detect the missing options page and surface an admin notice rather than failing silently.
- **Middle:** move `base_url` into this plugin's own settings page — which already exists but is display-only — so the plugin owns the value it depends on.
- **Thorough:** make template-part references resilient so the templates degrade rather than break under a different theme.

The middle option also gives the existing settings page a reason to exist beyond displaying the plugin description.

## 3. Establish a test harness

There is currently **no automated test coverage of any kind** — no PHPUnit, no `.wp-env.json`, no JS tests. `composer lint` is the only mechanical gate, and it checks style, not behavior.

This is what makes items 1 and 2 risky: the fund-linking logic has several branches (fund type, empty `base_url`, missing designation, non-`fund` post types, in-loop vs out-of-loop) that today can only be verified by hand.

Suggested order:

1. Add a `.wp-env.json` so contributors get a reproducible environment from the repo instead of configuring one externally.
2. Add PHPUnit with a small suite around `ucscgiving_link_filter()` and `ucscgiving_fund_url()` — the two functions that independently compute the same URL and are the most likely to drift.
3. Wire lint and tests into CI on pull requests. Today `.github/workflows/release.yml` only runs on tags, so **nothing validates a PR automatically.**

## 4. Harden packaging and templates

- **Relative `require` in block templates.** `lib/templates/*.php` use `require 'parts/…'`, which resolves only because PHP falls back to the calling file's directory. Change to `__DIR__`-relative paths. Low effort, removes a latent break.
- **Single URL-construction path.** `ucscgiving_fund_url()` (block binding) and `ucscgiving_link_filter()` (permalink) build the same URL independently. Extract one helper so they cannot diverge.

## 5. Maintenance

- **Dependabot: 104 open alerts, all `development` scope.** Zero production-scope alerts, and the shipped ZIP contains only `acf-json/`, `lib/`, `plugin.php`, and docs — no `node_modules`. **Nothing here reaches a WordPress site.** The cluster is the `@wordpress/scripts` transitive tree (axios, webpack-dev-server, node-forge, handlebars). Bumping `@wordpress/scripts` should clear most of it. Real, but a housekeeping item rather than a security incident.
- **`.github/copilot-instructions.md` has drifted** — it still documents `standard-version`, replaced by `commit-and-tag-version` in a19905c. Keep it in sync with `CLAUDE.md` when conventions change.
- **Automated daily-repo-status issues** accounted for most of the closed-issue history (#94–#118). If that workflow is still active, consider whether the signal justifies the noise in the issue tracker.

---

## Non-goals

- Rewriting the plugin as class-based / namespaced. The procedural structure is small and consistent; churn here would buy little.
- Registering the post type and taxonomies in PHP. `acf-json/` is the working source of truth and the ACF admin round-trips to it.
- Shipping a build step for front-end assets. The plugin ships no JavaScript, and `@wordpress/scripts` is used only for `plugin-zip`.
