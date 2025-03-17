<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\SearchEngine\TypeSenseClient;

#[AsCommand(name: 'app:reset-ts')]
class ResetTypeSenseCommand extends Command
{
    public function __construct(
        private readonly TypeSenseClient $client,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->client->collections()['cells']->delete(['truncate' => true]);

        dump($result);
        dd($this->client->collections());

        return Command::SUCCESS;
    }
}
