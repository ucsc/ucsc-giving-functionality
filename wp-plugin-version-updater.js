/**
 * commit-and-tag-version updater for the WordPress plugin header.
 *
 * Registered in package.json `bumpFiles` so the `Version:` header in
 * plugin.php stays in step with package.json.
 *
 * Segments are `\d+` rather than a single `[0-9]`: a single-digit pattern
 * matches the `0.5.1` prefix of `0.5.10` and rewrites only that prefix,
 * silently corrupting the header. The optional prerelease suffix supports
 * the `v*.*.*-rc.*` tags that .github/workflows/release.yml triggers on.
 */

const VERSION_PATTERN = /(Version:\s*)(\d+\.\d+\.\d+(?:-[\w.]+)?)/;

module.exports.readVersion = function (contents) {
  const found = contents.match(VERSION_PATTERN);

  if (!found) {
    throw new Error(
      'wp-plugin-version-updater: no "Version:" header found. ' +
        "Expected a line like ' * Version: 1.2.3'."
    );
  }

  return found[2];
};

module.exports.writeVersion = function (contents, version) {
  if (!VERSION_PATTERN.test(contents)) {
    throw new Error(
      'wp-plugin-version-updater: no "Version:" header to update. ' +
        "Refusing to write, as the plugin header would silently drift from package.json."
    );
  }

  return contents.replace(VERSION_PATTERN, `$1${version}`);
};
