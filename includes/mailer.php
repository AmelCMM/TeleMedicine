<?php

/**
 * Send an email notification.
 * Uses PHP mail() by default. Swap with SMTP in production.
 */
function sendEmail(string $to, string $subject, string $message): bool
{
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . APP_NAME . ' <noreply@mediconnect.zm>',
        'X-Mailer: PHP/' . phpversion(),
    ];

    $htmlMessage = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>';
    $htmlMessage .= '<div style="max-width:600px;margin:0 auto;font-family:sans-serif;">';
    $htmlMessage .= '<h2 style="color:#0077B6;">' . APP_NAME . '</h2>';
    $htmlMessage .= nl2br(e($message));
    $htmlMessage .= '<hr><p style="color:#6C757D;font-size:12px;">This is an automated message from ' . APP_NAME . '. Please do not reply.</p>';
    $htmlMessage .= '</div></body></html>';

    return mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
}
