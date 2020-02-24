<?php declare (strict_types=1);

namespace JanMikes\Slowpoker;

use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class TestTimeCommand extends Command
{
	protected function configure(): void
	{
		$this->setName('test');

		$this->addOption('maxSpeed', null, InputOption::VALUE_REQUIRED, 'Max allowed response time, if not met (is higher) will exit as error');
		$this->addOption('requests', null, InputOption::VALUE_REQUIRED, 'Number of HTTP requests made', 1000);
		$this->addOption('url', null, InputOption::VALUE_REQUIRED, 'URL to test speed with');
		$this->addOption('cacheDir', null, InputOption::VALUE_REQUIRED, 'Relative path to cache directory');
		$this->addOption('workingDirectory', null, InputOption::VALUE_REQUIRED, 'Working directory of the repository, useful when running outside of it and therefore can not be detected automatically');
	}


	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$maxSpeed = $this->getMaxSpeedOptionValue($input);
		$url = $this->getUrlOptionValue($input);

		/** @var string $numberOfRequests */
		$numberOfRequests = $input->getOption('requests');

		/** @var string|null $cacheDirectory */
		$cacheDirectory = $input->getOption('cacheDir');

		/** @var string|null $workingDirectory */
		$workingDirectory = $input->getOption('workingDirectory');

		if ($cacheDirectory !== null) {
			$this->clearCache($cacheDirectory, $workingDirectory);
		}

		$this->optimizeComposerAutoload($workingDirectory);

		$speed = $this->getMeasuredSpeed($numberOfRequests, $url);
		$output->writeln(sprintf('Measured speed: %d ms', $speed));

		$this->discardGitChanges($workingDirectory);

		if ($speed > $maxSpeed) {
			return 1;
		}

		return 0;
	}


	private function getMeasuredSpeed(string $numberOfRequests, string $url): int
	{
		$speedTestProcess = new Process([
			'ab',
			'-c1',
			'-n', $numberOfRequests,
			'-S',
			'-k',
			'-H', 'XHttpTest: 1', // TODO: has to be parametrized
			'-H', 'X-No-Debug: 1', // TODO: has to be parametrized
			'-H', 'User-Agent: GlamiTest', // TODO: has to be parametrized
			$url,
		]);

		// Apache Benchmark can take long time to complete, depending on number of requests
		$speedTestProcess->setTimeout(null);
		$speedTestProcess->mustRun();

		$matches = Strings::match($speedTestProcess->getOutput(), '/Total:[\s]+(?<speed>\d+)/');

		if (!isset($matches['speed'])) {
			throw new \RuntimeException('Could not detect speed from AB output');
		}

		return (int) $matches['speed'];
	}


	private function clearCache(string $cacheDirectory, ?string $workingDirectory): void
	{
		if ($workingDirectory) {
			$cacheDirectory = $workingDirectory . '/' . $cacheDirectory;
		}

		FileSystem::delete($cacheDirectory);
	}


	private function optimizeComposerAutoload(?string $workingDirectory): void
	{
		$process = new Process([
			'composer',
			'dump-autoload',
			'-o',
			'-a'
		], $workingDirectory);

		$process->mustRun();
	}


	private function discardGitChanges(?string $workingDirectory): void
	{
		$process = new Process([
			'git',
			'reset',
			'--hard',
		], $workingDirectory);

		$process->mustRun();
	}


	private function getMaxSpeedOptionValue(InputInterface $input): int
	{
		/** @var string|null $maxSpeed */
		$maxSpeed = $input->getOption('maxSpeed');

		if ($maxSpeed === null) {
			throw new \LogicException('Please provide maxSpeed option.');
		}

		return (int) $maxSpeed;
	}


	private function getUrlOptionValue(InputInterface $input): string
	{
		/** @var string|null $url */
		$url = $input->getOption('url');

		if ($url === null) {
			throw new \LogicException('Please provide url option.');
		}

		return $url;
	}
}
