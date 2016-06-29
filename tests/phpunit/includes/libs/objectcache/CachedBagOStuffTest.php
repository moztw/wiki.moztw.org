<?php

/**
 * @group BagOStuff
 */
class CachedBagOStuffTest extends PHPUnit_Framework_TestCase {

	public function testGetFromBackend() {
		$backend = new HashBagOStuff;
		$cache = new CachedBagOStuff( $backend );

		$backend->set( 'foo', 'bar' );
		$this->assertEquals( 'bar', $cache->get( 'foo' ) );

		$backend->set( 'foo', 'baz' );
		$this->assertEquals( 'bar', $cache->get( 'foo' ), 'cached' );
	}

	public function testSetAndDelete() {
		$backend = new HashBagOStuff;
		$cache = new CachedBagOStuff( $backend );

		for ( $i = 0; $i < 10; $i++ ) {
			$cache->set( "key$i", 1 );
			$this->assertEquals( 1, $cache->get( "key$i" ) );
			$this->assertEquals( 1, $backend->get( "key$i" ) );
			$cache->delete( "key$i" );
			$this->assertEquals( false, $cache->get( "key$i" ) );
			$this->assertEquals( false, $backend->get( "key$i" ) );
		}
	}

	public function testWriteCacheOnly() {
		$backend = new HashBagOStuff;
		$cache = new CachedBagOStuff( $backend );

		$cache->set( 'foo', 'bar', 0, CachedBagOStuff::WRITE_CACHE_ONLY );
		$this->assertEquals( 'bar', $cache->get( 'foo' ) );
		$this->assertFalse( $backend->get( 'foo' ) );

		$cache->set( 'foo', 'old' );
		$this->assertEquals( 'old', $cache->get( 'foo' ) );
		$this->assertEquals( 'old', $backend->get( 'foo' ) );

		$cache->set( 'foo', 'new', 0, CachedBagOStuff::WRITE_CACHE_ONLY );
		$this->assertEquals( 'new', $cache->get( 'foo' ) );
		$this->assertEquals( 'old', $backend->get( 'foo' ) );

		$cache->delete( 'foo', CachedBagOStuff::WRITE_CACHE_ONLY );
		$this->assertEquals( 'old', $cache->get( 'foo' ) ); // Reloaded from backend
	}

	public function testCacheBackendMisses() {
		$backend = new HashBagOStuff;
		$cache = new CachedBagOStuff( $backend );

		// First hit primes the cache with miss from the backend
		$this->assertEquals( false, $cache->get( 'foo' ) );

		// Change the value in the backend
		$backend->set( 'foo', true );

		// Second hit returns the cached miss
		$this->assertEquals( false, $cache->get( 'foo' ) );

		// But a fresh value is read from the backend
		$backend->set( 'bar', true );
		$this->assertEquals( true, $cache->get( 'bar' ) );
	}
}
