<?php

namespace Tests\Queryr\Replicator\EntitySource;

use Queryr\Replicator\EntitySource\BatchingEntityPageFetcher;
use Queryr\Replicator\EntitySource\EntityPageBatchFetcher;
use Queryr\Replicator\Model\EntityPage;

/**
 * @covers Queryr\Replicator\EntitySource\BatchingEntityPageFetcher
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class BatchingEntityPageFetcherTest extends \PHPUnit_Framework_TestCase {

	public function testGivenNoPagesToFetch_noneAreReturned() {
		$fetcher = $this->newFetcherForPages( [] );

		$this->assertSame( [], $fetcher->fetchNext( 10 ) );
		$this->assertSame( [], $fetcher->fetchNext( 10 ) );
	}

	private function newFetcherForPages( array $entityIds ) {
		return new BatchingEntityPageFetcher( new FakeEntityPageBatchFetcher(), $entityIds );
	}

	public function testRewind() {
		$fetcher = $this->newFetcherForPages( [ 'Q1', 'Q2', 'Q3' ] );

		$fetcher->fetchNext( 2 );
		$fetcher->rewind();
		$this->assertSame( [ 'Q1', 'Q2' ], $fetcher->fetchNext( 2 ) );
	}

	public function testProgressionThroughArray() {
		$fetcher = $this->newFetcherForPages( [ 'Q1', 'Q2', 'Q3' ] );

		$this->assertSame( [ 'Q1', 'Q2' ], $fetcher->fetchNext( 2 ) );
		$this->assertSame( [ 'Q3' ], $fetcher->fetchNext( 2 ) );
		$this->assertSame( [], $fetcher->fetchNext( 2 ) );
	}

	public function testWhenAtInitialPosition_addingNewPagesWorks() {
		$fetcher = $this->newFetcherForPages( [ 'Q1', 'Q2', 'Q3' ] );

		$fetcher->addPagesToFetch( [ 'Q4' ] );

		$this->assertSame( [ 'Q1', 'Q2', 'Q3', 'Q4' ], $fetcher->fetchNext( 5 ) );
	}

	public function testWhenPartiallyIterated_addingNewPagesWorks() {
		$fetcher = $this->newFetcherForPages( [ 'Q1', 'Q2', 'Q3' ] );

		$fetcher->fetchNext( 2 );
		$fetcher->addPagesToFetch( [ 'Q4' ] );

		$this->assertSame( [ 'Q3', 'Q4' ], $fetcher->fetchNext( 2 ) );
	}

	public function testWhenFetchedBeyondTheLastPage_newlyAddedPagesAreStillFetchedOnNextCall() {
		$fetcher = $this->newFetcherForPages( [ 'Q1', 'Q2' ] );

		$fetcher->fetchNext( 3 );
		$fetcher->addPagesToFetch( [ 'Q3', 'Q4' ] );

		$this->assertSame( [ 'Q3', 'Q4' ], $fetcher->fetchNext( 3 ) );
	}

	public function testWhenPartiallyIterated_additionOfDuplicatesGetsIgnored() {
		$fetcher = $this->newFetcherForPages( [ 'Q1', 'Q2', 'Q3' ] );

		$fetcher->fetchNext( 2 );
		$fetcher->addPagesToFetch( [ 'Q1', 'Q4', 'Q3' ] );

		$this->assertSame( [ 'Q3', 'Q4' ], $fetcher->fetchNext( 3 ) );
	}

}

class FakeEntityPageBatchFetcher implements EntityPageBatchFetcher {

	/**
	 * @param string[] $entityIds
	 *
	 * @return EntityPage[]
	 */
	public function fetchEntityPages( array $entityIds ) {
		return $entityIds;
	}

}