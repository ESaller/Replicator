<?php

namespace Wikibase\DumpReader\XmlReader;

use Iterator;
use IteratorAggregate;
use Wikibase\DumpReader\DumpReader;
use Wikibase\DumpReader\DumpReaderException;
use XMLReader;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DumpXmlReader extends DumpReader {

	/**
	 * @var XMLReader
	 */
	private $xmlReader;

	/**
	 * @var string
	 */
	private $dumpFile;

	public function __construct( $dumpFile ) {
		$this->dumpFile = $dumpFile;

		$this->initReader();
	}

	private function initReader() {
		$this->xmlReader = new XMLReader();
		$this->xmlReader->open( $this->dumpFile );
	}

	public function __destruct() {
		$this->closeReader();
	}

	private function closeReader() {
		$this->xmlReader->close();
	}

	/**
	 * @see DumpReader::rewind
	 */
	public function rewind() {
		$this->closeReader();
		$this->initReader();
	}

	/**
	 * @see DumpReader::nextEntityJson
	 *
	 * @return string|null
	 * @throws DumpReaderException
	 */
	public function nextEntityJson() {
		$revisionNode = $this->nextRevisionNode();

		if ( $revisionNode === null ) {
			return null;
		}

		while ( !$revisionNode->isEntity() ) {
			$revisionNode = $this->nextRevisionNode();

			if ( $revisionNode === null ) {
				return null;
			}
		}

		return $revisionNode->getEntityJson();
	}

	/**
	 * @return RevisionNode|null
	 */
	private function nextRevisionNode() {
		while ( !$this->isPageNode() ) {
			$this->xmlReader->read();

			if ( $this->xmlReader->nodeType === XMLReader::NONE ) {
				return null;
			}
		}

		$pageNode = new PageNode( $this->xmlReader->expand() );

		$revisionNode = $pageNode->getRevisionNode();
		$this->xmlReader->next();
		return $revisionNode;
	}

	private function isPageNode() {
		return $this->xmlReader->nodeType === XMLReader::ELEMENT && $this->xmlReader->name === 'page';
	}

}