<?php


class SpecialSiteMatrix extends SpecialPage {

	function __construct() {
		parent::__construct( 'SiteMatrix' );
	}

	/**
	 * @return array
	 */
	public static function getLocalLanguageNames() {
		if ( class_exists( 'LanguageNames' ) ) {
			global $wgLang;
			return LanguageNames::getNames( $wgLang->getCode() );
		}
		return array();
	}

	function execute( $par ) {
		$langNames = Language::fetchLanguageNames();

		$this->setHeaders();
		$this->outputHeader();

		$matrix = new SiteMatrix();

		$localLanguageNames = self::getLocalLanguageNames();

		# Construct the HTML

		# Header row
		$s = Xml::openElement( 'table', array( 'class' => 'wikitable', 'id' => 'mw-sitematrix-table' ) ) .
			"<tr>" .
			Xml::element( 'th',
				array( 'rowspan' => 2 ),
				$this->msg( 'sitematrix-language' )->text() ) .
			Xml::element( 'th',
				array( 'colspan' => count( $matrix->getSites() ) ),
				$this->msg( 'sitematrix-project' )->text() ) .
			"</tr>
			<tr>";
		foreach ( $matrix->getNames() as $id => $name ) {
			$url = $matrix->getSiteUrl( $id );
			$s .= Xml::tags( 'th', null, "<a href=\"{$url}\">{$name}</a>" );
		}
		$s .= "</tr>\n";

		# Bulk of table
		foreach ( $matrix->getLangList() as $lang ) {
			if ( in_array( $lang, array( 'cz', 'dk', 'epo', 'jp', 'minnan', 'nan', 'nb', 'zh-cfr' ) ) ) {
				continue;
			}

			$anchor = strtolower( '<a id="' . htmlspecialchars( $lang ) . '" name="' . htmlspecialchars( $lang ) . '"></a>' );
			$s .= '<tr>';
			$attribs = array();
			if ( isset( $localLanguageNames[$lang] ) ) {
				$attribs['title'] = $localLanguageNames[$lang];
			}

			$langDisplay = ( isset( $langNames[$lang] ) ? Html::rawElement( 'span', array( 'lang' => htmlspecialchars( $lang ) ), $langNames[$lang] ) : '' );
			if ( isset( $localLanguageNames[$lang] ) && strlen( $localLanguageNames[$lang] ) && $langDisplay != $localLanguageNames[$lang] ) {
				$langDisplay .= $this->msg( 'word-separator' )->text() .
						 $this->msg( 'parentheses', $localLanguageNames[$lang] )->text();
			}
			$s .= '<td>' . $anchor . Html::rawElement( 'strong', $attribs, $langDisplay ) . '</td>';

			foreach ( $matrix->getNames() as $site => $name ) {
				$url = $matrix->getUrl( $lang, $site );
				if ( $matrix->exist( $lang, $site ) ) {
					# Wiki exists
					$closed = $matrix->isClosed( $lang, $site );
					$s .= "<td>" . ( $closed ? "<del>" : '' ) . "<a href=\"{$url}\">{$lang}</a>" . ( $closed ? "</del>" : '' ) . '</td>';
				} else {
					# Non-existent wiki
					$s .= "<td><a href=\"{$url}\" class=\"new\">{$lang}</a></td>";
				}
			}
			$s .= "</tr>\n";
		}

		$language = $this->getLanguage();
		# Total
		$totalCount = 0;
		$s .= '<tr><th rowspan="2"><a id="total" name="total"></a>' . $this->msg( 'sitematrix-sitetotal' )->escaped() . '</th>';
		foreach ( $matrix->getNames() as $site => $name ) {
			$url = $matrix->getSiteUrl( $site );
			$count = $matrix->getCountPerSite( $site );
			$totalCount += $count;
			$count = $language->formatNum( $count );
			$s .= "<th><a href=\"{$url}\">{$count}</a></th>";
		}
		$s .= '</tr>';

		$s .= '<tr>';
		$noProjects = count( $matrix->getNames() );
		$totalCount = $language->formatNum( $totalCount );
		$s .= "<th colspan=\"{$noProjects }\">{$totalCount}</th>";
		$s .= '</tr>';

		$s .= Xml::closeElement( 'table' ) . "\n";

		# Specials
		$s .= '<h2 id="mw-sitematrix-others">' . $this->msg( 'sitematrix-others' )->escaped() . '</h2>';

		$s .= Xml::openElement( 'table', array( 'class' => 'wikitable', 'id' => 'mw-sitematrix-other-table' ) ) .
			"<tr>" .
			Xml::element( 'th', null, $this->msg( 'sitematrix-other-projects' )->text() ) .
			"</tr>";

		foreach ( $matrix->getSpecials() as $special ) {
			list( $lang, $site ) = $special;

			// sanity check
			if ( !$lang && !$site ) {
				continue;
			}

			$langhost = str_replace( '_', '-', $lang );
			$url = $matrix->getUrl( $lang, $site );

			# Handle options
			$flags = array();
			if ( $matrix->isPrivate( $lang . $site ) ) {
				$flags[] = $this->msg( 'sitematrix-private' )->escaped();
			}
			if ( $matrix->isFishbowl( $lang . $site ) ) {
				$flags[] = $this->msg( 'sitematrix-fishbowl' )->escaped();
			}
			$flagsStr = implode( ', ', $flags );
			if ( $site != 'wiki' ) {
				$langhost .= $site;
			}
			$closed = $matrix->isClosed( $lang, $site );
			$s .= '<tr><td>' . ( $closed ? '<del>' : '' ) .
				$language->specialList( '<a href="' . $url . '/">' . $langhost . "</a>", $flagsStr ) .
				( $closed ? '</del>' : '' ) . "</td></tr>\n";
		}

		$s .= Xml::closeElement( 'table' ) . "\n";

		$this->getOutput()->addHTML( $s );
		$this->getOutput()->addWikiMsg( 'sitematrix-total', $language->formatNum( $matrix->getCount() ) );
	}
}
