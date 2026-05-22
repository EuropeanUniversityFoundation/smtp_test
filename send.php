<?php

declare(strict_types=1);

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\ProgressBar;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\SMTP;
use Dotenv\Dotenv;

// Load Composer autoloader
require __DIR__ . '/vendor/autoload.php';

// Load environment configurations
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

class SendEmailCommand extends Command
{
    protected static $defaultName = 'app:send-email';
    protected static $defaultDescription = 'Sends a secure whitelisted test email with 1 retry and file logging.';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper('question');
        
        $question = new ConfirmationQuestion(
            "Are you sure you want to send an email to <comment>{$_ENV['RECEIVER_EMAIL']}</comment>? (y/N): ", 
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<comment>[warning] Operation cancelled by user.</comment>');
            return Command::SUCCESS;
        }

        $output->writeln('<info>Initializing email transfer.</info>');

        // Progress Bar (Total 4 operational steps)
        $progressBar = new ProgressBar($output, 4);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $progressBar->start();

        $mail = new PHPMailer(true);
        $debugLogs = []; 

        // Step 1: Secure Configuration
        $progressBar->setMessage('Configuring SMTP settings...');
        $mail->isSMTP();
        $mail->Host       = (string) $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = false; 
        $mail->Port       = (int) $_ENV['SMTP_PORT'];

        // Automatic local container environment detection (DDEV fallback layout)
        if (isset($_ENV['IS_DDEV_PROJECT'])) {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        }

        // Intercept raw network records via a secure modern closure callback
        $mail->SMTPDebug = SMTP::DEBUG_SERVER; 
        $mail->Debugoutput = function (string $str) use (&$debugLogs): void {
            $cleanStr = trim(preg_replace('/\r\n|\r|\n/', ' ', $str));
            if ($cleanStr !== '') {
                $debugLogs[] = $cleanStr;
            }
        };

        $mail->setFrom((string) $_ENV['SENDER_EMAIL'], (string) $_ENV['SENDER_NAME']);
        $mail->addAddress((string) $_ENV['RECEIVER_EMAIL'], (string) $_ENV['RECEIVER_NAME']);
        $mail->isHTML(true);
        $mail->Subject = 'Secure Symfony LTS Test Email';
        $mail->Body    = '<h1>Success!</h1><p>Sent via future-proofed Symfony architecture.</p>';
        $mail->AltBody = 'Success! Sent via future-proofed Symfony architecture.';
        
        usleep(200000);
        $progressBar->advance();

        // Loop Variables
        $maxAttempts = 2;
        $attempt = 1;
        $isSent = false;
        $errorMessage = '';

        // Step 2 & 3: Resilient Delivery & Fallback Evaluation
        while ($attempt <= $maxAttempts && !$isSent) {
            try {
                $progressBar->setMessage("Delivery attempt {$attempt}/{$maxAttempts} to {$_ENV['SMTP_HOST']}...");
                $mail->send();
                $isSent = true;
                $progressBar->advance();
            } catch (PHPMailerException) {
                $errorMessage = $mail->ErrorInfo;
                $debugLogs[] = "CRITICAL: Attempt {$attempt} failed with error: {$errorMessage}";
                
                if ($attempt < $maxAttempts) {
                    $progressBar->setMessage("<comment>Attempt {$attempt} failed. Retrying in 2 seconds...</comment>");
                    $mail->clearAddresses();
                    $mail->addAddress((string) $_ENV['RECEIVER_EMAIL'], (string) $_ENV['RECEIVER_NAME']);
                    
                    sleep(2); 
                    $attempt++;
                } else {
                    break;
                }
            }
        }

        // Step 4: Finalizing components
        $progressBar->setMessage('Saving operational summary...');
        $progressBar->advance();
        usleep(200000);
        $progressBar->finish();
        $output->writeln("\n"); 

        // Always print the verbose layout to the terminal
        $output->writeln('<comment>--- Verbose SMTP Network Logs ---</comment>');
        foreach ($debugLogs as $log) {
            $output->writeln("  <comment>[debug]</comment> {$log}");
        }
        $output->writeln('<comment>---------------------------------</comment>');

        // Persistent Audit Storage Logs
        $logFile = __DIR__ . '/smtp_delivery.log';
        $timestamp = date('Y-m-d H:i:s');
        $statusText = $isSent ? "SUCCESS" : "FAILED";
        
        $logPayload = "==================================================\n";
        $logPayload .= "[{$timestamp}] STATUS: {$statusText} (Attempts: {$attempt}/{$maxAttempts})\n";
        $logPayload .= "Recipient: {$_ENV['RECEIVER_EMAIL']}\n";
        $logPayload .= "--------------------------------------------------\n";
        foreach ($debugLogs as $log) {
            $logPayload .= "{$log}\n";
        }
        if (!$isSent) {
            $logPayload .= "Final Error Summary: {$errorMessage}\n";
        }
        $logPayload .= "==================================================\n\n";

        file_put_contents($logFile, $logPayload, FILE_APPEND);
        $output->writeln("Logs written successfully to: <comment>{$logFile}</comment>");

        if ($isSent) {
            $output->writeln('<info>[success] Email sent successfully!</info>');
            return Command::SUCCESS;
        }

        $output->writeln('<error>[error] Message delivery completely failed after maximum retries.</error>');
        $output->writeln("<error>Final Mailer Error: {$errorMessage}</error>");
        return Command::FAILURE;
    }
}

// Modern, Symfony LTS bootstrap runner configuration
$application = new Application('Email CLI Utility', '1.0.0');
$application->addCommand(new SendEmailCommand()); // Future-proof non-deprecated syntax
$application->setDefaultCommand('app:send-email', true);
$application->run();
