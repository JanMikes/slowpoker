<?php declare (strict_types=1);

namespace Glami\SpeedTest;

use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class TestSpeedCommand extends Command
{
	/**
	 * @var string
	 */
	private const WORKING_COPIES_PATH = '/home/users/klarka/wc';


	protected function configure(): void
	{
		$this->setName('test-speed');

		$this->addOption('maxSpeed', null, InputOption::VALUE_REQUIRED, 'Max allowed response, if not met (is higher) will exit as error');
		$this->addOption('wc', null, InputOption::VALUE_REQUIRED, 'Number of working copy to test');
		$this->addOption('requests', null, InputOption::VALUE_REQUIRED, 'Number of HTTP requests made', 2000);
		$this->addOption('url', null, InputOption::VALUE_REQUIRED, 'URL to test speed with (glami.cz will be replaced with your working copy)', 'http://www.glami.cz/damske-baleriny/?original');
		$this->addOption('cacheDir', null, InputOption::VALUE_REQUIRED, 'Relative path to cache directory', 'temp/cache');
	}


	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$wc = $input->getOption('wc');
		$maxSpeed = $input->getOption('maxSpeed');
		$numberOfRequests = $input->getOption('requests');
		$cacheDirectory = $input->getOption('cacheDir');

		if ($wc === null) {
			throw new \LogicException('Please provide wc option.');
		}

		if ($maxSpeed === null) {
			throw new \LogicException('Please provide maxSpeed option.');
		}

		// http://www.glami.cz/damske-baleriny/?original -> http://www.17.devklarka.cz/damske-baleriny/?original
		$url = str_replace('glami.cz', $wc . '.devklarka.cz', $input->getOption('url'));

		$workingDirectory = self::WORKING_COPIES_PATH . '/' . $wc;

		$this->clearCache($workingDirectory . '/' . $cacheDirectory);
		$this->optimizeComposerAutoload($workingDirectory);

		$speed = $this->getMeasuredSpeed($numberOfRequests, $url, $workingDirectory);
		$output->writeln(sprintf('Measured %s ms', $speed));

		$this->discardGitChanges($workingDirectory);

		if ((int) $speed > (int) $maxSpeed) {
			return 1;
		}

		return 0;
	}


	private function getMeasuredSpeed(string $numberOfRequests, string $url, string $workingDirectory): string
	{
		$speedTestProcess = new Process([
			'ab',
			'-c1',
			'-n', $numberOfRequests,
			'-S',
			'-k',
			'-H', 'XHttpTest: 1',
			'-H', 'X-No-Debug: 1',
			'-H', 'User-Agent: GlamiTest',
			$url,
		], $workingDirectory, null, null, null);

		$speedTestProcess->mustRun();

		$matches = Strings::match($speedTestProcess->getOutput(), '/Total:[\s]+(?<speed>\d+)/');

		if (!isset($matches['speed'])) {
			throw new \RuntimeException('Could not detect speed from AB output');
		}

		return $matches['speed'];
	}


	private function clearCache(string $cacheDirectory): void
	{
		FileSystem::delete($cacheDirectory);
	}


	private function optimizeComposerAutoload(string $workingDirectory): void
	{
		$process = new Process([
			'composer',
			'dump-autoload',
			'-o',
			'-a'
		], $workingDirectory);

		$process->mustRun();
	}


	private function discardGitChanges(string $workingDirectory): void
	{
		$process = new Process([
			'git',
			'reset',
			'--hard',
		], $workingDirectory);

		$process->mustRun();
	}
}
