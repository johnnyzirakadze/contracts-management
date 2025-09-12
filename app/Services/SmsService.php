<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class SmsService
{
	public function send(string $phoneNumber, string $message): bool
	{
		$sid = (string) env('TWILIO_SID', '');
		$token = (string) env('TWILIO_TOKEN', '');
		$from = (string) env('TWILIO_FROM', '');

		if ($sid === '' || $token === '' || $from === '') {
			Log::warning('SMS provider not configured; skipping', ['to' => $phoneNumber]);
			return false;
		}

		try {
			$client = new TwilioClient($sid, $token);
			$client->messages->create($phoneNumber, [
				'from' => $from,
				'body' => $message,
			]);
			return true;
		} catch (\Throwable $e) {
			Log::error('SMS send error', ['to' => $phoneNumber, 'error' => $e->getMessage()]);
			return false;
		}
	}
}
