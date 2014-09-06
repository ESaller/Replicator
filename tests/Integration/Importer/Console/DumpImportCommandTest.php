<?php

namespace Tests\Queryr\Replicator\Importer\Console;

use Doctrine\DBAL\DriverManager;
use Queryr\Replicator\Cli\Command\DumpImportCommand;
use Queryr\Replicator\ServiceFactory;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Queryr\Replicator\Integration\TestEnvironment;

/**
 * @covers Queryr\Replicator\Cli\Command\DumpImportCommand
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DumpImportCommandTest extends \PHPUnit_Framework_TestCase {

	public function testEntityIdInOutput() {
		$output = $this->getOutputForArgs( [
			'file' => 'tests/data/simple/one-item.xml'
		] );

		$this->assertContains( 'Q15831780', $output );
		$this->assertContains( 'Entity imported', $output );
	}

	private function getOutputForArgs( array $args ) {
		$commandTester = $this->newCommandTester();

		$commandTester->execute( $args );

		return $commandTester->getDisplay();
	}

	private function newCommandTester() {
		$command = new DumpImportCommand();
		$command->setServiceFactory( TestEnvironment::newInstance()->getFactory() );

		return new CommandTester( $command );
	}

	public function testResume() {
		$output = $this->getOutputForArgs( [
			'file' => 'tests/data/simple/five-items.xml',
			'--continue' => 'Q15826086'
		] );

		$this->assertNotContains( 'Q15831779', $output );
		$this->assertNotContains( 'Q15831780', $output );
		$this->assertContains( 'Q15826087', $output );
		$this->assertContains( 'Q15826088', $output );
		$this->assertContains( 'Entities: 2', $output );
	}

}