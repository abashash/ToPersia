/**
 * محیط محلی از یک فایل — P5-D285 (فاز ۰ب٫۱). همان tests/env.json که env.php می‌خواند.
 *
 *   const ENV = require( './env.js' );   // ENV.base · ENV.wp · ENV.root · ENV.php · ENV.mysql · ENV.dbPort · ENV.workspace
 *
 * متغیر محیطی `TP_ENV` فایل دیگری را جایگزین می‌کند.
 */
const fs   = require( 'fs' );
const path = require( 'path' );

const file = process.env.TP_ENV || path.join( __dirname, 'env.json' );
const raw  = JSON.parse( fs.readFileSync( file, 'utf8' ) );
const trim = s => String( s || '' ).replace( /[\\/]+$/, '' );

module.exports = {
	root:      trim( raw.root ),
	wp:        trim( raw.wp ),
	base:      trim( raw.base ),
	host:      new URL( raw.base ).hostname,
	workspace: trim( raw.workspace ),
	php:       process.env.TP_PHP || raw.php,
	mysql:     process.env.TP_MYSQL || raw.mysql,
	dbPort:    String( process.env.TP_DB_PORT || raw.db_port || 3306 ),   /* ۰ب٫۵ */
	wpCli:     raw.wp_cli,
};
