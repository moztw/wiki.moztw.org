<?php

/**
 * Make an HTML table showing all the wikis on the site
 */

# Not a valid entry point, skip unless MEDIAWIKI is defined
if ( !defined( 'MEDIAWIKI' ) ) {
	echo "This file is part of MediaWiki, it is not a valid entry point.\n";
	exit( 1 );
}

$wgExtensionCredits['specialpage'][] = array(
	'path'           => __FILE__,
	'name'           => 'SiteMatrix',
	'author'         => array( 'Tim Starling', 'Brion Vibber', 'Victor Vasiliev', 'Alexandre Emsenhuber' ),
	'version'        => '1.3.0',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:SiteMatrix',
	'descriptionmsg' => 'sitematrix-desc',
);

$wgSiteMatrixFile = null;
$wgSiteMatrixSites = array(
	'wiki' => array(
		'name' => 'Wikipedia',
		'host' => 'www.wikipedia.org',
		'prefix' => 'w',
	),
	'wiktionary' => array(
		'name' => 'Wiktionary',
		'host' => 'www.wiktionary.org',
		'prefix' => 'wikt',
	),
	'wikibooks' => array(
		'name' => 'Wikibooks',
		'host' => 'www.wikibooks.org',
		'prefix' => 'b',
	),
	'wikinews' => array(
		'name' => 'Wikinews',
		'host' => 'www.wikinews.org',
		'prefix' => 'n',
	),
	'wikiquote' => array(
		'name' => 'Wikiquote',
		'host' => 'www.wikiquote.org',
		'prefix' => 'q',
	),
	'wikisource' => array(
		'name' => 'Wikisource',
		'host' => 'www.wikisource.org',
		'prefix' => 's',
	),
	'wikiversity' => array(
		'name' => 'Wikiversity',
		'host' => 'www.wikiversity.org',
		'prefix' => 'v',
	),
	'wikivoyage' => array(
		'name' => 'Wikivoyage',
		'host' => 'www.wikivoyage.org',
		'prefix' => 'voy',
	)
);
$wgSiteMatrixPrivateSites = null;
$wgSiteMatrixFishbowlSites = null;
$wgSiteMatrixClosedSites = null;

$dir = __DIR__ . '/';

$wgMessagesDirs['SiteMatrix'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['SiteMatrix'] = $dir . 'SiteMatrix.i18n.php';
$wgExtensionMessagesFiles['SiteMatrixAlias'] = $dir . 'SiteMatrix.alias.php';

$wgAutoloadClasses['SiteMatrix'] = $dir . 'SiteMatrix_body.php';

$wgAutoloadClasses['SpecialSiteMatrix'] = $dir . 'SpecialSiteMatrix.php';
$wgSpecialPages['SiteMatrix'] = 'SpecialSiteMatrix';
$wgSpecialPageGroups['SiteMatrix'] = 'wiki';

$wgAutoloadClasses['ApiSiteMatrix'] = $dir . 'SiteMatrixApi.php';
$wgAPIModules['sitematrix'] = 'ApiSiteMatrix';

$wgHooks['APIQuerySiteInfoGeneralInfo'][] = 'SiteMatrix::APIQuerySiteInfoGeneralInfo';
