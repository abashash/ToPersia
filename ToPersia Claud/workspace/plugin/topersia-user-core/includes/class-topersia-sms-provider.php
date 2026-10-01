<?php
defined('ABSPATH') || exit;

interface ToPersia_SMS_Provider {
    /** @return array{success:bool,message:string,request_id:int|string|null} */
    public function send_otp(string $recipient, string $otp, string $pattern_code): array;
}
