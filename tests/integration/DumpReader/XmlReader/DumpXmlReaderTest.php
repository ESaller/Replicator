<?php

namespace Tests\Wikibase\DumpReader;

use Wikibase\DumpReader\DumpReader;
use Wikibase\DumpReader\Page;
use Wikibase\DumpReader\Revision;
use Wikibase\DumpReader\XmlReader\DumpXmlReader;

/**
 * @covers Wikibase\DumpReader\XmlReader\DumpXmlReader
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DumpXmlReaderTest extends \PHPUnit_Framework_TestCase {

	public function testGivenFileWithNoEntities_nullIsReturned() {

		$reader = $this->newReaderForFile( 'simple/empty.xml' );

		$this->assertNull( $reader->nextEntityPage() );
	}

	private function newReaderForFile( $fileName ) {
		return new DumpXmlReader( $this->getFilePath( $fileName ) );
	}

	private function getFilePath( $fileName ) {
		return __DIR__ . '/../../../data/' . $fileName;
	}

	private function assertFindsAnotherEntity( DumpReader $reader ) {
		$entityPage = $reader->nextEntityPage();
		$this->assertIsEntityPage( $entityPage );
	}

	private function assertIsEntityPage( $entityPage ) {
		/**
		 * @var Page $entityPage
		 */
		$this->assertInstanceOf( 'Wikibase\DumpReader\Page', $entityPage );

		$revision = $entityPage->getRevision();

		$this->assertTrue( $revision->hasEntityModel() );
		$this->assertHasEntityJson( $revision );
	}

	private function assertHasEntityJson( Revision $revision ) {
		$entityArray = json_decode( $revision->getText(), true );
		$this->assertInternalType( 'array', $entityArray );

		$this->assertArrayHasKey( 'entity', $entityArray );
	}

	public function testGivenFileWithOneEntity_oneEntityIsFound() {
		$reader = $this->newReaderForFile( 'simple/one-item.xml' );

		$this->assertFindsAnotherEntity( $reader );
		$this->assertNull( $reader->nextEntityPage() );
	}

	public function testRewind() {
		$reader = $this->newReaderForFile( 'simple/one-item.xml' );

		$this->assertFindsAnotherEntity( $reader );
		$this->assertNull( $reader->nextEntityPage() );

		$reader->rewind();

		$this->assertFindsAnotherEntity( $reader );
		$this->assertNull( $reader->nextEntityPage() );
	}

	public function testGivenFileWithTwoEntities_twoEntitiesAreFound() {
		$reader = $this->newReaderForFile( 'simple/two-items.xml' );

		$this->assertFindsAnotherEntity( $reader );
		$this->assertFindsAnotherEntity( $reader );
		$this->assertNull( $reader->nextEntityPage() );
	}

	public function testGivenEntityAmongstNonEntities_itemIsFound() {
		$reader = $this->newReaderForFile( 'simple/item-amongst-wikitext.xml' );

		$this->assertFindsAnotherEntity( $reader );
		$this->assertNull( $reader->nextEntityPage() );
	}

	public function testFetchedIteratorIterationWorks() {
		$reader = $this->newReaderForFile( 'simple/two-items.xml' )->getIterator();

		$this->assertCount( 2, iterator_to_array( $reader ) );
		$this->assertContainsOnlyInstancesOf( 'Wikibase\DumpReader\Page', $reader );
	}

	public function testCanUseAsTraversable() {
		$reader = $this->newReaderForFile( 'simple/two-items.xml' );

		foreach ( $reader as $page ) {
			$this->assertIsEntityPage( $page );
		}
	}

	public function testGivenManyRevisions_allPropertiesAreFound() {
		$reader = $this->newReaderForFile( 'big/5341-revs-3-props.xml' );

		$propertyCount = 0;

		while ( $page = $reader->nextEntityPage() ) {
			$this->assertIsEntityPage( $page );

			if ( $this->isPropertyPage( $page ) ) {
				$propertyCount++;
			}
		}

		$this->assertEquals( 3, $propertyCount );
	}

	private function isPropertyPage( Page $page ) {
		$array = json_decode( $page->getRevision()->getText(), true );
		return $array['entity'][0] === 'property';
	}

	public function testGivenItemWithoutRevision_exceptionInThrown() {
		$reader = $this->newReaderForFile( 'invalid/item-without-revision.xml' );

		$this->setExpectedException( 'Wikibase\DumpReader\DumpReaderException' );
		$reader->nextEntityPage();
	}

}