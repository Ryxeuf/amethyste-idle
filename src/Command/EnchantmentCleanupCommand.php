<?php

namespace App\Command;

use App\GameEngine\Item\EnchantmentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:enchantment:cleanup',
    description: 'Supprime les enchantements expires de la base de donnees',
)]
class EnchantmentCleanupCommand extends Command
{
    public function __construct(
        private readonly EnchantmentManager $enchantmentManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $removed = $this->enchantmentManager->removeExpired();
        $this->entityManager->flush();

        if ($removed > 0) {
            $io->success(sprintf('%d enchantement(s) expire(s) supprime(s).', $removed));
        }

        return Command::SUCCESS;
    }
}
