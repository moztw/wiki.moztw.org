<?php

/**
 * Class representing a list of resources for one gadget, basically a wrapper
 * around the Gadget class.
 */
class GadgetResourceLoaderModule extends ResourceLoaderWikiModule {
	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var Gadget
	 */
	private $gadget;

	/**
	 * Creates an instance of this class
	 *
	 * @param array $options
	 */
	public function __construct( array $options ) {
		$this->id = $options['id'];
	}

	/**
	 * @return Gadget instance this module is about
	 */
	private function getGadget() {
		if ( !$this->gadget ) {
			try {
				$this->gadget = GadgetRepo::singleton()->getGadget( $this->id );
			} catch ( InvalidArgumentException $e ) {
				// Fallback to a placeholder object...
				$this->gadget = Gadget::newEmptyGadget( $this->id );
			}
		}

		return $this->gadget;
	}

	/**
	 * Overrides the function from ResourceLoaderWikiModule class
	 * @param ResourceLoaderContext $context
	 * @return array
	 */
	protected function getPages( ResourceLoaderContext $context ) {
		$gadget = $this->getGadget();
		$pages = array();

		foreach ( $gadget->getStyles() as $style ) {
			$pages[$style] = array( 'type' => 'style' );
		}

		if ( $gadget->supportsResourceLoader() ) {
			foreach ( $gadget->getScripts() as $script ) {
				$pages[$script] = array( 'type' => 'script' );
			}
		}

		return $pages;
	}

	/**
	 * Overrides ResourceLoaderModule::getDependencies()
	 * @param $context ResourceLoaderContext
	 * @return Array: Names of resources this module depends on
	 */
	public function getDependencies( ResourceLoaderContext $context = null ) {
		return $this->getGadget()->getDependencies();
	}

	/**
	 * Overrides ResourceLoaderModule::getPosition()
	 * @return String: 'bottom' or 'top'
	 */
	public function getPosition() {
		return $this->getGadget()->getPosition();
	}

	public function getMessages() {
		return $this->getGadget()->getMessages();
	}

	public function getTargets() {
		return $this->getGadget()->getTargets();
	}
}
