<?php

if ( function_exists( 'wfLoadSkin' ) ) {
  wfLoadSkin( 'MozTW' );
  $wgAutoloadClasses['SkinMozTW'] = __DIR__ . '/SkinMozTW.php';
  $wgAutoloadClasses['MozTWTemplate'] = __DIR__ . '/MozTWTemplate.php';
  return true;
} else {
  die( 'This version of the MozTW skin requires MediaWiki 1.25+' );
}
