<?php
/**
 * @package MediaWiki
 */

/**
 * Class to represent an image
 * 
 * Provides methods to retrieve paths (physical, logical, URL),
 * to generate thumbnails or for uploading.
 * @package MediaWiki
 */
class Image
{
	/**#@+
	 * @access private
	 */
	var	$name,		# name of the image
		$imagePath,	# Path of the image
		$url,		# Image URL
		$title,		# Title object for this image. Initialized when needed.
		$fileExists,	# does the image file exist on disk?
		$fromSharedDirectory, # load this image from $wgSharedUploadDirectory
		$historyLine,	# Number of line to return by nextHistoryLine()
		$historyRes,	# result of the query for the image's history
		$width,		# \
		$height,	#  |
		$bits,		#   --- returned by getimagesize, see http://de3.php.net/manual/en/function.getimagesize.php
		$type,		#  |
		$attr,		# /
		$attributesLoaded;

	/**#@-*/


	/**
	 * Create an Image object from an image name
	 *
	 * @param string $name name of the image, used to create a title object using Title::makeTitleSafe
	 * @access public
	 */
	function Image( $name, $recache = false )
	{
		global $wgUploadDirectory,$wgHashedUploadDirectory,
		       $wgUseSharedUploads, $wgSharedUploadDirectory, 
		       $wgHashedSharedUploadDirectory,$wgUseLatin1,
		       $wgSharedLatin1,$wgLang,
			   $wgMemc, $wgDBname, $wgSharedUploadDBname;
		
		$this->attributesLoaded = false;
		$this->name      = $name;
		$this->title     = Title::makeTitleSafe( NS_IMAGE, $this->name );
		if( is_null( $this->title ) ) {
			wfDebugDieBacktrace( 'Bad title given to Image() constructor: ' . htmlspecialchars( $name ) );
		}
		$this->fromSharedDirectory = false;				
		if ($wgHashedUploadDirectory) {
			$hash 		 = md5( $this->title->getDBkey() );
			$this->imagePath = $wgUploadDirectory . '/' . $hash{0} . '/' .
						substr( $hash, 0, 2 ) . "/{$name}";
		} else {
			$this->imagePath = $wgUploadDirectory . '/' . $name;
		}

		$n = strrpos( $name, '.' );
		$this->extension = strtolower( $n ? substr( $name, $n + 1 ) : '' );
		$gis = false;
		$hashedName = md5($this->name);
		$cacheKey = "$wgDBname:image:".$hashedName;
		$foundCached = false;

		if ( !$recache ) {
			$cachedValues = $wgMemc->get( $cacheKey );

			if (!empty($cachedValues) && is_array($cachedValues)) {
				wfIncrStats( "image_cache_hit" );
				if ($wgUseSharedUploads && $cachedValues['fromShared']) {
					# if this is shared file, we need to check if image
					# in shared repository has not changed
					$commonsCachedValues = $wgMemc->get( "$wgSharedUploadDBname:image:".$hashedName );
					if (!empty($commonsCachedValues) && is_array($commonsCachedValues)) {
						$this->name = $commonsCachedValues['name'];
						$this->imagePath = $commonsCachedValues['imagePath'];
						$this->fileExists = $commonsCachedValues['fileExists'];
						$this->fromSharedDirectory = true;
						$gis = $commonsCachedValues['gis'];
						$foundCached = true;
					}
				}
				else {
					$this->name = $cachedValues['name'];
					$this->imagePath = $cachedValues['imagePath'];
					$this->fileExists = $cachedValues['fileExists'];
					$this->fromSharedDirectory = false;
					$gis = $cachedValues['gis'];
					$foundCached = true;
				}
			} else {
				wfIncrStats( "image_cache_miss" );
			}
		} else {
			wfIncrStats( "image_cache_update" );
		}

		if ($foundCached) {
			$this->loadAttributesFromGis($gis);
			$this->attributesLoaded = true;
		} else {
			$this->fileExists = file_exists( $this->imagePath);		
		
			# If the file is not found, and a shared upload directory 
			# like the Wikimedia Commons is used, look for it there.
			if (!$this->fileExists && $wgUseSharedUploads) {
				# in case we're running a capitallinks=false wiki						
				$sharedname=$wgLang->ucfirst($name);
				$sharedtitle=$wgLang->ucfirst($this->title->getDBkey());
				if($wgUseLatin1 && !$wgSharedLatin1) {				
					$sharedname=utf8_encode($sharedname);
					$sharedtitle=utf8_encode($sharedtitle);					
				}
				$sharedhashedName = md5($sharedname);
			
				if($wgHashedSharedUploadDirectory) {				
					$hash = md5( $sharedtitle );
					$this->imagePath = $wgSharedUploadDirectory . '/' . $hash{0} . '/' .
						substr( $hash, 0, 2 ) . "/".$sharedname;
	 			} else {
					$this->imagePath = $wgSharedUploadDirectory . '/' . $sharedname;
				}			
				$this->fileExists = file_exists( $this->imagePath);
				$this->fromSharedDirectory = true;			
				$name=$sharedname;
			}		

			$cachedValues = array('name' => $this->name,
								  'imagePath' => $this->imagePath,
								  'fileExists' => $this->fileExists,
								  'fromShared' => $this->fromSharedDirectory,
								  'gis' => $this->loadAttributes());

			$wgMemc->set( $cacheKey, $cachedValues, 7*86400 );

			if ($wgUseSharedUploads && $this->fromSharedDirectory) {
				$cachedValues['fromShared'] = false;
				$wgMemc->set( "$wgSharedUploadDBname:image:".$sharedhashedName, $cachedValues, 7*86400 );
			}
		}
		
		if($this->fileExists) {			
			$this->url       = $this->wfImageUrl( $name, $this->fromSharedDirectory );
		} else {
			$this->url='';
		}

		$this->historyLine = 0;				
	}

	/**
	 * Factory function
	 *
	 * Create a new image object from a title object.
	 *
	 * @param Title $nt Title object. Must be from namespace "image"
	 * @access public
	 */
	function newFromTitle( $nt )
	{
		if( is_null( $nt ) ) {
			wfDebugDieBacktrace( 'Image::newFromTitle given null title' );
		}
		$img = new Image( $nt->getDBKey() );
		$img->title = $nt;
		return $img;
	}

	function loadAttributesFromGis($gis)
	{
		if( $gis !== false ) {
			$this->width = $gis[0];
			$this->height = $gis[1];
			$this->type = $gis[2];
			$this->attr = $gis[3];
			if ( isset( $gis['bits'] ) )  {
				$this->bits = $gis['bits'];
			} else {
				$this->bits = 0;
			}
		}
	}

	function loadAttributes()
	{
	    $gis = false;
		if ( $this->fileExists ) {
			if( $this->extension == 'svg' ) {
				@$gis = getSVGsize( $this->imagePath );
			} else {
				@$gis = getimagesize( $this->imagePath );
			}
			$this->loadAttributesFromGis($gis);
		} else {
			$this->width = 0;
			$this->height = 0;
			$this->type = 0;
			$this->attr = 0;
			$this->bits = 0;
		}
		$this->attributesLoaded = true;
		return $gis;
	}

	/**
	 * Return the name of this image
	 * @access public
	 */
	function getName()
	{
		return $this->name;
	}

	/**
	 * Return the associated title object
	 * @access public
	 */
	function getTitle()
	{
		return $this->title;
	}

	/**
	 * Return the URL of the image file
	 * @access public
	 */
	function getURL()
	{
		return $this->url;
	}
	
	function getViewURL() {
		if( $this->mustRender() ) {
			return $this->createThumb( $this->getWidth() );
		} else {
			return $this->getURL();
		}
	}

	/**
	 * Return the image path of the image in the
	 * local file system as an absolute path
	 * @access public
	 */
	function getImagePath()
	{
		return $this->imagePath;
	}

	/**
	 * Return the width of the image
	 *
	 * Returns -1 if the file specified is not a known image type
	 * @access public
	 */
	function getWidth()
	{
		if ( !$this->attributesLoaded ) {
			$this->loadAttributes();
		}
		return $this->width;
	}

	/**
	 * Return the height of the image
	 *
	 * Returns -1 if the file specified is not a known image type
	 * @access public
	 */
	function getHeight()
	{
		if ( !$this->attributesLoaded ) {
			$this->loadAttributes();
		}
		return $this->height;
	}

	/**
	 * Return the size of the image file, in bytes
	 * @access public
	 */
	function getSize()
	{
		$st = stat( $this->getImagePath() );
		return $st['size'];
	}

	/**
	 * Return the type of the image
	 *
	 * -  1 GIF
	 * -  2 JPG
	 * -  3 PNG
	 * - 15 WBMP
	 * - 16 XBM
	 */
	function getType()
	{
		if ( !$this->attributesLoaded ) {
			$this->loadAttributes();
		}
		return $this->type;
	}

	/**
	 * Return the escapeLocalURL of this image
	 * @access public
	 */
	function getEscapeLocalURL()
	{
		return $this->title->escapeLocalURL();
	}

	/**
	 * Return the escapeFullURL of this image
	 * @access public
	 */
	function getEscapeFullURL()
	{
		return $this->title->escapeFullURL();
	}

	/**
	 * Return the URL of an image, provided its name.
	 *
	 * @param string $name	Name of the image, without the leading "Image:"
	 * @param boolean $fromSharedDirectory	Should this be in $wgSharedUploadPath?	 
	 * @access public
	 */
	function wfImageUrl( $name, $fromSharedDirectory = false )
	{
		global $wgUploadPath,$wgUploadBaseUrl,$wgHashedUploadDirectory,
		       $wgHashedSharedUploadDirectory,$wgSharedUploadPath;
		if($fromSharedDirectory) {
			$hash = $wgHashedSharedUploadDirectory;
			$base = '';
			$path = $wgSharedUploadPath;
		} else {
			$hash = $wgHashedUploadDirectory;
			$base = $wgUploadBaseUrl;
			$path = $wgUploadPath;
		}			
		if ( $hash ) {
			$hash = md5( $name );
        		$url = "{$base}{$path}/" . $hash{0} . "/" .
              		substr( $hash, 0, 2 ) . "/{$name}";
		} else {
			$url = "{$base}{$path}/{$name}";
		}
        	return wfUrlencode( $url );
	}

	/**
	 * Returns true iff the image file exists on disk.
	 *
	 * @access public
	 */
	function exists()
	{
		return $this->fileExists;
	}

	/**
	 *
	 * @access private
	 */
	function thumbUrl( $width, $subdir='thumb') {
		global $wgUploadPath,$wgHashedUploadDirectory, $wgUploadBaseUrl,
		       $wgSharedUploadPath,$wgSharedUploadDirectory,
		       $wgHashedSharedUploadDirectory,$wgUseLatin1,$wgSharedLatin1;
		$name = $this->thumbName( $width );		
		if($this->fromSharedDirectory) {
			$hashdir = $wgHashedSharedUploadDirectory;
			$base = '';
			$path = $wgSharedUploadPath;
			if($wgUseLatin1 && !$wgSharedLatin1) {
				$name=utf8_encode($name);
			}			
		} else {
			$hashdir = $wgHashedUploadDirectory;
			$base = $wgUploadBaseUrl;
			$path = $wgUploadPath;
		}
		if ($hashdir) {
			$hash = md5( $name );
			$url = "{$base}{$path}/{$subdir}/" . $hash{0} . "/" . 
				substr( $hash, 0, 2 ) . "/{$name}";
		} else {
			$url = "{$base}{$path}/{$subdir}/{$name}";
		}

		return wfUrlencode($url);
	}

	/**
	 * Return the file name of a thumbnail of the specified width
	 *
	 * @param integer $width	Width of the thumbnail image
	 * @param boolean $shared	Does the thumbnail come from the shared repository?
	 * @access private
	 */
	function thumbName( $width, $shared=false ) {
		global $wgUseLatin1,$wgSharedLatin1;
		$thumb = $width."px-".$this->name;
		if( $this->extension == 'svg' ) {
			# Rasterize SVG vector images to PNG
			$thumb .= '.png';
		}
		if( $shared && $wgUseLatin1 && !$wgSharedLatin1) { 
			$thumb=utf8_encode($thumb); 
		} 
		return $thumb;
	}

	/**
	 * Create a thumbnail of the image having the specified width/height.
	 * The thumbnail will not be created if the width is larger than the
	 * image's width. Let the browser do the scaling in this case.
	 * The thumbnail is stored on disk and is only computed if the thumbnail
	 * file does not exist OR if it is older than the image.
	 * Returns the URL.
	 * 
	 * Keeps aspect ratio of original image. If both width and height are
	 * specified, the generated image will be no bigger than width x height,
	 * and will also have correct aspect ratio.
	 *
	 * @param integer $width	maximum width of the generated thumbnail
	 * @param integer $height	maximum height of the image (optional)
	 * @access public
	 */
	function createThumb( $width, $height=-1 ) {
		$thumb = $this->getThumbnail( $width, $height );
		if( is_null( $thumb ) ) return '';
		return $thumb->getUrl();
	}
	
	/**
	 * As createThumb, but returns a ThumbnailImage object. This can
	 * provide access to the actual file, the real size of the thumb,
	 * and can produce a convenient <img> tag for you.
	 *
	 * @param integer $width	maximum width of the generated thumbnail
	 * @param integer $height	maximum height of the image (optional)
	 * @return ThumbnailImage
	 * @access public
	 */
	function &getThumbnail( $width, $height=-1 ) {
		if ( !$this->attributesLoaded ) {
			$this->loadAttributes();
		}
		if ( $height == -1 ) {
			return $this->renderThumb( $width );
		}
		if ( $width < $this->width ) {
			$thumbheight = $this->height * $width / $this->width;
			$thumbwidth = $width;
		} else {
			$thumbheight = $this->height;
			$thumbwidth = $this->width;
		}
		if ( $thumbheight > $height ) {
			$thumbwidth = $thumbwidth * $height / $thumbheight;
			$thumbheight = $height;
		}
		$thumb = $this->renderThumb( $thumbwidth );
		if( is_null( $thumb ) ) {
			$thumb = $this->iconThumb();
		}
		return $thumb;
	}
	
	/**
	 * @return ThumbnailImage
	 */
	function iconThumb() {
		global $wgStylePath, $wgStyleDirectory;
		
		$try = array( 'fileicon-' . $this->extension . '.png', 'fileicon.png' );
		foreach( $try as $icon ) {
			$path = '/common/images/' . $icon;
			$filepath = $wgStyleDirectory . $path;
			if( file_exists( $filepath ) ) {
				return new ThumbnailImage( $filepath, $wgStylePath . $path );
			}
		}
		return null;
	}
		
	/**
	 * Create a thumbnail of the image having the specified width.
	 * The thumbnail will not be created if the width is larger than the
	 * image's width. Let the browser do the scaling in this case.
	 * The thumbnail is stored on disk and is only computed if the thumbnail
	 * file does not exist OR if it is older than the image.
	 * Returns an object which can return the pathname, URL, and physical
	 * pixel size of the thumbnail -- or null on failure.
	 *
	 * @return ThumbnailImage
	 * @access private
	 */
	function /* private */ renderThumb( $width ) {
		global $wgImageMagickConvertCommand;
		global $wgUseImageMagick;
		global $wgUseSquid, $wgInternalServer;
		
		if ( !$this->attributesLoaded ) {
			$this->loadAttributes();
		}

		$width = IntVal( $width );

		$thumbName = $this->thumbName( $width, $this->fromSharedDirectory );
		$thumbPath = wfImageThumbDir( $thumbName, 'thumb', $this->fromSharedDirectory ).'/'.$thumbName;
		$thumbUrl  = $this->thumbUrl( $width );
		#wfDebug ( "Render name: $thumbName path: $thumbPath url: $thumbUrl\n");
		if ( ! $this->exists() )
		{
			# If there is no image, there will be no thumbnail
			return null;
		}
		
		# Sanity check $width
		if( $width <= 0 ) {
			# BZZZT
			return null;
		}

		if( $width >= $this->width && !$this->mustRender() ) {
			# Don't make an image bigger than the source
			return new ThumbnailImage( $this->getImagePath(), $this->getViewURL() );
		}

		if ( (! file_exists( $thumbPath ) ) || ( filemtime($thumbPath) < filemtime($this->imagePath) ) ) {
			if( $this->extension == 'svg' ) {
				global $wgSVGConverters, $wgSVGConverter;
				if( isset( $wgSVGConverters[$wgSVGConverter] ) ) {
					global $wgSVGConverterPath;
					$cmd = str_replace(
						array( '$path/', '$width', '$input', '$output' ),
						array( $wgSVGConverterPath,
							   $width,
							   escapeshellarg( $this->imagePath ),
							   escapeshellarg( $thumbPath ) ),
						$wgSVGConverters[$wgSVGConverter] );
					$conv = shell_exec( $cmd );
				} else {
					$conv = false;
				}
			} elseif ( $wgUseImageMagick ) {
				# use ImageMagick
				# Specify white background color, will be used for transparent images
				# in Internet Explorer/Windows instead of default black.
				$cmd  =  $wgImageMagickConvertCommand .
					" -quality 85 -background white -geometry {$width} ".
					escapeshellarg($this->imagePath) . " " .
					escapeshellarg($thumbPath);				
				$conv = shell_exec( $cmd );
			} else {
				# Use PHP's builtin GD library functions.
				#
				# First find out what kind of file this is, and select the correct
				# input routine for this.

				$truecolor = false;
				
				switch( $this->type ) {
					case 1: # GIF
						$src_image = imagecreatefromgif( $this->imagePath );
						break;
					case 2: # JPG
						$src_image = imagecreatefromjpeg( $this->imagePath );
						$truecolor = true;
						break;
					case 3: # PNG
						$src_image = imagecreatefrompng( $this->imagePath );
						$truecolor = ( $this->bits > 8 );
						break;
					case 15: # WBMP for WML
						$src_image = imagecreatefromwbmp( $this->imagePath );
						break;
					case 16: # XBM
						$src_image = imagecreatefromxbm( $this->imagePath );
						break;
					default:
						return 'Image type not supported';
						break;
				}
				$height = floor( $this->height * ( $width/$this->width ) );
				if ( $truecolor ) {
					$dst_image = imagecreatetruecolor( $width, $height );
				} else {
					$dst_image = imagecreate( $width, $height );
				}
				imagecopyresampled( $dst_image, $src_image, 
							0,0,0,0,
							$width, $height, $this->width, $this->height );
				switch( $this->type ) {
					case 1:  # GIF
					case 3:  # PNG
					case 15: # WBMP
					case 16: # XBM
						#$thumbUrl .= ".png";
						#$thumbPath .= ".png";
						imagepng( $dst_image, $thumbPath );
						break;
					case 2:  # JPEG
						#$thumbUrl .= ".jpg";
						#$thumbPath .= ".jpg";
						imageinterlace( $dst_image );
						imagejpeg( $dst_image, $thumbPath, 95 );
						break;
					default:
						break;
				}
				imagedestroy( $dst_image );
				imagedestroy( $src_image );
			}
			#
			# Check for zero-sized thumbnails. Those can be generated when 
			# no disk space is available or some other error occurs
			#
			if( file_exists( $thumbPath ) ) {
				$thumbstat = stat( $thumbPath );
				if( $thumbstat['size'] == 0 ) {
					unlink( $thumbPath );
				}
			}

			# Purge squid
			# This has to be done after the image is updated and present for all machines on NFS, 
			# or else the old version might be stored into the squid again
			if ( $wgUseSquid ) {
				# don't prefix with internal server if we share one (-> Commons)
				$prefix = preg_match("/^http:\/\//",$thumbUrl) ? '' : $wgInternalServer;
				$urlArr = Array(
					$prefix.$thumbUrl
				);
				wfPurgeSquidServers($urlArr);
			}
		}
		return new ThumbnailImage( $thumbPath, $thumbUrl );
	} // END OF function createThumb

	/**
	 * Return the image history of this image, line by line.
	 * starts with current version, then old versions.
	 * uses $this->historyLine to check which line to return:
	 *  0      return line for current version
	 *  1      query for old versions, return first one
	 *  2, ... return next old version from above query
	 *
	 * @access public
	 */
	function nextHistoryLine()
	{
		$fname = 'Image::nextHistoryLine()';
		$dbr =& wfGetDB( DB_SLAVE );
		if ( $this->historyLine == 0 ) {// called for the first time, return line from cur 
			$this->historyRes = $dbr->select( 'image', 
				array( 'img_size','img_description','img_user','img_user_text','img_timestamp', "'' AS oi_archive_name" ), 
				array( 'img_name' => $this->title->getDBkey() ),
				$fname
			);
			if ( 0 == wfNumRows( $this->historyRes ) ) { 
				return FALSE; 
			}
		} else if ( $this->historyLine == 1 ) {
			$this->historyRes = $dbr->select( 'oldimage', 
				array( 'oi_size AS img_size', 'oi_description AS img_description', 'oi_user AS img_user',
					'oi_user_text AS img_user_text', 'oi_timestamp AS img_timestamp', 'oi_archive_name'
				), array( 'oi_name' => $this->title->getDBkey() ), $fname, array( 'ORDER BY' => 'oi_timestamp DESC' ) 
			);
		}
		$this->historyLine ++;

		return $dbr->fetchObject( $this->historyRes );
	}

	/**
	 * Reset the history pointer to the first element of the history
	 * @access public
	 */
	function resetHistory()
	{
		$this->historyLine = 0;
	}

	/**
	 * Return true if the file is of a type that can't be directly
	 * rendered by typical browsers and needs to be re-rasterized.
	 * @return bool
	 */
	function mustRender() {
		return ( $this->extension == 'svg' );
	}
	
} //class


/**
 * Returns the image directory of an image
 * If the directory does not exist, it is created.
 * The result is an absolute path.
 * 
 * @param string $fname		file name of the image file
 * @access public
 */
function wfImageDir( $fname )
{
	global $wgUploadDirectory, $wgHashedUploadDirectory;
	
	if (!$wgHashedUploadDirectory) { return $wgUploadDirectory; }

	$hash = md5( $fname );
	$oldumask = umask(0);
	$dest = $wgUploadDirectory . '/' . $hash{0};
	if ( ! is_dir( $dest ) ) { mkdir( $dest, 0777 ); }
	$dest .= '/' . substr( $hash, 0, 2 );
	if ( ! is_dir( $dest ) ) { mkdir( $dest, 0777 ); }

	umask( $oldumask );
	return $dest;
}

/**
 * Returns the image directory of an image's thubnail
 * If the directory does not exist, it is created.
 * The result is an absolute path.
 * 
 * @param string $fname		file name of the thumbnail file, including file size prefix
 * @param string $subdir	(optional) subdirectory of the image upload directory that should be used for storing the thumbnail. Default is 'thumb'
 * @param boolean $shared	(optional) use the shared upload directory
 * @access public
 */
function wfImageThumbDir( $fname , $subdir='thumb', $shared=false)
{
	return wfImageArchiveDir( $fname, $subdir, $shared );
}

/**
 * Returns the image directory of an image's old version
 * If the directory does not exist, it is created.
 * The result is an absolute path.
 * 
 * @param string $fname		file name of the thumbnail file, including file size prefix
 * @param string $subdir	(optional) subdirectory of the image upload directory that should be used for storing the old version. Default is 'archive'
 * @param boolean $shared	(optional) use the shared upload directory (only relevant for other functions which call this one)
 * @access public
 */
function wfImageArchiveDir( $fname , $subdir='archive', $shared=false )
{
	global $wgUploadDirectory, $wgHashedUploadDirectory,
	       $wgSharedUploadDirectory, $wgHashedSharedUploadDirectory;
	$dir = $shared ? $wgSharedUploadDirectory : $wgUploadDirectory;
	$hashdir = $shared ? $wgHashedSharedUploadDirectory : $wgHashedUploadDirectory;	
	if (!$hashdir) { return $dir.'/'.$subdir; }
	$hash = md5( $fname );
	$oldumask = umask(0);
	# Suppress warning messages here; if the file itself can't
	# be written we'll worry about it then.
	$archive = $dir.'/'.$subdir;
	if ( ! is_dir( $archive ) ) { @mkdir( $archive, 0777 ); }
	$archive .= '/' . $hash{0};
	if ( ! is_dir( $archive ) ) { @mkdir( $archive, 0777 ); }
	$archive .= '/' . substr( $hash, 0, 2 );
	if ( ! is_dir( $archive ) ) { @mkdir( $archive, 0777 ); }

	umask( $oldumask );
	return $archive;
}

/**
 * Record an image upload in the upload log.
 */
function wfRecordUpload( $name, $oldver, $size, $desc, $copyStatus = "", $source = "" )
{
	global $wgUser, $wgLang, $wgTitle, $wgOut, $wgDeferredUpdateList;
	global $wgUseCopyrightUpload;

	$fname = 'wfRecordUpload';
	$dbw =& wfGetDB( DB_MASTER );

	# img_name must be unique
	if ( !$dbw->indexUnique( 'image', 'img_name' ) && !$dbw->indexExists('image','PRIMARY') ) {
		wfDebugDieBacktrace( 'Database schema not up to date, please run maintenance/archives/patch-image_name_unique.sql' );
	}


	$now = wfTimestampNow();
	$won = wfInvertTimestamp( $now );
	$size = IntVal( $size );

	if ( $wgUseCopyrightUpload )
	  {
		$textdesc = '== ' . wfMsg ( 'filedesc' ) . " ==\n" . $desc . "\n" .
		  '== ' . wfMsg ( 'filestatus' ) . " ==\n" . $copyStatus . "\n" .
		  '== ' . wfMsg ( 'filesource' ) . " ==\n" . $source ;
	  }
	else $textdesc = $desc ;

	$now = wfTimestampNow();
	$won = wfInvertTimestamp( $now );

	# Test to see if the row exists using INSERT IGNORE
	# This avoids race conditions by locking the row until the commit, and also
	# doesn't deadlock. SELECT FOR UPDATE causes a deadlock for every race condition.
	$dbw->insert( 'image',
		array(
			'img_name' => $name,
			'img_size'=> $size,
			'img_timestamp' => $dbw->timestamp($now),
			'img_description' => $desc,
			'img_user' => $wgUser->getID(),
			'img_user_text' => $wgUser->getName(),
		), $fname, 'IGNORE'
	);
	$descTitle = Title::makeTitleSafe( NS_IMAGE, $name );

	if ( $dbw->affectedRows() ) {
		# Successfully inserted, this is a new image
		$id = $descTitle->getArticleID();

		if ( $id == 0 ) {
			$article = new Article( $descTitle );
			$article->insertNewArticle( $textdesc, $desc, false, false );
		}
	} else {
		# Collision, this is an update of an image
		# Get current image row for update
		$s = $dbw->selectRow( 'image', array( 'img_name','img_size','img_timestamp','img_description',
		  'img_user','img_user_text' ), array( 'img_name' => $name ), $fname, 'FOR UPDATE' );

		# Insert it into oldimage
		$dbw->insert( 'oldimage',
			array(
				'oi_name' => $s->img_name,
				'oi_archive_name' => $oldver,
				'oi_size' => $s->img_size,
				'oi_timestamp' => $dbw->timestamp($s->img_timestamp),
				'oi_description' => $s->img_description,
				'oi_user' => $s->img_user,
				'oi_user_text' => $s->img_user_text
			), $fname
		);

		# Update the current image row
		$dbw->update( 'image',
			array( /* SET */
				'img_size' => $size,
				'img_timestamp' => $dbw->timestamp(),
				'img_user' => $wgUser->getID(),
				'img_user_text' => $wgUser->getName(),
				'img_description' => $desc,
			), array( /* WHERE */
				'img_name' => $name
			), $fname
		);

		# Invalidate the cache for the description page
		$descTitle->invalidateCache();
	}

	$log = new LogPage( 'upload' );
	$log->addEntry( 'upload', $descTitle, $desc );
	
	# Run page save hooks
	$article = new Article( $descTitle );
	if( wfRunHooks( 'ArticleSave', array(
			&$article, &$wgUser, $textdesc, $desc, false, false, null ) ) ) {
		wfRunHooks( 'ArticleSaveComplete', array(
			&$article, &$wgUser, $textdesc, $desc, false, false, null ) );
	}
	
}

/**
 * Returns the image URL of an image's old version
 * 
 * @param string $fname		file name of the image file
 * @param string $subdir	(optional) subdirectory of the image upload directory that is used by the old version. Default is 'archive'
 * @access public
 */
function wfImageArchiveUrl( $name, $subdir='archive' )
{
	global $wgUploadPath, $wgHashedUploadDirectory;

	if ($wgHashedUploadDirectory) {
		$hash = md5( substr( $name, 15) );
		$url = $wgUploadPath.'/'.$subdir.'/' . $hash{0} . '/' .
		  substr( $hash, 0, 2 ) . '/'.$name;
	} else {
		$url = $wgUploadPath.'/'.$subdir.'/'.$name;
	}
	return wfUrlencode($url);
}

/**
 * Return a rounded pixel equivalent for a labeled CSS/SVG length.
 * http://www.w3.org/TR/SVG11/coords.html#UnitIdentifiers
 *
 * @param string $length
 * @return int Length in pixels
 */
function scaleSVGUnit( $length ) {
	static $unitLength = array(
		'px' => 1.0,
		'pt' => 1.25,
		'pc' => 15.0,
		'mm' => 3.543307,
		'cm' => 35.43307,
		'in' => 90.0,
		''   => 1.0, // "User units" pixels by default
		'%'  => 2.0, // Fake it!
		);
	if( preg_match( '/^(\d+)(em|ex|px|pt|pc|cm|mm|in|%|)$/', $length, $matches ) ) {
		$length = FloatVal( $matches[1] );
		$unit = $matches[2];
		return round( $length * $unitLength[$unit] );
	} else {
		// Assume pixels
		return round( FloatVal( $length ) );
	}
}

/**
 * Compatible with PHP getimagesize()
 * @todo support gzipped SVGZ
 * @todo check XML more carefully
 * @todo sensible defaults
 *
 * @param string $filename
 * @return array
 */
function getSVGsize( $filename ) {
	$width = 256;
	$height = 256;
	
	// Read a chunk of the file
	$f = fopen( $filename, "rt" );
	if( !$f ) return false;
	$chunk = fread( $f, 4096 );
	fclose( $f );
	
	// Uber-crappy hack! Run through a real XML parser.
	if( !preg_match( '/<svg\s*([^>]*)\s*>/s', $chunk, $matches ) ) {
		return false;
	}
	$tag = $matches[1];
	if( preg_match( '/\bwidth\s*=\s*("[^"]+"|\'[^\']+\')/s', $tag, $matches ) ) {
		$width = scaleSVGUnit( trim( substr( $matches[1], 1, -1 ) ) );
	}
	if( preg_match( '/\bheight\s*=\s*("[^"]+"|\'[^\']+\')/s', $tag, $matches ) ) {
		$height = scaleSVGUnit( trim( substr( $matches[1], 1, -1 ) ) );
	}
	
	return array( $width, $height, 'SVG',
		"width=\"$width\" height=\"$height\"" );
}

/**
 * Determine if an image exists on the 'bad image list'
 *
 * @param string $name The image to check
 * @return bool
 */
function wfIsBadImage( $name ) {
	global $wgLang;

	static $titleList = false;
	if ( $titleList === false ) {
		$titleList = array();

		$lines = explode("\n", wfMsgForContent( 'bad_image_list' ));
		foreach ( $lines as $line ) {
			if ( preg_match( '/^\*\s*\[{2}:(' . $wgLang->getNsText( NS_IMAGE ) . ':.*?)\]{2}/', $line, $m ) ) {
				$t = Title::newFromText( $m[1] );
				$titleList[$t->getDBkey()] = 1;
			}
		}
	}

	return array_key_exists( $name, $titleList );
}
	


/**
 * Wrapper class for thumbnail images
 */
class ThumbnailImage {
	/**
	 * @param string $path Filesystem path to the thumb
	 * @param string $url URL path to the thumb
	 * @access private
	 */
	function ThumbnailImage( $path, $url ) {
		$this->url = $url;
		$this->path = $path;
		$size = @getimagesize( $this->path );
		if( $size ) {
			$this->width = $size[0];
			$this->height = $size[1];
		} else {
			$this->width = 0;
			$this->height = 0;
		}
	}
	
	function getUrl() {
		return $this->url;
	}
	
	/**
	 * Return HTML <img ... /> tag for the thumbnail, will include
	 * width and height attributes and a blank alt text (as required).
	 *
	 * You can set or override additional attributes by passing an
	 * associative array of name => data pairs. The data will be escaped
	 * for HTML output, so should be in plaintext.
	 *
	 * @param array $attribs
	 * @return string
	 * @access public
	 */
	function toHtml( $attribs = array() ) {
		$attribs['src'] = $this->url;
		$attribs['width'] = $this->width;
		$attribs['height'] = $this->height;
		if( !isset( $attribs['alt'] ) ) $attribs['alt'] = '';

		$html = '<img ';
		foreach( $attribs as $name => $data ) {
			$html .= $name . '="' . htmlspecialchars( $data ) . '" ';
		}
		$html .= '/>';
		return $html;
	}

        /**             
         * Return the size of the thumbnail file, in bytes
         * @access public 
         */                     
        function getSize()
        {               
                $st = stat( $this->path );
                if( $st ) {     
                        return $st['size']; 
                } else {        
                        return false;                }                       
        }

}

?>
