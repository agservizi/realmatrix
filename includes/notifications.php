<?php
// includes/notifications.php
// Replace stubs with real integrations (SMTP/Twilio/queue/webhooks signed)

function notify_email(string $to, string $subject, string $body): bool {
    // TODO integrate SMTP/API (e.g. SendGrid/Mailgun). Return stub true for now.
    return true;
}

function notify_sms(string $to, string $message): bool {
    // TODO integrate SMS provider (Twilio/etc.). Return stub true for now.
    return true;
}

function sign_payload(array $data): string {
    $secret = defined('WEBHOOK_SECRET') ? WEBHOOK_SECRET : 'change_me';
    return hash_hmac('sha256', json_encode($data), $secret);
}

function dispatch_webhook(string $event, array $payload, string $url): bool {
    $body = ['event' => $event, 'data' => $payload, 'ts' => time()];
    $signature = sign_payload($body);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Domus-Signature: ' . $signature
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_exec($ch);
    curl_close($ch);
    return true;
}
