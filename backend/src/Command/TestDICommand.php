<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Plugin\PluginService;
use App\Service\PluginService as ZipPluginService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test-di',
    description: 'Test dependency injection configuration'
)]
class TestDICommand extends Command
{
    public function __construct(
        private readonly PluginService $commandPluginService,
        private readonly ZipPluginService $zipPluginService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Testing DI configuration...');
        
        $output->writeln('✓ App\Service\Plugin\PluginService (command execution) injected successfully');
        $output->writeln('✓ App\Service\PluginService (zip management) injected successfully');
        
        $output->writeln('');
        $output->writeln('Constructor parameters for command execution service:');
        
        $reflection = new \ReflectionClass($this->commandPluginService);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        foreach ($parameters as $param) {
            $type = $param->getType() ? $param->getType()->getName() : 'mixed';
            $output->writeln("  - {$param->getName()}: {$type}");
        }
        
        $output->writeln('');
        $output->writeln('DI configuration is working correctly!');
        
        return Command::SUCCESS;
    }
}
