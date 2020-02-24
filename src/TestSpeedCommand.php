<?php declare (strict_types=1);

namespace Glami\SpeedTest;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class TestSpeedCommand extends Command
{
	protected function configure(): void
	{
		$this->setName('test');
	}


	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$output->writeln('test');

		return 0;
	}
}
