<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    /**
     * Generate structured campaign JSON from user prompt.
     */
    public function generateCampaignConfig(string $prompt): array
    {
        if (empty($this->apiKey)) {
            Log::warning('Gemini API key is not set. Using rule-based fallback parser.');
            return $this->fallbackParser($prompt);
        }

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $this->apiKey;

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => "You are ShopReach AI's backend marketing copilot. Convert the following marketing campaign request into a structured JSON configuration.
                                Prompt: \"{$prompt}\""
                            ]
                        ]
                    ]
                ],
                'systemInstruction' => [
                    'parts' => [
                        [
                            'text' => "You translate customer criteria and marketing goals into a structured JSON configuration for ShopReach AI.
                            The JSON object must contain:
                            1. `segment_name`: A descriptive name for the audience.
                            2. `segment_description`: A short summary description of the segment (e.g., 'Customers with lifetime spend above ₹5000 and inactive for 45+ days').
                            3. `campaign_goal`: A short label of the campaign goal (e.g. 'Customer Re-engagement').
                            4. `rules`: An object with filters. Supported keys are: `total_spent`, `inactive_days`, `city`, `gender`, `last_order_days`, `order_count`, `engagement_score`, `age`, `date_of_birth`.
                               You MUST extract all filters described in the prompt. Examples:
                               - 'spent more than 5000' -> total_spent: { operator: '>=', value: 5000 }
                               - 'not ordered in the last 45 days' or 'inactive for 45 days' -> inactive_days: { operator: '>=', value: 45 }
                               - 'ordered in the last 30 days' -> last_order_days: { operator: '<=', value: 30 }
                               - 'from Delhi' -> city: { operator: '=', value: 'Delhi' }
                               - 'female' -> gender: { operator: '=', value: 'female' }
                               - 'engagement score above 80' -> engagement_score: { operator: '>', value: 80 }
                               - 'age above 30' -> age: { operator: '>', value: 30 }
                            5. `channel`: One of 'whatsapp', 'email', 'sms', 'rcs'.
                            6. `message`: A creative message template. Use {{name}} for name placeholder, {{city}} for city, and {{last_order_date}} for last order date.
                            7. `reasoning`: A short sentence explaining why this channel was selected.
                            8. `subject`: A suitable email subject line or message subject.
                            9. `headline`: A catchy headline for the template.
                            10. `body`: The main body copy of the campaign. Personalization placeholders should match {{name}}, {{city}}, {{last_order_date}}.
                            11. `cta`: Call-to-action text (e.g., 'Shop Now', 'Claim 20% Off').
                            
                            Ensure the response is strictly JSON."
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'segment_name' => ['type' => 'STRING'],
                            'segment_description' => ['type' => 'STRING'],
                            'campaign_goal' => ['type' => 'STRING'],
                            'channel' => [
                                'type' => 'STRING',
                                'enum' => ['whatsapp', 'email', 'sms', 'rcs'],
                            ],
                            'reasoning' => ['type' => 'STRING'],
                            'subject' => ['type' => 'STRING'],
                            'headline' => ['type' => 'STRING'],
                            'body' => ['type' => 'STRING'],
                            'cta' => ['type' => 'STRING'],
                            'message' => ['type' => 'STRING'],
                            'rules' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'total_spent' => [
                                        'type' => 'OBJECT',
                                        'properties' => [
                                            'operator' => ['type' => 'STRING'],
                                            'value' => ['type' => 'INTEGER']
                                        ],
                                        'required' => ['operator', 'value']
                                    ],
                                    'inactive_days' => [
                                        'type' => 'OBJECT',
                                        'properties' => [
                                            'operator' => ['type' => 'STRING'],
                                            'value' => ['type' => 'INTEGER']
                                        ],
                                        'required' => ['operator', 'value']
                                    ],
                                    'city' => [
                                        'type' => 'OBJECT',
                                        'properties' => [
                                            'operator' => ['type' => 'STRING'],
                                            'value' => ['type' => 'STRING']
                                        ],
                                        'required' => ['operator', 'value']
                                    ],
                                    'gender' => [
                                        'type' => 'OBJECT',
                                        'properties' => [
                                            'operator' => ['type' => 'STRING'],
                                            'value' => ['type' => 'STRING']
                                        ],
                                        'required' => ['operator', 'value']
                                    ],
                                    'last_order_days' => [
                                        'type' => 'OBJECT',
                                        'properties' => [
                                            'operator' => ['type' => 'STRING'],
                                            'value' => ['type' => 'INTEGER']
                                        ],
                                        'required' => ['operator', 'value']
                                    ],
                                    'order_count' => [
                                        'type' => 'OBJECT',
                                        'properties' => [
                                            'operator' => ['type' => 'STRING'],
                                            'value' => ['type' => 'INTEGER']
                                        ],
                                        'required' => ['operator', 'value']
                                    ],
                                    'engagement_score' => [
                                        'type' => 'OBJECT',
                                        'properties' => [
                                            'operator' => ['type' => 'STRING'],
                                            'value' => ['type' => 'INTEGER']
                                        ],
                                        'required' => ['operator', 'value']
                                    ],
                                    'age' => [
                                        'type' => 'OBJECT',
                                        'properties' => [
                                            'operator' => ['type' => 'STRING'],
                                            'value' => ['type' => 'INTEGER']
                                        ],
                                        'required' => ['operator', 'value']
                                    ],
                                    'date_of_birth' => [
                                        'type' => 'OBJECT',
                                        'properties' => [
                                            'operator' => ['type' => 'STRING'],
                                            'value' => ['type' => 'STRING']
                                        ],
                                        'required' => ['operator', 'value']
                                    ],
                                ],
                            ],
                        ],
                        'required' => ['segment_name', 'segment_description', 'campaign_goal', 'channel', 'reasoning', 'message', 'rules', 'headline', 'body', 'cta', 'subject'],
                    ],
                ],
            ];

            $response = Http::timeout(30)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $textResponse = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($textResponse) {
                    Log::info('Gemini Raw Response: ' . $textResponse);
                    $config = json_decode($textResponse, true);
                    if ($config) {
                        return $this->normalizeRules($config, $prompt);
                    }
                }
            }

            Log::error('Gemini API request failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Gemini Service error: ' . $e->getMessage());
        }

        return $this->fallbackParser($prompt);
    }

    /**
     * Defense logic to ensure rules always follow the nested format {operator: '...', value: '...'}
     * and merges regex-extracted rules to guarantee correctness.
     */
    private function normalizeRules(array $config, string $prompt): array
    {
        if (!isset($config['rules']) || !is_array($config['rules'])) {
            $config['rules'] = [];
        }

        // Run fallback parser on the prompt text to extract all rules deterministically
        $fallback = $this->fallbackParser($prompt);
        $fallbackRules = $fallback['rules'] ?? [];

        // Merge missing rules from fallback
        foreach ($fallbackRules as $key => $rule) {
            if (!isset($config['rules'][$key])) {
                $config['rules'][$key] = $rule;
            }
        }

        foreach ($config['rules'] as $key => $rule) {
            if (!is_array($rule)) {
                // Flat rule fallback
                $operator = '=';
                if (in_array($key, ['total_spent', 'order_count', 'engagement_score', 'age'])) {
                    $operator = '>=';
                } elseif ($key === 'inactive_days') {
                    $operator = '>=';
                } elseif ($key === 'last_order_days') {
                    $operator = '<=';
                }
                $config['rules'][$key] = [
                    'operator' => $operator,
                    'value' => $rule
                ];
            }
        }

        return $config;
    }

    /**
     * Resilient rule-based parser that handles typical inputs locally if Gemini is offline/errored.
     */
    private function fallbackParser(string $text): array
    {
        $rules = [];
        $segmentName = 'Custom Audience';
        $description = 'Custom audience based on request criteria';
        $goal = 'Marketing Promotion';
        $channel = 'whatsapp';
        $reasoning = 'WhatsApp generally delivers higher engagement rates for standard customer outreach.';
        $subject = 'Special Offer for You!';
        $headline = 'We Miss You';
        $body = 'Enjoy a special discount on your next order!';
        $cta = 'Shop Now';
        $discount = '15%';

        // Spend pattern
        if (preg_match('/(?:spent|spend|spending)\s+(?:more\s+)?than\s*[^0-9]*(\d+)/i', $text, $matches)) {
            $rules['total_spent'] = [
                'operator' => '>=',
                'value' => intval($matches[1])
            ];
            $segmentName = 'High Value Customers';
            $description = "Customers with lifetime spend above ₹{$matches[1]}";
        }

        // Inactive pattern
        if (preg_match('/(?:inactive|dormant|no\s+order|not\s+ordered|haven\'t\s+ordered|not\s+purchased|last\s+order)\s+.*?\s*(\d+)\s*(days?|weeks?|months?)/i', $text, $matches)) {
            $days = intval($matches[1]);
            if (str_contains($matches[2], 'week')) $days *= 7;
            if (str_contains($matches[2], 'month')) $days *= 30;
            
            $rules['inactive_days'] = [
                'operator' => '>=',
                'value' => $days
            ];
            
            if (isset($rules['total_spent'])) {
                $segmentName = 'High Value Dormant Customers';
                $description = "Customers with lifetime spend above ₹" . $rules['total_spent']['value'] . " and inactive for {$days}+ days";
            } else {
                $segmentName = 'Dormant Customers';
                $description = "Customers inactive for {$days}+ days";
            }
            $goal = 'Customer Re-engagement';
            $reasoning = 'WhatsApp generally delivers higher engagement rates for inactive high-value customers.';
        }

        // Discount pattern
        if (preg_match('/(\d+)%\s*(?:discount|off)/i', $text, $matches)) {
            $discount = $matches[1] . '%';
        }

        // City pattern
        if (preg_match('/(?:in|city|from)\s+(Delhi|Mumbai|Bengaluru|Pune|Hyderabad|Chennai)/i', $text, $matches)) {
            $rules['city'] = [
                'operator' => '=',
                'value' => $matches[1]
            ];
            $description .= " in {$matches[1]}";
        }

        // Channel pattern
        if (preg_match('/(whatsapp|email|sms|rcs)/i', $text, $matches)) {
            $channel = strtolower($matches[1]);
        }

        if (isset($rules['inactive_days'])) {
            $body = "Hi {{name}}, we miss you. Enjoy {$discount} off on your next purchase using code COMEBACK" . str_replace('%', '', $discount) . ".";
            $subject = "We miss you! Enjoy {$discount} off";
            $headline = "We Miss You";
            $cta = "Shop Now";
        } else {
            $body = "Hi {{name}}, enjoy an exclusive {$discount} off on our latest collection. Use code SAVE" . str_replace('%', '', $discount) . " at checkout.";
            $subject = "Exclusive {$discount} discount just for you";
            $headline = "Special Discount";
            $cta = "Claim Code";
        }

        $message = "{$headline}\n\n{$body}\n\n{$cta}";

        return [
            'segment_name' => $segmentName,
            'segment_description' => $description,
            'campaign_goal' => $goal,
            'rules' => $rules,
            'channel' => $channel,
            'reasoning' => $reasoning,
            'subject' => $subject,
            'headline' => $headline,
            'body' => $body,
            'cta' => $cta,
            'message' => $message,
        ];
    }
}
