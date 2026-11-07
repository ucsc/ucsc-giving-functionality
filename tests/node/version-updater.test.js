/**
 * Tests for the release version updater, run against this plugin's own files.
 *
 * `npm run release` bumps the `Version:` header in plugin.php through
 * @ucsantacruz/wordpress-version-updater (issue #149). That package has its
 * own tests. These tests cover how this repo uses it: the real plugin.php
 * header, the package.json configuration, and the versions that the release
 * workflow triggers on (multi-digit segments and `-rc.N` suffixes).
 *
 * Uses only the Node test runner (`node:test`), so no test toolchain is
 * installed. Run with `npm run test:release`.
 */

const { test } = require( 'node:test' );
const assert = require( 'node:assert/strict' );
const { spawnSync } = require( 'node:child_process' );
const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );

const root = path.resolve( __dirname, '..', '..' );
const pluginFile = path.join( root, 'plugin.php' );
const packageJson = require( path.join( root, 'package.json' ) );
const packageLock = require( path.join( root, 'package-lock.json' ) );

const updaterPath = 'node_modules/@ucsantacruz/wordpress-version-updater/index.js';
const updater = require( path.join( root, updaterPath ) );
const cli = path.join(
	root,
	'node_modules/@ucsantacruz/wordpress-version-updater/cli.js'
);

const header = fs.readFileSync( pluginFile, 'utf8' );

/**
 * Run the `wordpress-version-updater` command.
 *
 * @param {...string} args Command arguments.
 * @return {import('node:child_process').SpawnSyncReturns<string>} The result.
 */
function runCli( ...args ) {
	return spawnSync( process.execPath, [ cli, ...args ], {
		cwd: root,
		encoding: 'utf8',
	} );
}

/**
 * List the lines that differ between two strings.
 *
 * @param {string} before Original contents.
 * @param {string} after  Updated contents.
 * @return {string[]} The changed lines, as they are in `after`.
 */
function changedLines( before, after ) {
	const a = before.split( '\n' );
	const b = after.split( '\n' );
	assert.equal( b.length, a.length, 'The update changed the line count.' );
	return b.filter( ( line, i ) => line !== a[ i ] );
}

test( 'readVersion returns the version in package.json', () => {
	assert.equal( updater.readVersion( header ), packageJson.version );
} );

test( 'plugin.php and package-lock.json agree on the version', () => {
	const version = updater.readVersion( header );
	assert.equal( packageLock.version, version );
	assert.equal( packageLock.packages[ '' ].version, version );
} );

for ( const version of [ '0.5.10', '10.20.300' ] ) {
	test( `writeVersion writes the multi-digit version ${ version }`, () => {
		const updated = updater.writeVersion( header, version );
		assert.equal( updater.readVersion( updated ), version );
		assert.deepEqual( changedLines( header, updated ), [
			` * Version: ${ version }`,
		] );
	} );
}

test( 'writeVersion writes an -rc.N prerelease version', () => {
	const updated = updater.writeVersion( header, '0.6.0-rc.1' );
	assert.equal( updater.readVersion( updated ), '0.6.0-rc.1' );
	assert.deepEqual( changedLines( header, updated ), [
		' * Version: 0.6.0-rc.1',
	] );
} );

test( 'writeVersion replaces the whole prerelease suffix', () => {
	const rc = updater.writeVersion( header, '0.6.0-rc.12' );
	const release = updater.writeVersion( rc, '0.6.0' );
	assert.equal( updater.readVersion( release ), '0.6.0' );
	assert.match( release, /^ \* Version: 0\.6\.0$/m );
} );

test( 'the check command accepts plugin.php and writes nothing to stdout', () => {
	const result = runCli( 'check', 'plugin.php' );
	assert.equal( result.status, 0, result.stderr );
	assert.equal( result.stdout, '' );
} );

test( 'the check command fails on a file with no Version header', ( t ) => {
	const dir = fs.mkdtempSync( path.join( os.tmpdir(), 'ucscgiving-' ) );
	t.after( () => fs.rmSync( dir, { recursive: true, force: true } ) );

	const file = path.join( dir, 'plugin.php' );
	fs.writeFileSync(
		file,
		header.replace( /^ \* Version:.*\n/m, '' )
	);

	const result = runCli( 'check', file );
	assert.equal( result.status, 1 );
	assert.match( result.stderr, /No "Version:" line/ );
} );

test( 'package.json sends plugin.php through the updater package', () => {
	const config = packageJson[ 'commit-and-tag-version' ];

	const entry = config.bumpFiles.find( ( f ) => f.filename === 'plugin.php' );
	assert.ok( entry, 'plugin.php is missing from bumpFiles.' );
	assert.equal( entry.updater, updaterPath );
	assert.ok( fs.existsSync( path.join( root, entry.updater ) ) );

	assert.equal(
		config.scripts.prerelease,
		'wordpress-version-updater check plugin.php'
	);
} );
