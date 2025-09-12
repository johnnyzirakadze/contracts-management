<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Notifications\ContractExpiryReminder;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendContractReminders extends Command
{
	protected $signature = 'contracts:send-reminders';
	protected $description = 'Send contract expiry reminders (-60d, -30d) with retry policy';

	public function handle(SmsService $sms): int
	{
		// TZ Asia/Tbilisi at 09:00
		$tz = 'Asia/Tbilisi';
		$nowTbilisi = \Illuminate\Support\Carbon::now($tz);

		$targetDays = [60, 30];
		foreach ($targetDays as $days) {
			$targetDate = $nowTbilisi->copy()->addDays($days)->toDateString();
			$this->info("Processing {$days}-day reminders for expiry_date {$targetDate}");

			$contracts = Contract::query()
				->whereDate('expiry_date', '=', $targetDate)
				->whereNotIn('status', ['დახურული', 'დასრულებული'])
				->with(['responsibleManager','initiator','type'])
				->get();

			foreach ($contracts as $contract) {
				$sentAtField = $days === 60 ? 'reminder_60_sent_at' : 'reminder_30_sent_at';
				$attemptsField = $days === 60 ? 'reminder_60_attempts' : 'reminder_30_attempts';
				$lastAttemptField = $days === 60 ? 'reminder_60_last_attempt_at' : 'reminder_30_last_attempt_at';

				if ($contract->{$sentAtField}) {
					continue; // already sent once for this stage
				}

				$attempts = (int) ($contract->{$attemptsField} ?? 0);
				$lastAttempt = $contract->{$lastAttemptField};
				if ($attempts >= 3) {
					continue; // exhausted retries
				}
				if ($lastAttempt && Carbon::parse($lastAttempt, $tz)->diffInMinutes($nowTbilisi) < 30) {
					continue; // wait 30 minutes between retries
				}

				try {
					$this->sendNotifications($contract, $days, $sms);
					$contract->forceFill([
						$sentAtField => Carbon::now($tz),
						$attemptsField => $attempts + 1,
						$lastAttemptField => Carbon::now($tz),
					])->save();
					Log::info('Reminder sent', ['contract_id' => $contract->id, 'days' => $days]);
				} catch (\Throwable $e) {
					$contract->forceFill([
						$attemptsField => $attempts + 1,
						$lastAttemptField => Carbon::now($tz),
					])->save();
					Log::error('Reminder send failed', ['contract_id' => $contract->id, 'days' => $days, 'error' => $e->getMessage()]);
				}
			}
		}

		return self::SUCCESS;
	}

	private function sendNotifications(Contract $contract, int $days, SmsService $sms): void
	{
		$notif = new ContractExpiryReminder($contract, $days);

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
		}

		$phoneTargets = [];
		if ($contract->responsibleManager && $contract->responsibleManager->phone) {
			$phoneTargets[] = $contract->responsibleManager->phone;
		}
		if ($contract->initiator && $contract->initiator->phone) {
			$phoneTargets[] = $contract->initiator->phone;
		}
		$phoneTargets = array_values(array_unique(array_filter($phoneTargets)));

		$body = sprintf('Contract %s expires in %d days (party %s, due %s).',
			(string) ($contract->contract_number ?? $contract->id),
			$days,
			(string) $contract->party_name,
			optional($contract->expiry_date)?->format('Y-m-d') ?? '-'
		);
		foreach ($phoneTargets as $phone) {
			$sms->send($phone, $body);
		}
	}
}
