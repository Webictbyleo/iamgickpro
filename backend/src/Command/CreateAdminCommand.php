<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Entity\UserSubscription;
use App\Entity\SubscriptionPlan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user for the platform'
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Admin email address')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Admin password')
            ->addOption('first-name', null, InputOption::VALUE_OPTIONAL, 'Admin first name')
            ->addOption('last-name', null, InputOption::VALUE_OPTIONAL, 'Admin last name')
            ->addOption('username', null, InputOption::VALUE_OPTIONAL, 'Admin username')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force create even if admin exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $userRepository = $this->entityManager->getRepository(User::class);
            
            // Check if admin already exists (look for users with ROLE_ADMIN)
            $existingAdmins = $userRepository->createQueryBuilder('u')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ADMIN%')
                ->getQuery()
                ->getResult();

            if (!empty($existingAdmins) && !$input->getOption('force')) {
                $io->info('Admin user already exists: ' . $existingAdmins[0]->getEmail());
                return Command::SUCCESS;
            }

            // Get admin details from options or prompt
            $email = $input->getOption('email');
            $password = $input->getOption('password');
            $firstName = $input->getOption('first-name');
            $lastName = $input->getOption('last-name');
            $username = $input->getOption('username');

            if (!$email) {
                $email = $io->ask('Admin email address', 'admin@example.com');
            }

            if (!$password) {
                $password = $io->askHidden('Admin password (will be hidden)', function ($value) {
                    if (empty($value)) {
                        throw new \Exception('Password cannot be empty');
                    }
                    if (strlen($value) < 8) {
                        throw new \Exception('Password must be at least 8 characters long');
                    }
                    return $value;
                });
            }

            if (!$firstName) {
                $firstName = $io->ask('First name', 'Admin');
            }

            if (!$lastName) {
                $lastName = $io->ask('Last name', 'User');
            }

            if (!$username) {
                $username = $io->ask('Username', 'admin');
            }

            // Check if email is already taken
            $existingUser = $userRepository->findOneBy(['email' => $email]);
            if ($existingUser && !$input->getOption('force')) {
                $io->error('A user with this email already exists: ' . $email);
                return Command::FAILURE;
            }

            // Create or update admin user
            $user = $existingUser ?: new User();
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setUsername($username);
            $user->setJobTitle('Platform Administrator');
            $user->setCompany($_ENV['APP_DISPLAY_NAME'] ?? 'Design Platform');
            $user->setBio('Platform administrator with full access to all features and settings.');
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
            $user->setIsVerified(true);
            $user->setEmailVerified(true);
            $user->setIsActive(true);
            $user->setUpdatedAt(new \DateTimeImmutable());

            if (!$existingUser) {
                $this->entityManager->persist($user);
            }

            // Try to create a premium subscription for admin if plans exist
            $planRepository = $this->entityManager->getRepository(SubscriptionPlan::class);
            $plans = $planRepository->findAll();
            
            if (!empty($plans)) {
                // Find the best plan (premium, pro, or highest tier)
                $premiumPlan = null;
                foreach ($plans as $plan) {
                    $planName = strtolower($plan->getName());
                    if (str_contains($planName, 'premium') || str_contains($planName, 'pro') || str_contains($planName, 'unlimited')) {
                        $premiumPlan = $plan;
                        break;
                    }
                }
                
                // If no premium plan found, use the first available plan
                if (!$premiumPlan) {
                    $premiumPlan = $plans[0];
                }

                // Check if user already has a subscription
                $subscriptionRepository = $this->entityManager->getRepository(UserSubscription::class);
                $existingSubscription = $subscriptionRepository->findOneBy(['user' => $user]);
                
                if (!$existingSubscription) {
                    $subscription = new UserSubscription();
                    $subscription->setUser($user);
                    $subscription->setSubscriptionPlan($premiumPlan);
                    $subscription->setStatus('active');
                    $subscription->setStartDate(new \DateTimeImmutable());
                    $subscription->setEndDate(new \DateTimeImmutable('+1 year'));

                    $this->entityManager->persist($subscription);
                    $io->info('Premium subscription created for admin user');
                }
            }

            $this->entityManager->flush();

            $io->success('Admin user created successfully!');
            $io->table(['Field', 'Value'], [
                ['Email', $email],
                ['Username', $username],
                ['First Name', $firstName],
                ['Last Name', $lastName],
                ['Roles', 'ROLE_ADMIN, ROLE_USER'],
                ['Subscription', !empty($plans) && isset($premiumPlan) ? $premiumPlan->getName() : 'None available']
            ]);

            $io->note([
                'Important:',
                '- Save the login credentials in a secure location',
                '- Change the password after first login',
                '- The admin user has full access to all platform features'
            ]);

        } catch (\Exception $e) {
            $io->error('Failed to create admin user: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}