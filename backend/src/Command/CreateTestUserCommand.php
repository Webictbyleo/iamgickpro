<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-user',
    description: 'Create a test user for development'
)]
class CreateTestUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Check if test user already exists
            $userRepository = $this->entityManager->getRepository(User::class);
            $existingUser = $userRepository->findOneBy(['email' => 'test@example.com']);
            
            if ($existingUser) {
                $io->info('Test user already exists with email: test@example.com');
                $io->info('Password: password');
                return Command::SUCCESS;
            }

            // Create test user
            $user = new User();
            $user->setEmail('test@example.com');
            $user->setFirstName('John');
            $user->setLastName('Doe');
            $user->setUsername('johndoe');
            $user->setJobTitle('Senior Designer');
            $user->setCompany('IamGickPro');
            $user->setWebsite('https://johndoe.com');
            $user->setPortfolio('https://portfolio.johndoe.com');
            $user->setBio('Test user for development and testing purposes');
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setRoles(['ROLE_USER']);
            $user->setUpdatedAt(new \DateTimeImmutable());

            // Set social links
            $user->setSocialLinks([
                'twitter' => 'johndoe',
                'linkedin' => 'https://linkedin.com/in/johndoe',
                'dribbble' => 'https://dribbble.com/johndoe',
                'behance' => 'https://behance.net/johndoe'
            ]);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success('Test user created successfully!');
            $io->info('Email: test@example.com');
            $io->info('Password: password');
            $io->info('Name: John Doe');

        } catch (\Exception $e) {
            $io->error('Failed to create test user: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
