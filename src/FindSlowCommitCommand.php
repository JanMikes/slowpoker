<?php declare (strict_types=1);

namespace JanMikes\Slowpoker;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class FindSlowCommitCommand extends Command
{
	protected function configure(): void
	{
		$this->setName('find');
		$this->setDescription('Find commit that slowed application');

		$this->addOption('bad', null, InputOption::VALUE_REQUIRED, 'Bad revision (for git bisect)');
		$this->addOption('good', null, InputOption::VALUE_REQUIRED, 'Good revision (for git bisect)');
		$this->addOption('maxSpeed', null, InputOption::VALUE_REQUIRED, 'Max allowed response time, if not met (is higher) will exit as error');
		$this->addOption('requests', null, InputOption::VALUE_REQUIRED, 'Number of HTTP requests made', 1000);
		$this->addOption('url', null, InputOption::VALUE_REQUIRED, 'URL to test speed with');
		$this->addOption('cacheDir', null, InputOption::VALUE_REQUIRED, 'Relative path to cache directory');
		$this->addOption('workingDirectory', null, InputOption::VALUE_REQUIRED, 'Working directory of the repository, useful when running outside of it and therefore can not be detected automatically');
	}


	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$goodRevision = $this->getGoodRevision($input);
		$badRevision = $this->getBadRevision($input);

		return 0;
	}


	private function getBadRevision(InputInterface $input): string
	{
		/** @var string|null $badRevision */
		$badRevision = $input->getOption('bad');

		if ($badRevision === null) {
			throw new \LogicException('Please provide "bad" option.');
		}

		return $badRevision;
	}


	private function getGoodRevision(InputInterface $input): string
	{
		/** @var string|null $goodRevision */
		$goodRevision = $input->getOption('good');

		if ($goodRevision === null) {
			throw new \LogicException('Please provide "good" option.');
		}

		return $goodRevision;
	}
}
