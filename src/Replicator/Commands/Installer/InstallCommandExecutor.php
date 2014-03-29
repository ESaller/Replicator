<?php

namespace QueryR\Replicator\Commands\Installer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class InstallCommandExecutor {
	use ProgressTrait;

	private $input;
	private $output;

	/**
	 * @var SqlExecutor
	 */
	private $sqlExecutor;

	public function __construct( InputInterface $input, OutputInterface $output ) {
		$this->input = $input;
		$this->output = $output;
	}

	public function run() {
		$this->startInstallation();

		$this->sqlExecutor = new SqlExecutor( $this->input, $this->output );

		try {
			$this->createConfigFile();
			$this->createDatabase();
			$this->reportInstallationSuccess();
		}
		catch ( InstallationException $ex ) {
			$this->reportInstallationFailure( $ex->getMessage() );
		}
	}

	private function startInstallation() {
		$this->output->writeln( '<info>Installing QueryR Replicator.</info>' );
	}

	private function createConfigFile() {
		$this->writeProgress( 'Creating config file' );

		$writeResult = @file_put_contents(
			__DIR__ . '/../../replicator.json',
			json_encode( $this->createConfigData(), JSON_PRETTY_PRINT )
		);

		if ( $writeResult === false ) {
			throw new InstallationException( 'Could not write the config file' );
		}

		$this->writeProgressEnd();
	}

	private function createConfigData() {
		return array(
			'user' => $this->input->getArgument( 'user' ),
			'password' => $this->input->getArgument( 'password' ),
			'database' => $this->input->getArgument( 'database' ),
		);
	}

	private function createDatabase() {
		$database = $this->input->getArgument( 'database' );
		$user = $this->input->getArgument( 'user' );
		$password = $this->input->getArgument( 'password' );

		$this->sqlExecutor->exec(
			"CREATE DATABASE $database;",
			'Creating database'
		);

		$this->sqlExecutor->exec(
			"CREATE USER '$user'@'localhost' IDENTIFIED BY '$password';",
			'Creating database user'
		);

		$this->sqlExecutor->exec(
			"GRANT ALL PRIVILEGES ON $database.* TO '$user'@'localhost';",
			'Assigning rights to database user'
		);
	}

	private function reportInstallationFailure( $failureMessage ) {
		$this->writeProgressEnd( 'failed!' );
		$this->writeError( 'Error! Installation of QueryR Replicator failed.' );
		$this->writeError( 'Reason: ' . $failureMessage );
	}

	private function reportInstallationSuccess() {
		$tag = 'bg=green;fg=black';

		$this->output->writeln(
			"<$tag>Installation of QueryR Replicator has completed successfully.</$tag>"
		);
	}

}
