# Secure Email CLI Utility

A future-proof, enterprise-grade PHP command-line tool built on Symfony 7.4 LTS and PHPMailer. It is architected to deliver messages securely via credential-free, IP-whitelisted SMTP relays, with fallback environment profiles for quick local container evaluation.

## Features

- **Symfony 7.4 LTS Engine**: Built with modern structured command handlers, full PHP type safety (`declare(strict_types=1)`), and zero deprecated hooks.
- **DDEV / Mailpit Ready**: Automatically detects local DDEV containers, self-adjusts network ports, and bypasses local TLS handshake enforcement natively.
- **Failover Resilience**: Automatically attempts exactly one delivery retry if the initial SMTP connection breaks.
- **Interactive Prompts**: Includes an input-safe verification confirmation layout before pushing data packets.
- **Live Progress Animation**: Provides a real-time tracking bar for underlying infrastructure steps.
- **Comprehensive Logging**: Full verbose SMTP transport diagnostics are printed directly to the console by default and appended cleanly to an isolated text log.

## Prerequisites

- **PHP 8.4** or higher
- **Composer** installed locally
- Access to an **IP-whitelisted SMTP server** requiring no credentials (or local **DDEV**)

## Installation & Setup

1. **Navigate** to your local project workspace directory:
   ```bash
   cd your-project-folder
   ```

2. **Install dependencies** utilizing strict version enforcement:
   ```bash
   composer install
   ```

3. **Initialize environment settings**:
   ```bash
   cp .env.example .env
   ```

4. Adjust the target server credentials inside `.env` to match your infrastructure variables.

## Local Testing with DDEV

This app includes native auto-detection for DDEV container ecosystems. To test completely offline:

1. Update your local `.env` variables to route directly through the DDEV internal loopback network:
   ```env
   SMTP_HOST=localhost
   SMTP_PORT=1025
   ```
2. Execute the script natively inside your active web service container container:
   ```bash
   ddev exec php send.php
   ```
3. View the intercepted formatted text results inside the Mailpit interface:
   ```bash
   ddev mailpit
   ```

## Production Usage

Run the utility file with your system's native runtime binary:

```bash
php send.php
```

All raw connection sequences are parsed dynamically and written to `/smtp_delivery.log` upon completion to protect the execution context from terminal output clutter.

## Project Structure

```text
├── .env                  # Environment values (Protected via .gitignore)
├── .env.example          # Non-sensitive structure template for staging
├── .gitignore            # Keeps variables, vendor libraries, and logs untracked
├── composer.json         # Strict version locking (Symfony 7.4 LTS, PHP 8.4+)
├── send.php              # Modern type-safe application runner execution entry
└── smtp_delivery.log     # Append-only transport tracking audit record
```
