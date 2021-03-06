<?php

namespace Queryr\Replicator\Importer;

use Iterator;
use Queryr\Replicator\Model\EntityPage;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PagesImporter {

	private $importer;
	private $statsReporter;
	private $onAborted;

	private $shouldStop = false;

	public function __construct( PageImporter $importer, StatsReporter $statsReporter, callable $onAborted = null ) {
		$this->importer = $importer;
		$this->statsReporter = $statsReporter;
		$this->onAborted = $onAborted;
	}

	public function importPages( Iterator $entityPageIterator ) {
		$startTime = microtime( true );

		$reporter = new StatsTrackingReporter( $this->importer->getReporter() );

		$this->importer->setReporter( $reporter );

		$this->runImportLoop( $entityPageIterator );

		$stats = $reporter->getStats();
		$stats->setDuration( microtime( true ) - $startTime );
		$this->statsReporter->reportStats( $stats );
	}

	private function runImportLoop( Iterator $entityPageIterator ) {
		$this->shouldStop = false;

		/**
		 * @var EntityPage $entityPage
		 */
		foreach ( $entityPageIterator as $entityPage ) {
			$this->importer->import( $entityPage );

			pcntl_signal_dispatch();
			if ( $this->shouldStop ) {
				if ( $this->onAborted !== null ) {
					call_user_func( $this->onAborted, $entityPage->getTitle() );
				}
				return;
			}
		}
	}

	public function stop() {
		$this->shouldStop = true;
	}

}

