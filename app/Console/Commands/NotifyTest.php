<?php

namespace App\Console\Commands;

use App\Notifications\ContractExpiryReminder;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class NotifyTest extends Command
{
    protected $signature = 'notify:test {--email=} {--phone=} {--contract=} {--days=30} {--from=}';
    protected $description = 'Send a test email/SMS or send to recipients of a specific contract';

    public function handle(SmsService $sms): int
    {
        // Ensure queued notifications run synchronously in tests
        config(['queue.default' => 'sync']);

        $email = (string) $this->option('email');
        $phone = (string) $this->option('phone');
        $contractId = $this->option('contract');
        $days = (int) $this->option('days');
        $from = (string) $this->option('from');

        $this->info('Starting notification test...');

        // Optionally override global from
        if ($from !== '') {
            config(['mail.from.address' => $from]);
        }

        // Build a lightweight fake-like notification using MailMessage directly
        $mailMessage = (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('ტესტური შეტყობინება (Contracts)')
            ->line('ეს არის ტესტური იმეილი სისტემიდან Contracts Management. თუ მიიღეთ, იმეილი მუშაობს.');

        if ($email !== '') {
            Notification::route('mail', [$email])->notify(new class($mailMessage) extends \Illuminate\Notifications\Notification {
                public function __construct(private readonly \Illuminate\Notifications\Messages\MailMessage $message) {}
                public function via(object $notifiable): array { return ['mail']; }
                public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage { return $this->message; }
            });
            $this->info("Email dispatched to {$email} (check mail transport/log)");
        } else {
            $this->warn('No --email provided, skipping email test.');
        }

        if ($phone !== '') {
            $sent = $sms->send($phone, 'ტესტური SMS სისტემიდან Contracts Management. თუ მიიღეთ, SMS მუშაობს.');
            $this->info($sent ? "SMS sent to {$phone}" : "SMS not sent; check logs and env (TWILIO_*)");
        } else {
            $this->warn('No --phone provided, skipping SMS test.');
        }

        // Contract-based recipients
        if ($contractId !== null && $contractId !== '') {
            $this->info("Collecting recipients from contract #{$contractId} (days={$days})...");
            /** @var \App\Models\Contract|null $contract */
            $contract = \App\Models\Contract::query()
                ->with(['responsibleManager','initiator','type'])
                ->find($contractId);
            if (! $contract) {
                $this->error('Contract not found');
                return self::FAILURE;
            }

            $notif = new \App\Notifications\ContractExpiryReminder($contract, $days);

            $emails = [];
            if ($contract->responsibleManager && $contract->responsibleManager->email) {
                $emails[] = $contract->responsibleManager->email;
            }
            if ($contract->initiator && $contract->initiator->email) {
                $emails[] = $contract->initiator->email;
            }
            if ($contract->notify_group_email) {
                $emails[] = $contract->notify_group_email;
            }
            $emails = array_values(array_unique(array_filter($emails)));
            if (! empty($emails)) {
                Notification::route('mail', $emails)->notify($notif);
                $this->info('Contract email recipients dispatched: '.implode(', ', $emails));
            } else {
                $this->warn('No email recipients found on contract.');
            }

            $phones = [];
            if ($contract->responsibleManager && $contract->responsibleManager->phone) {
                $phones[] = $contract->responsibleManager->phone;
            }
            if ($contract->initiator && $contract->initiator->phone) {
                $phones[] = $contract->initiator->phone;
            }
            $phones = array_values(array_unique(array_filter($phones)));
            if (! empty($phones)) {
                $body = sprintf('Contract %s expires in %d days (party %s, due %s).',
                    (string) ($contract->contract_number ?? $contract->id),
                    $days,
                    (string) $contract->party_name,
                    optional($contract->expiry_date)?->format('Y-m-d') ?? '-'
                );
                foreach ($phones as $p) {
                    $sms->send($p, $body);
                }
                $this->info('Contract SMS recipients attempted: '.implode(', ', $phones));
            } else {
                $this->warn('No phone recipients found on contract.');
            }
        }

        $this->info('Notification test completed.');
        return self::SUCCESS;
    }
}


