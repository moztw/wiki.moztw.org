<?php
/**
 * Mozilla Taiwan Skin for mediawiki 1.25+
 * @file
 * @ingroup Skins
 */

/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @ingroup Skins
 */
class SkinMozTW extends SkinTemplate {
	public $skinname = 'moztw';
	public $stylename = 'MozTW';
	public $template = 'MozTWTemplate';

	/**
	 * @param OutputPage $out
	 */
	function setupSkinUserCss( OutputPage $out ) {
		parent::setupSkinUserCss( $out );

		$out->addModuleStyles( array(
			'mediawiki.skinning.interface',
			'mediawiki.skinning.content.externallinks',
			'skins.moztw.styles'
		) );
	}
}
