<?php

namespace Queryr\Replicator\Importer;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ImportStats {

	private $count = 0;
	private $errorCount = 0;
	private $durationInMs;

	public function recordSuccess() {
		$this->count++;
	}

	public function recordError( \Exception $ex ) {
		$this->count++;
		$this->errorCount++;
	}

	public function setDuration( float $durationInMs ) {
		$this->durationInMs = $durationInMs;
	}

	public function getEntityCount(): int {
		return $this->count;
	}

	public function getErrorCount(): int {
		return $this->errorCount;
	}

	public function getSuccessCount(): int {
		return $this->count - $this->errorCount;
	}

	public function getErrorRatio(): float {
		if ( $this->count === 0 ) {
			return 0;
		}

		return $this->errorCount / $this->count * 100;
	}

	public function getDurationInMs(): float {
		return $this->durationInMs;
	}

}