<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiryReminder extends Notification implements ShouldQueue
{
	use Queueable;

	public function __construct(
		private readonly Contract $contract,
		private readonly int $daysLeft
	) {}

	public function via(object $notifiable): array
	{
		return ['mail'];
	}

	public function toMail(object $notifiable): MailMessage
	{
		$contract = $this->contract;
		$subject = sprintf('ხელშეკრულების ვადა ახლოვდება – %s/%s', (string) ($contract->contract_number ?? $contract->id), (string) $contract->party_name);
		$text = sprintf(
			'ხელშეკრულება %s, ტიპი %s, მხარე %s იწურება %s-ზე. დარჩენილია %d დღე.',
			(string) ($contract->contract_number ?? $contract->id),
			optional($contract->type)->name ?? '-',
			(string) $contract->party_name,
			optional($contract->expiry_date)?->format('Y-m-d') ?? '-',
			$this->daysLeft
		);

		return (new MailMessage)
			->subject($subject)
			->line($text);
	}
}
