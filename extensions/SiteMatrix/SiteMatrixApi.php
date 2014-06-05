<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo "SiteMatrix extension\n";
	exit( 1 );
}

/**
 * Query module to get site matrix
 * @ingroup API
 */
class ApiQuerySiteMatrix extends ApiQueryBase {

	public function __construct( $query, $moduleName ) {
		parent :: __construct( $query, $moduleName, 'sm' );
	}

	public function execute() {
		$result = $this->getResult();
		$matrix = new SiteMatrix();
		$langNames = Language::fetchLanguageNames();

		$matrix_out = array(
			'count' => $matrix->getCount(),
		);

		$localLanguageNames = SpecialSiteMatrix::getLocalLanguageNames();

		$params = $this->extractRequestParams();
		$type = array_flip( $params['type'] );
		$state = array_flip( $params['state'] );
		$langProp = array_flip( $params['langprop'] );
		$siteProp = array_flip( $params['siteprop'] );
		$limit = $params['limit'];
		$continue = isset( $params['continue'] )
			? explode( '|', $params['continue'] )
			: array( 'language', '' );
		if ( count( $continue ) != 2 ) {
			$this->dieUsage( 'Invalid continue param. You should pass the original value returned by the previous query', 'badcontinue' );
		}

		$all = isset( $state['all'] );
		$closed = isset( $state['closed'] );
		$private = isset( $state['private'] );
		$fishbowl = isset( $state['fishbowl'] );

		$count = 0;
		if ( isset( $type['language'] ) && $continue[0] == 'language' ) {
			foreach ( $matrix->getLangList() as $lang ) {
				$langhost = str_replace( '_', '-', $lang );
				if ( $langhost < $continue[1] ) {
					continue;
				}
				if ( $count >= $limit ) {
					$this->setContinueEnumParameter( 'continue', "language|$langhost" );
					break;
				}
				$language = array(
					'code' => $langhost,
					'name' => $langNames[$lang],
					'site' => array(),
				);
				if ( isset( $localLanguageNames[$lang] ) ) {
					$language['localname'] = $localLanguageNames[$lang];
				}
				$language = array_intersect_key( $language, $langProp );

				if ( isset( $language['site'] ) ) {
					foreach ( $matrix->getSites() as $site ) {
						if ( $matrix->exist( $lang, $site ) ) {
							$skip = true;

							if ( $all ) {
								$skip = false;
							}

							$url = $matrix->getCanonicalUrl( $lang, $site );
							$site_out = array(
								'url' => $url,
								'dbname' => $matrix->getDBName( $lang, $site ),
								'code' => $site,
								'sitename' => $matrix->getSitename( $lang, $site ),
							);
							$site_out = array_intersect_key( $site_out, $siteProp );
							if ( $matrix->isClosed( $lang, $site ) ) {
								$site_out['closed'] = '';
								if ( $closed ) {
									$skip = false;
								}
							}

							if ( $skip ) {
								continue;
							}
							$language['site'][] = $site_out;
						}
					}
					$result->setIndexedTagName( $language['site'], 'site' );
				}

				$count++;
				$matrix_out[] = $language;
			}
		}

		$result->setIndexedTagName( $matrix_out, 'language' );
		$result->addValue( null, "sitematrix", $matrix_out );

		if ( isset( $type['special'] ) && $count < $limit ) {
			$specials = array();
			foreach ( $matrix->getSpecials() as $special ) {
				list( $lang, $site ) = $special;
				$dbName = $matrix->getDBName( $lang, $site );
				if ( $continue[0] == 'special' && $dbName < $continue[1] ) {
					continue;
				}
				if ( $count >= $limit ) {
					$this->setContinueEnumParameter( 'continue', "special|$dbName" );
					break;
				}
				$url = $matrix->getCanonicalUrl( $lang, $site );

				$wiki = array();
				$wiki['url'] = $url;
				$wiki['dbname'] = $dbName;
				$wiki['code'] = str_replace( '_', '-', $lang ) . ( $site != 'wiki' ? $site : '' );

				$skip = true;

				if ( $all ) {
					$skip = false;
				}
				if ( $matrix->isPrivate( $lang . $site ) ) {
					$wiki['private'] = '';

					if ( $private ) {
						$skip = false;
					}
				}
				if ( $matrix->isFishbowl( $lang . $site ) ) {
					$wiki['fishbowl'] = '';

					if ( $fishbowl ) {
						$skip = false;
					}
				}
				if ( $matrix->isClosed( $lang, $site ) ) {
					$wiki['closed'] = '';

					if ( $closed ) {
						$skip = false;
					}
				}

				if ( $skip ) {
					continue;
				}

				$specials[] = $wiki;
			}

			$result->setIndexedTagName( $specials, 'special' );
			$result->addValue( "sitematrix", "specials", $specials );
		}
	}

	protected function setContinueEnumParameter( $paramName, $paramValue ) {
		$paramName = $this->encodeParamName( $paramName );
		$msg = array( $paramName => $paramValue );
		$result = $this->getResult();
		$result->disableSizeCheck();
		$result->addValue( 'query-continue', $this->getModuleName(), $msg );
		$result->enableSizeCheck();
	}

	public function getAllowedParams() {
		return array(
			'type' => array(
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => array(
					'special',
					'language'
				),
				ApiBase::PARAM_DFLT => 'special|language',
			),
			'state' => array(
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => array(
					'all',
					'closed',
					'private',
					'fishbowl'
				),
				ApiBase::PARAM_DFLT => 'all',
			),
			'langprop' => array(
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => array(
					'code',
					'name',
					'site',
					'localname',
				),
				ApiBase::PARAM_DFLT => 'code|name|site|localname',
			),
			'siteprop' => array(
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => array(
					'url',
					'dbname',
					'code',
					'sitename',
				),
				ApiBase::PARAM_DFLT => 'url|dbname|code|sitename',
			),
			'limit' => array(
				ApiBase::PARAM_DFLT => 5000,
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => 5000,
				ApiBase::PARAM_MAX2 => 5000,
			),
			'continue' => null,
		);
	}

	public function getParamDescription() {
		return array(
			'type' => array(
				'Filter the Site Matrix by type',
				' special  - One off, and multilingual Wikimedia projects',
				' language - Wikimedia projects under this language code',
			),
			'state' => array(
				'Filter the Site Matrix by wiki state',
				' closed   - No write access, full read access',
				' private  - Read and write restricted',
				' fishbowl - Restricted write access, full read access',
			),
			'langprop' => 'Which information about a language to return',
			'siteprop' => 'Which information about a site to return',
			'limit' => 'Maximum number of results',
			'continue' => 'When more results are available, use this to continue',
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'badcontinue', 'info' => 'Invalid continue param. You should pass the original value returned by the previous query' ),
		) );
	}

	public function getDescription() {
		return array(
			'Get Wikimedia sites list',
			'The code is either the unique identifier for specials else, for languages, the project code',
		);
	}

	public function getExamples() {
		return array(
			'api.php?action=sitematrix',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
}
