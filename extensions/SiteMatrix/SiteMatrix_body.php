<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo "SiteMatrix extension\n";
	exit( 1 );
}

class SiteMatrix {
	protected $langlist, $sites, $names, $hosts;

	/**
	 * @var array|null
	 */
	protected $private = null;

	/**
	 * @var array|null
	 */
	protected $fishbowl = null;

	/**
	 * @var array|null
	 */
	protected $closed = null;

	protected $specials, $matrix, $count, $countPerSite;

	public function __construct() {
		global $wgSiteMatrixFile, $wgSiteMatrixSites;
		global $wgLocalDatabases, $wgConf;

		$wgConf->loadFullData();

		if ( file_exists( $wgSiteMatrixFile ) ) {
			$this->langlist = $this->extractFile( $wgSiteMatrixFile );
			$hideEmpty = false;
		} else {
			$this->langlist = array_keys( Language::fetchLanguageNames( false ) );
			$hideEmpty = true;
		}

		sort( $this->langlist );
		$xLanglist = array_flip( $this->langlist );

		$this->sites = array();
		$this->names = array();
		$this->hosts = array();

		foreach ( $wgSiteMatrixSites as $site => $conf ) {
			$this->sites[] = $site;
			$this->names[$site] = $conf['name'] . ( isset( $conf['prefix'] ) ?
				'<br />' . $conf['prefix'] : '' );
			$this->hosts[$site] = $conf['host'];
		}

		# Initialize $countPerSite
		$this->countPerSite = array();
		foreach ( $this->sites as $site ) {
			$this->countPerSite[$site] = 0;
		}

		# Tabulate the matrix
		$this->specials = array();
		$this->matrix = array();
		foreach ( $wgLocalDatabases as $db ) {
			# Find suffix
			$found = false;
			foreach ( $this->sites as $site ) {
				$m = array();
				if ( preg_match( "/(.*)$site\$/", $db, $m ) ) {
					$lang = $m[1];
					$langhost = str_replace( '_', '-', $lang );
					if ( isset( $xLanglist[$langhost] ) ) {
						$this->matrix[$site][$langhost] = 1;
						$this->countPerSite[$site]++;
					} else {
						$this->specials[] = array( $lang, $site );
					}
					$found = true;
					break;
				}
			}
			if ( !$found ) {
				list( $major, $minor ) = $wgConf->siteFromDB( $db );
				$this->specials[] = array( str_replace( '-', '_', $minor ), $major );
			}
		}

		uasort( $this->specials, array( __CLASS__, 'sortSpecial' ) );

		if ( $hideEmpty ) {
			foreach ( $xLanglist as $lang => $unused ) {
				$empty = true;
				foreach ( $this->sites as $site ) {
					if ( !empty( $this->matrix[$site][$lang] ) ) {
						$empty = false;
					}
				}
				if ( $empty ) {
					unset( $xLanglist[$lang] );
				}
			}
			$this->langlist = array_keys( $xLanglist );
		}

		$this->count = count( $wgLocalDatabases );
	}

	/**
	 * @param $a1 array
	 * @param $a2 array
	 * @return int
	 */
	public static function sortSpecial( $a1, $a2 ) {
		return strcmp( $a1[0], $a2[0] );
	}

	/**
	 * @return array
	 */
	public function getLangList() {
		return $this->langlist;
	}

	/**
	 * @return array
	 */
	public function getNames() {
		return $this->names;
	}

	/**
	 * @return array
	 */
	public function getSites() {
		return $this->sites;
	}

	/**
	 * @return array
	 */
	public function getSpecials() {
		return $this->specials;
	}

	/**
	 * @return int
	 */
	public function getCount() {
		return $this->count;
	}

	/**
	 * @param $site string
	 * @return int
	 */
	public function getCountPerSite( $site ) {
		return $this->countPerSite[$site];
	}

	/**
	 * @param $site string
	 * @return string
	 */
	public function getSiteUrl( $site ) {
		return '//' . $this->hosts[$site] . '/';
	}

	/**
	 * @param string $minor Language
	 * @param string $major Site
	 * @param bool $canonical: use getCanonicalUrl()
	 * @return Mixed
	 */
	public function getUrl( $minor, $major, $canonical = false ) {
		global $wgConf;
		$dbname = $this->getDBName( $minor, $major );
		$minor = str_replace( '_', '-', $minor );
		return $wgConf->get( $canonical ? 'wgCanonicalServer' : 'wgServer',
			$dbname, $major, array( 'lang' => $minor, 'site' => $major )
		);
	}

	/**
	 * @param string $minor Language
	 * @param string $major Site
	 * @return Mixed
	 */
	public function getCanonicalUrl( $minor, $major ) {
		return $this->getUrl( $minor, $major, true );
	}

	/**
	 * @param $minor string
	 * @param $major string
	 * @return string
	 */
	public function getSitename( $minor, $major ) {
		global $wgConf;
		$dbname = $this->getDBName( $minor, $major );
		return $wgConf->get( 'wgSitename', $dbname );
	}

	/**
	 * @param $minor string
	 * @param $major string
	 * @return string
	 */
	public function getDBName( $minor, $major ) {
		return str_replace( '-', '_', $minor ) . $major;
	}

	/**
	 * @param string $minor Language
	 * @param string $major Site
	 * @return bool
	 */
	public function exist( $minor, $major ) {
		return !empty( $this->matrix[$major][$minor] );
	}

	/**
	 * @param string $minor Language
	 * @param string $major Site
	 * @return bool
	 */
	public function isClosed( $minor, $major ) {
		global $wgSiteMatrixClosedSites;

		$dbname = $this->getDBName( $minor, $major );

		if ( $wgSiteMatrixClosedSites === null ) {
			// Fallback to old behavior checking read-only settings;
			// not very reliable.
			global $wgConf;

			if ( $wgConf->get( 'wgReadOnly', $dbname, $major, array( 'site' => $major, 'lang' => $minor ) ) ) {
				return true;
			}
			$readOnlyFile = $wgConf->get( 'wgReadOnlyFile', $dbname, $major, array( 'site' => $major, 'lang' => $minor ) );
			if ( $readOnlyFile && file_exists( $readOnlyFile ) ) {
				return true;
			}
			return false;
		}

		if ( $this->closed == null ) {
			$this->closed = $this->extractDbList( $wgSiteMatrixClosedSites );
		}
		return in_array( $dbname, $this->closed );
	}

	/**
	 * @param string $dbname DatabaseName
	 * @return bool
	 */
	public function isPrivate( $dbname ) {
		global $wgSiteMatrixPrivateSites;

		if ( $this->private == null ) {
			$this->private = $this->extractDbList( $wgSiteMatrixPrivateSites );
		}

		return in_array( $dbname, $this->private );
	}

	/**
	 * @param string $dbname DatabaseName
	 * @return bool
	 */
	public function isFishbowl( $dbname ) {
		global $wgSiteMatrixFishbowlSites;

		if ( $this->fishbowl == null ) {
			$this->fishbowl = $this->extractDbList( $wgSiteMatrixFishbowlSites );
		}

		return in_array( $dbname, $this->fishbowl );
	}

	/**
	 * @param string $dbname DatabaseName
	 * @return bool
	 */
	public function isSpecial( $dbname ) {
		return in_array( $dbname, $this->specials );
	}

	/**
	 * Pull a list of dbnames from a given text file, or pass through an array.
	 * Used for the DB list configuration settings.
	 *
	 * @param mixed $listOrFilename array of strings, or string with a filename
	 * @return array
	 */
	private function extractDbList( $listOrFilename ) {
		if ( is_string( $listOrFilename ) ) {
			return $this->extractFile( $listOrFilename );
		} elseif ( is_array( $listOrFilename ) ) {
			return $listOrFilename;
		} else {
			return array();
		}
	}

	/**
	 * Pull a list of dbnames from a given text file.
	 *
	 * @param string $filename
	 * @return array
	 */
	private function extractFile( $filename ) {
		return array_map( 'trim', file( $filename ) );
	}

	/**
	 * Handler method for the APISiteInfoGeneralInfo hook
	 *
	 * @param ApiQuerySiteinfo $module
	 * @param array $results
	 * @return bool
	 */
	public static function APIQuerySiteInfoGeneralInfo( $module, &$results ) {
		global $wgDBname, $wgConf;

		$matrix = new SiteMatrix();

		list( $site, $lang ) = $wgConf->siteFromDB( $wgDBname );

		if ( $matrix->isClosed( $lang, $site ) ) {
			$results['closed'] = '';
		}

		if ( $matrix->isSpecial( $wgDBname ) ) {
			$results['special'] = '';
		}

		if ( $matrix->isPrivate( $wgDBname ) ) {
			$results['private'] = '';
		}

		if ( $matrix->isFishbowl( $wgDBname ) ) {
			$results['fishbowl'] = '';
		}

		return true;
	}
}
