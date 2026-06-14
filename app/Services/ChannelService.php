<?php

namespace App\Services;

use App\Jobs\SimulateCallbackJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class ChannelService
{
    /**
     * Send message via channel (real delivery if configured, simulated callbacks for dashboard metrics).
     */
    public function sendMessage(int $communicationId, array $customer, string $message, string $channel): void
    {
        Log::info("ChannelService sending message to {$customer['name']} via {$channel}");

        // 1. Live Delivery Integrations
        if ($channel === 'email') {
            $this->sendRealEmail($customer['email'], $customer['name'], $message);
        } elseif ($channel === 'sms' || $channel === 'whatsapp') {
            $this->sendRealTwilioMessage($customer['phone'], $message, $channel);
        }

        // 2. Simulated Engagement Metrics (for dashboard analytics)
        // Keep progressive status updates active so the campaign charts animate and show opens/clicks/conversions.
        $roll = rand(1, 100);

        if ($roll <= 5) {
            // 5% chance: Failed to deliver
            SimulateCallbackJob::dispatch($communicationId, 'failed')
                ->delay(now()->addSeconds(rand(1, 2)));
        } else {
            // 95% chance: Delivered successfully
            SimulateCallbackJob::dispatch($communicationId, 'delivered')
                ->delay(now()->addSeconds(rand(1, 2)));

            // Roll for open
            $openRoll = rand(1, 100);
            if ($openRoll <= 75) {
                // 75% of delivered get opened
                SimulateCallbackJob::dispatch($communicationId, 'opened')
                    ->delay(now()->addSeconds(rand(3, 5)));

                // 90% of opened get read
                $readRoll = rand(1, 100);
                if ($readRoll <= 90) {
                    SimulateCallbackJob::dispatch($communicationId, 'read')
                        ->delay(now()->addSeconds(rand(4, 6)));
                }

                // Roll for click
                $clickRoll = rand(1, 100);
                if ($clickRoll <= 35) {
                    // 35% of opened get clicked
                    SimulateCallbackJob::dispatch($communicationId, 'clicked')
                        ->delay(now()->addSeconds(rand(6, 8)));

                    // Roll for conversion
                    $convertRoll = rand(1, 100);
                    if ($convertRoll <= 20) {
                        // 20% of clicked get converted
                        SimulateCallbackJob::dispatch($communicationId, 'converted')
                            ->delay(now()->addSeconds(rand(9, 12)));
                    }
                }
            }
        }
    }

    /**
     * Send HTML email using Laravel Mail facade if configured.
     */
    protected function sendRealEmail(string $recipientEmail, string $customerName, string $body): void
    {
        $mailer = config('mail.default');
        
        // Skip log and array drivers for real sending, but allow SMTP or others if configured
        if ($mailer === 'log' || $mailer === 'array') {
            Log::info("Skipping real email delivery (Mail driver is: {$mailer})");
            return;
        }

        try {
            $htmlContent = $this->buildHtmlEmail($customerName, $body, 'ShopReach Marketing Campaign');
            
            Mail::html($htmlContent, function ($mail) use ($recipientEmail) {
                $mail->to($recipientEmail)
                     ->subject('ShopReach Marketing Campaign');
            });
            
            Log::info("Real HTML email successfully sent to {$recipientEmail}");
        } catch (\Exception $e) {
            Log::error("Failed to send real email to {$recipientEmail}: " . $e->getMessage());
        }
    }

    /**
     * Send SMS/WhatsApp using Twilio API.
     */
    protected function sendRealTwilioMessage(string $recipientPhone, string $body, string $channel): void
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = $channel === 'whatsapp' ? env('TWILIO_WHATSAPP_NUMBER') : env('TWILIO_PHONE_NUMBER');

        if (empty($sid) || empty($token) || empty($from)) {
            Log::info("Twilio credentials not fully set. Skipping real {$channel} delivery.");
            return;
        }

        // Format recipient phone number: ensure country code (default to +91 for 10-digit Indian numbers)
        $to = trim($recipientPhone);
        if (!str_starts_with($to, '+')) {
            $to = '+' . (strlen($to) === 10 ? '91' : '') . $to;
        }

        if ($channel === 'whatsapp') {
            // Twilio requires "whatsapp:" prefix for From and To numbers in WhatsApp API requests
            $fromNumber = str_starts_with($from, 'whatsapp:') ? substr($from, 9) : $from;
            $from = 'whatsapp:' . trim($fromNumber);
            $to = 'whatsapp:' . $to;
        }

        try {
            Log::info("Attempting to send real Twilio {$channel} from {$from} to {$to}");
            
            $response = Http::timeout(10)
                ->withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $to,
                    'Body' => $body
                ]);

            if ($response->successful()) {
                Log::info("Real Twilio {$channel} message successfully sent. SID: " . ($response->json()['sid'] ?? 'unknown'));
            } else {
                Log::error("Twilio {$channel} API returned error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Failed to call Twilio API: " . $e->getMessage());
        }
    }

    /**
     * Wrap message body inside a beautiful, styled, responsive HTML email template.
     */
    protected function buildHtmlEmail(string $customerName, string $messageBody, string $subject): string
    {
        $formattedBody = nl2br(e($messageBody));
        
        // Extract CTA button if present in message text
        $buttonHtml = '';
        if (preg_match('/(?:Shop now|Claim Code|Claim Offer|Visit Store|Shop Online|Get Coupon|Grab Treatment|Claim Treat|Get Discount|Claim Your|Click Here).*?(https?:\/\/[^\s]+|#[^\s]+|[^\s]+\.[^\s]+)/i', $messageBody, $matches)) {
            $url = $matches[1];
            if (!str_starts_with($url, 'http')) {
                $url = 'https://' . $url;
            }
            $buttonText = 'Shop Now';
            if (preg_match('/(Shop now|Claim Code|Claim Offer|Visit Store|Shop Online|Get Coupon|Grab Treatment|Claim Treat|Get Discount|Claim Your.*?)\b/i', $messageBody, $btnMatches)) {
                $buttonText = trim(str_replace(['→', ':', '!', '-', '>', '»'], '', $btnMatches[1]));
            }
            $buttonHtml = "
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$url}' style='background: linear-gradient(135deg, #9333ea 0%, #06b6d4 100%); color: #ffffff; text-decoration: none; padding: 12px 30px; font-size: 14px; font-weight: bold; border-radius: 8px; display: inline-block; box-shadow: 0 4px 6px rgba(147, 51, 234, 0.2);'>{$buttonText}</a>
            </div>";
        } else {
            // Default CTA button pointing to root shopreach dashboard or store
            $buttonHtml = "
            <div style='text-align: center; margin: 30px 0;'>
                <a href='http://localhost:3000' style='background: linear-gradient(135deg, #9333ea 0%, #06b6d4 100%); color: #ffffff; text-decoration: none; padding: 12px 30px; font-size: 14px; font-weight: bold; border-radius: 8px; display: inline-block; box-shadow: 0 4px 6px rgba(147, 51, 234, 0.2);'>Visit Our Store</a>
            </div>";
        }

        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$subject}</title>
</head>
<body style='margin: 0; padding: 0; background-color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; -webkit-font-smoothing: antialiased;'>
    <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #0f172a; padding: 40px 0;'>
        <tr>
            <td align='center'>
                <!-- Email Container -->
                <table border='0' cellpadding='0' cellspacing='0' width='600' style='background-color: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);'>
                    <!-- Header -->
                    <tr>
                        <td align='center' style='padding: 30px 40px; background: linear-gradient(135deg, rgba(147, 51, 234, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%); border-bottom: 1px solid #334155;'>
                            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                                <tr>
                                    <td align='center'>
                                        <!-- Glowing Logo -->
                                        <div style='display: inline-block; width: 44px; height: 44px; line-height: 44px; border-radius: 10px; background: linear-gradient(135deg, #9333ea 0%, #06b6d4 100%); color: #ffffff; font-size: 24px; font-weight: bold; margin-bottom: 10px;'>⚡</div>
                                        <h1 style='margin: 0; color: #ffffff; font-size: 20px; font-weight: 800; letter-spacing: 0.5px;'>ShopReach AI</h1>
                                        <p style='margin: 2px 0 0 0; color: #94a3b8; font-size: 11px; text-transform: uppercase; tracking-wider: 1px;'>Campaign Promotion</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Body Content -->
                    <tr>
                        <td style='padding: 40px; color: #e2e8f0; font-size: 15px; line-height: 1.6;'>
                            <p style='margin-top: 0; color: #ffffff; font-size: 18px; font-weight: bold;'>Hello {$customerName},</p>
                            <div style='color: #cbd5e1; font-size: 15px; white-space: pre-wrap;'>
                                {$formattedBody}
                            </div>
                            {$buttonHtml}
                            <p style='margin-bottom: 0; color: #64748b; font-size: 12px; border-top: 1px solid #334155; padding-top: 20px; text-align: center;'>
                                If you have any questions, feel free to reply directly to this email.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align='center' style='padding: 30px 40px; background-color: #0f172a; border-top: 1px solid #334155; color: #64748b; font-size: 11px;'>
                            <p style='margin: 0 0 10px 0;'>&copy; " . date('Y') . " ShopReach AI. All rights reserved.</p>
                            <p style='margin: 0;'>You received this email because you are a valued customer of ShopReach. <a href='#' style='color: #38bdf8; text-decoration: none;'>Unsubscribe</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";
    }
}
