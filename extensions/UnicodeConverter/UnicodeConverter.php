<?php

# This is a simple example of a special page module
# Given a string in UTF-8, it converts it to HTML entities suitable for
# an ISO 8859-1 web page.

# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI')) {
	echo "Unicode Converter extension";
	exit( 1 );
}

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'UnicodeConverter',
	'author' => 'Tim Starling',
	'url' => 'https://www.mediawiki.org/wiki/Extension:UnicodeConverter',
	'descriptionmsg' => 'unicodeconverter-desc',
);

$dir = __DIR__ . '/';
$wgMessagesDirs['UnicodeConverter'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['UnicodeConverter'] = $dir . 'UnicodeConverter.i18n.php';
$wgExtensionMessagesFiles['UnicodeConverterAlias'] = $dir . 'UnicodeConverter.alias.php';
$wgAutoloadClasses['SpecialUnicodeConverter'] = $dir . 'UnicodeConverter_body.php';
$wgSpecialPages['UnicodeConverter'] = 'SpecialUnicodeConverter';
