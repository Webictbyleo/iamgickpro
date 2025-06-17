<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Psr\Log\LoggerInterface;

/**
 * Email Service
 * 
 * Handles all email sending operations including verification emails,
 * password reset emails, notifications, and other transactional emails.
 */
readonly class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $fromEmail,
        private string $appName,
        private string $frontendUrl
    ) {}

    /**
     * Send email verification email
     */
    public function sendEmailVerification(User $user, string $verificationToken): void
    {
        try {
            $verificationUrl = $this->frontendUrl . '/verify-email?token=' . $verificationToken;
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->appName))
                ->to(new Address($user->getEmail(), $user->getFirstName() . ' ' . $user->getLastName()))
                ->subject('Verify your email address')
                ->htmlTemplate('emails/email_verification.html.twig')
                ->context([
                    'user' => $user,
                    'verification_url' => $verificationUrl,
                    'app_name' => $this->appName
                ]);

            $this->mailer->send($email);
            
            $this->logger->info('Email verification sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send email verification', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordReset(User $user, string $resetToken): void
    {
        try {
            $resetUrl = $this->frontendUrl . '/reset-password?token=' . $resetToken;
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->appName))
                ->to(new Address($user->getEmail(), $user->getFirstName() . ' ' . $user->getLastName()))
                ->subject('Reset your password')
                ->htmlTemplate('emails/password_reset.html.twig')
                ->context([
                    'user' => $user,
                    'reset_url' => $resetUrl,
                    'app_name' => $this->appName
                ]);

            $this->mailer->send($email);
            
            $this->logger->info('Password reset email sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send password reset email', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send welcome email
     */
    public function sendWelcome(User $user): void
    {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->appName))
                ->to(new Address($user->getEmail(), $user->getFirstName() . ' ' . $user->getLastName()))
                ->subject('Welcome to ' . $this->appName)
                ->htmlTemplate('emails/welcome.html.twig')
                ->context([
                    'user' => $user,
                    'app_name' => $this->appName,
                    'dashboard_url' => $this->frontendUrl . '/dashboard'
                ]);

            $this->mailer->send($email);
            
            $this->logger->info('Welcome email sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send welcome email', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);
            // Don't throw for welcome emails - they're not critical
        }
    }

    /**
     * Send account deletion confirmation email
     */
    public function sendAccountDeletionConfirmation(User $user): void
    {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->appName))
                ->to(new Address($user->getEmail(), $user->getFirstName() . ' ' . $user->getLastName()))
                ->subject('Account deletion confirmation')
                ->htmlTemplate('emails/account_deletion.html.twig')
                ->context([
                    'user' => $user,
                    'app_name' => $this->appName
                ]);

            $this->mailer->send($email);
            
            $this->logger->info('Account deletion confirmation sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send account deletion confirmation', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);
            // Don't throw for deletion confirmations
        }
    }

    /**
     * Send notification email
     */
    public function sendNotification(User $user, string $subject, string $template, array $context = []): void
    {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->appName))
                ->to(new Address($user->getEmail(), $user->getFirstName() . ' ' . $user->getLastName()))
                ->subject($subject)
                ->htmlTemplate($template)
                ->context(array_merge([
                    'user' => $user,
                    'app_name' => $this->appName
                ], $context));

            $this->mailer->send($email);
            
            $this->logger->info('Notification email sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'subject' => $subject,
                'template' => $template
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send notification email', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'subject' => $subject,
                'template' => $template,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send plain text email
     */
    public function sendPlainEmail(string $to, string $subject, string $content): void
    {
        try {
            $email = (new Email())
                ->from(new Address($this->fromEmail, $this->appName))
                ->to($to)
                ->subject($subject)
                ->text($content);

            $this->mailer->send($email);
            
            $this->logger->info('Plain email sent', [
                'to' => $to,
                'subject' => $subject
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send plain email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
