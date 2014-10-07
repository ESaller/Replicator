<?php

namespace Queryr\Replicator\EntitySource\JsonDump;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class JsonDumpReader {

	/**
	 * @var string
	 */
	private $dumpFile;

	/**
	 * @var resource
	 */
	private $handle;

	/**
	 * @param string $dumpFilePath
	 */
	public function __construct( $dumpFilePath ) {
		$this->dumpFile = $dumpFilePath;

		$this->initReader();
	}

	private function initReader() {
		$this->handle = fopen( $this->dumpFile, 'r' );
	}

	public function __destruct() {
		$this->closeReader();
	}

	private function closeReader() {
		fclose( $this->handle );
	}

	public function rewind() {
		$this->closeReader();
		$this->initReader();
	}

	/**
	 * @return string|null
	 */
	public function nextJsonLine() {
		do {
			$line = fgets( $this->handle );

			if ( $line === false ) {
				return null;
			}

			if ( $line{0} === '{' ) {
				return rtrim( $line, ",\n\r" );
			}
		} while ( true );

		return null;
	}

	/**
	 * @return int
	 */
	public function getPosition() {
		if ( PHP_INT_SIZE < 8 ) {
			throw new \RuntimeException( 'Cannot reliably get the file position on 32bit PHP' );
		}

		return ftell( $this->handle );
	}

	/**
	 * @param int $position
	 */
	public function seekToPosition( $position ) {
		fseek( $this->handle, $position );
	}

}
