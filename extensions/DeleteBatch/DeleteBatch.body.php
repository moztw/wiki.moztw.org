<?php

class SpecialDeleteBatch extends SpecialPage {
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'DeleteBatch'/*class*/, 'deletebatch'/*restriction*/ );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 * @throws UserBlockedError
	 * @return void
	 */
	public function execute( $par ) {
		# Check permissions
		$user = $this->getUser();
		if ( !$user->isAllowed( 'deletebatch' ) ) {
			$this->displayRestrictionError();
			return;
		}

		# Show a message if the database is in read-only mode
		if ( wfReadOnly() ) {
			$this->getOutput()->readOnlyPage();
			return;
		}

		# If user is blocked, s/he doesn't need to access this page
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $this->getUser()->mBlock );
		}

		$this->getOutput()->setPageTitle( $this->msg( 'deletebatch-title' ) );
		$cSF = new DeleteBatchForm( $par, $this->getTitle(), $this->getContext() );

		$request = $this->getRequest();
		$action = $request->getVal( 'action' );
		if ( 'success' == $action ) {
			/* do something */
		} elseif ( $request->wasPosted() && 'submit' == $action &&
			$user->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			$cSF->doSubmit();
		} else {
			$cSF->showForm();
		}
	}

	/**
	 * Adds a link to Special:DeleteBatch within the page
	 * Special:AdminLinks, if the 'AdminLinks' extension is defined
	 */
	static function addToAdminLinks( &$admin_links_tree ) {
		$general_section = $admin_links_tree->getSection( wfMessage( 'adminlinks_general' )->text() );
		$extensions_row = $general_section->getRow( 'extensions' );
		if ( is_null( $extensions_row ) ) {
			$extensions_row = new ALRow( 'extensions' );
			$general_section->addRow( $extensions_row );
		}
		$extensions_row->addItem( ALItem::newFromSpecialPage( 'DeleteBatch' ) );
		return true;
	}
}

/* the form for deleting pages */
class DeleteBatchForm {
	var $mUser, $mPage, $mFile, $mFileTemp;

	/**
	 * @var IContextSource|RequestContext
	 */
	protected $context;

	/**
	 * @var Title
	 */
	protected $title;

	/**
	 * @param $par
	 * @param $title
	 * @param $context IContextSource|RequestContext
	 */
	function __construct( $par, $title, $context ) {
		$this->context = $context;
		$request = $context->getRequest();

		$this->title = $title;
		$this->mMode = $request->getText( 'wpMode' );
		$this->mPage = $request->getText( 'wpPage' );
		$this->mReason = $request->getText( 'wpReason' );
		$this->mFile = $request->getFileName( 'wpFile' );
		$this->mFileTemp = $request->getFileTempName( 'wpFile' );
	}

	/**
	 * Show the form for deleting pages
	 *
	 * @param $errorMessage mixed: error message or null if there's no error
	 */
	function showForm( $errorMessage = false ) {
		$out = $this->context->getOutput();

		if ( $errorMessage ) {
			$out->setSubtitle( $this->context->msg( 'formerror' ) );
			$out->wrapWikiMsg( "<p class='error'>$1</p>\n", $errorMessage );
		}

		$out->addWikiMsg( 'deletebatch-help' );

		$tabindex = 1;

		$rows = array(

		array(
			Xml::label( $this->context->msg( 'deletebatch-as' )->text(), 'wpMode' ),
			$this->userSelect( 'wpMode', ++$tabindex )->getHtml()
		),
		array(
			Xml::label( $this->context->msg( 'deletebatch-page' )->text(), 'wpPage' ),
			$this->pagelistInput( 'wpPage', ++$tabindex )
		),
		array(
			$this->context->msg( 'deletebatch-or' )->parse(),
			'&#160;'
		),
		array(
			Xml::label( $this->context->msg( 'deletebatch-caption' )->text(), 'wpFile' ),
			$this->fileInput( 'wpFile', ++$tabindex )
		),
		array(
			'&#160;',
			$this->submitButton( 'wpdeletebatchSubmit', ++$tabindex )
		)

		);

		$form =

		Xml::openElement( 'form', array(
			'name' => 'deletebatch',
			'enctype' => 'multipart/form-data',
			'method' => 'post',
			'action' => $this->title->getLocalUrl( array( 'action' => 'submit' ) ),
		) );

		$form .= '<table>';

		foreach( $rows as $row ) {
			list( $label, $input ) = $row;
			$form .= "<tr><td class='mw-label'>$label</td>";
			$form .= "<td class='mw-input'>$input</td></tr>";
		}

		$form .= '</table>';

		$form .= Html::Hidden( 'title', $this->title );
		$form .= Html::Hidden( 'wpEditToken', $this->context->getUser()->getEditToken() );
		$form .= '</form>';
		$out->addHTML( $form );
	}

	function userSelect( $name, $tabindex ) {
		$options = array(
			$this->context->msg( 'deletebatch-select-script' )->text() => 'script',
			$this->context->msg( 'deletebatch-select-yourself' )->text() => 'you'
		);

		$select = new XmlSelect( $name, $name );
		$select->setDefault( $this->mMode );
		$select->setAttribute( 'tabindex', $tabindex );
		$select->addOptions( $options );

		return $select;
	}

	function pagelistInput( $name, $tabindex ) {
		$params = array(
			'tabindex' => $tabindex,
			'name' => $name,
			'id' => $name,
			'cols' => 40,
			'rows' => 10
		);

		return Xml::element( 'textarea', $params, $this->mPage, false );
	}

	function fileInput( $name, $tabindex ) {
		$params = array(
			'type' => 'file',
			'tabindex' => $tabindex,
			'name' => $name,
			'id' => $name,
			'value' => $this->mFile
		);

		return Xml::element( 'input', $params );
	}

	function submitButton( $name, $tabindex ) {
		$params = array(
			'tabindex' => $tabindex,
			'name' => $name,
		);

		return Xml::submitButton( $this->context->msg( 'deletebatch-delete' )->text(), $params );
	}

	/* wraps up multi deletes */
	function deleteBatch( $user = false, $line = '', $filename = null ) {
		global $wgUser;

		/* first, check the file if given */
		if ( $filename ) {
			/* both a file and a given page? not too much? */
			if ( '' != $this->mPage ) {
				$this->showForm( 'deletebatch-both-modes' );
				return;
			}
			if ( "text/plain" != mime_content_type( $filename ) ) {
				$this->showForm( 'deletebatch-file-bad-format' );
				return;
			}
			$file = fopen( $filename, 'r' );
			if ( !$file ) {
				$this->showForm( 'deletebatch-file-missing' );
				return;
			}
		}
		/* switch user if necessary */
		$OldUser = $wgUser;
		if ( 'script' == $this->mMode ) {
			$username = 'Delete page script';
			$wgUser = User::newFromName( $username );
			/* Create the user if necessary */
			if ( !$wgUser->getID() ) {
				$wgUser->addToDatabase();
			}
		}

		/* @todo run tests - run many tests */
		$dbw = wfGetDB( DB_MASTER );
		if ( $filename ) { /* if from filename, delete from filename */
			for ( $linenum = 1; !feof( $file ); $linenum++ ) {
				$line = trim( fgets( $file ) );
				if ( $line == false ) {
					break;
				}
				/* explode and give me a reason
				   the file should contain only "page title|reason"\n lines
				   the rest is trash
				*/
				$arr = explode( "|", $line );
				is_null( $arr[1] ) ? $reason = '' : $reason = $arr[1];
				$this->deletePage( $arr[0], $reason, $dbw, true, $linenum );
			}
		} else {
			/* run through text and do all like it should be */
			$lines = explode( "\n", $line );
			foreach ( $lines as $single_page ) {
				/* explode and give me a reason */
				$page_data = explode( "|", trim( $single_page ) );
				if ( count( $page_data ) < 2 )
					$page_data[1] = '';
				$this->deletePage( $page_data[0], $page_data[1], $dbw, false, 0, $OldUser );
			}
		}

		/* restore user back */
		if ( 'script' == $this->mMode ) {
			$wgUser = $OldUser;
		}

		$link_back = Linker::linkKnown(
			$this->title,
			$this->context->msg( 'deletebatch-link-back' )->escaped()
		);
		$this->context->getOutput()->addHTML( "<br /><b>" . $link_back . "</b>" );
	}

	/**
	 * Performs a single delete
	 * @$mode String - singular/multi
	 * @$linennum Integer - mostly for informational reasons
	 * @param $line
	 * @param string $reason
	 * @param DatabaseBase $db
	 * @param bool $multi
	 * @param int $linenum
	 * @param null|User $user
	 * @return bool
	 */
	function deletePage( $line, $reason = '', &$db, $multi = false, $linenum = 0, $user = null ) {
		global $wgUser;
		$page = Title::newFromText( $line );
			if ( is_null( $page ) ) { /* invalid title? */
				$this->context->getOutput()->addWikiMsg(
					'deletebatch-omitting-invalid', $line );
			if ( !$multi ) {
				if ( !is_null( $user ) ) {
					$wgUser = $user;
				}
			}
			return false;
		}

		// Check page existence
		$pageExists = $page->exists();

		// If it's a file, check file existence
		if ( NS_MEDIA == $page->getNamespace() ) {
			$page = Title::makeTitle( NS_FILE, $page->getDBkey() );
		}
		$localFile = null;
		$localFileExists = null;
		if ( $page->getNamespace() == NS_FILE ) {
			$localFile = wfLocalFile ( $page );
			$localFileExists = $localFile->exists();
		}

		if ( !$pageExists ) { /* no such page? */
			if ( !$localFileExists ) { /* no such file either? */
				$this->context->getOutput()->addWikiMsg(
					'deletebatch-omitting-nonexistent', $line );
				if ( !$multi ) {
					if ( !is_null( $user ) ) {
						$wgUser = $user;
					}
				}
				return false;
			} else { /* no such page, but there is such a file? */
				$this->context->getOutput()->addWikiMsg(
					'deletebatch-deleting-file-only', $line );
			}
		}

		$db->begin();
		/* this stuff goes like articleFromTitle in Wiki.php */
		// Delete the page; in the case of a file, this would be the File: description page
		if ( $pageExists ) {
			$art = WikiPage::factory( $page );
			/* what is the generic reason for page deletion?
			   something about the content, I guess...
			*/
			$art->doDeleteArticle( $reason );
		}
		// Delete the actual file, if applicable
		if ( $localFileExists ) {
			// This deletes all versions of the file. This does not create a
			// log entry to note the file's deletion. It's assumed that the log
			// entry was already created when the file's description page was
			// deleted, and now we are just cleaning up after a deletion script
			// that didn't finish the job by deleting the file too.
			$localFile->delete( $reason );
		}
		$db->commit();
		return true;
	}

	/* on submit */
	function doSubmit() {
		$out = $this->context->getOutput();

		$out->setPageTitle( $this->context->msg( 'deletebatch-title' ) );
		if ( !$this->mPage && !$this->mFileTemp ) {
			$this->showForm( 'deletebatch-no-page' );
			return;
		}

		if ( $this->mPage ) {
			$out->setSubTitle( $this->context->msg( 'deletebatch-processing-from-form' ) );
		} else {
			$out->setSubTitle( $this->context->msg( 'deletebatch-processing-from-file' ) );
		}

		$this->deleteBatch( $this->mUser, $this->mPage, $this->mFileTemp );
	}
}