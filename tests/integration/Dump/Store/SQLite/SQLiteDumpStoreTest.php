<?php

namespace Tests\Wikibase\Dump\Store;

use InvalidArgumentException;
use PDO;
use Wikibase\Database\Escaper;
use Wikibase\Database\PDO\PDOTableBuilder;
use Wikibase\Database\Schema\TableBuilder;
use Wikibase\Database\SQLite\SQLiteFieldSqlBuilder;
use Wikibase\Database\SQLite\SQLiteIndexSqlBuilder;
use Wikibase\Database\SQLite\SQLiteTableSqlBuilder;
use Wikibase\Database\TableNameFormatter;
use Wikibase\Dump\Store\DumpStore;
use Wikibase\Dump\Store\SQLite\SQLiteDumpStore;

/**
 * @covers Wikibase\Dump\Store\SQLite\SQLiteDumpStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SQLiteDumpStoreTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var DumpStore
	 */
	private $store;

	/**
	 * @var TableBuilder
	 */
	private $tableBuilder;

	public function setUp() {
		$pdo = new PDO( 'sqlite::memory:' );
		$escaper = new PDOEscaper( $pdo );
		$tableNameFormatter = new PrefixingTableNameFormatter( 'test_' );

		$this->tableBuilder = new PDOTableBuilder(
			$pdo,
			new SQLiteTableSqlBuilder(
				$escaper,
				$tableNameFormatter,
				new SQLiteFieldSqlBuilder( $escaper ),
				new SQLiteIndexSqlBuilder( $escaper, $tableNameFormatter )
			)
		);

		$this->store = new SQLiteDumpStore( $this->tableBuilder );
	}

	public function testInstallationAndRemoval() {
		$this->store->install();

		$this->assertTrue( $this->tableBuilder->tableExists( 'test_entities' ) );

		$this->store->uninstall();

		$this->assertFalse( $this->tableBuilder->tableExists( 'test_entities' ) );
	}

}

class PDOEscaper implements Escaper {

	protected $pdo;

	public function __construct( PDO $pdo ) {
		$this->pdo = $pdo;
	}

	/**
	 * @see ValueEscaper::getEscapedValue
	 *
	 * @param mixed $value
	 *
	 * @return string The escaped value
	 */
	public function getEscapedValue( $value ) {
		return $this->pdo->quote( $value );
	}

	/**
	 * @see IdentifierEscaper::getEscapedIdentifier
	 *
	 * @param mixed $identifier
	 *
	 * @return string The escaped identifier
	 */
	public function getEscapedIdentifier( $identifier ) {
		return '`' . str_replace( '`', '``', $identifier ) . '`';
	}

}

class PrefixingTableNameFormatter implements TableNameFormatter {

	/**
	 * @param string $prefix
	 * @throws InvalidArgumentException
	 */
	public function __construct( $prefix ) {
		if ( !is_string( $prefix ) ) {
			throw new InvalidArgumentException( '$prefix should be a string' );
		}

		$this->prefix = $prefix;
	}

	/**
	 * @see TableName::formatTableName
	 *
	 * @param string $tableName
	 *
	 * @return string
	 */
	public function formatTableName( $tableName ) {
		return $this->prefix . $tableName;
	}

}