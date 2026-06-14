<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SegmentService
{
    /**
     * Compile rules into a Customer Eloquent query builder.
     */
    public function getCustomersQuery(array $rules): Builder
    {
        $query = Customer::query();

        foreach ($rules as $field => $rule) {
            $value = $this->extractValue($rule);
            $operator = $this->extractOperator($rule, '=');

            switch ($field) {
                case 'total_spent':
                    $query->where('total_spent', $operator, $value);
                    break;

                case 'inactive_days':
                    $days = intval($value);
                    $dateOperator = $this->invertOperator($operator);

                    $query->where(function ($q) use ($days, $dateOperator) {
                        $q->where('last_order_date', $dateOperator, Carbon::now()->subDays($days));
                        if ($dateOperator === '<=' || $dateOperator === '<') {
                            $q->orWhereNull('last_order_date');
                        }
                    });
                    break;

                case 'last_order_days':
                    $days = intval($value);
                    $dateOperator = $this->invertOperator($operator);

                    $query->whereNotNull('last_order_date')
                          ->where('last_order_date', $dateOperator, Carbon::now()->subDays($days));
                    break;

                case 'city':
                    $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
                    if ($operator === '=' || $operator === 'like') {
                        $query->where('city', $like, '%' . $value . '%');
                    } else {
                        $query->where('city', $operator, $value);
                    }
                    break;

                case 'gender':
                    $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
                    if ($operator === '=' || $operator === 'like') {
                        $query->where('gender', $like, $value);
                    } else {
                        $query->where('gender', $operator, $value);
                    }
                    break;

                case 'order_count':
                    $count = intval($value);
                    // Filter based on relation count
                    $query->has('orders', $operator, $count);
                    break;

                case 'engagement_score':
                    $query->where('engagement_score', $operator, intval($value));
                    break;

                case 'age':
                    $years = intval($value);
                    $dateOperator = $this->invertOperator($operator);
                    $targetDate = Carbon::now()->subYears($years);

                    $query->whereNotNull('date_of_birth')
                          ->where('date_of_birth', $dateOperator, $targetDate);
                    break;

                case 'date_of_birth':
                    $operatorLower = strtolower(strval($operator));
                    $valueLower = strtolower(strval($value));
                    
                    $isWeek = str_contains($operatorLower, 'week') || str_contains($operatorLower, '7_day') || str_contains($operatorLower, '7day') ||
                              str_contains($valueLower, 'week') || str_contains($valueLower, '7_day') || str_contains($valueLower, '7day');
                              
                    $isMonth = str_contains($operatorLower, 'month') || str_contains($valueLower, 'month');
                               
                    $isToday = str_contains($operatorLower, 'today') || str_contains($valueLower, 'today');

                    if ($isWeek) {
                        $daysInWeek = [];
                        $temp = Carbon::now()->startOfWeek();
                        $endOfWeek = Carbon::now()->endOfWeek();
                        while ($temp->lte($endOfWeek)) {
                             $daysInWeek[] = $temp->format('m-d');
                             $temp->addDay();
                        }
                        
                        $query->whereNotNull('date_of_birth');
                        $dbType = \Illuminate\Support\Facades\DB::connection()->getDriverName();
                        if ($dbType === 'sqlite') {
                            $query->whereIn(\Illuminate\Support\Facades\DB::raw("strftime('%m-%d', date_of_birth)"), $daysInWeek);
                        } elseif ($dbType === 'mysql' || $dbType === 'mariadb') {
                            $query->whereIn(\Illuminate\Support\Facades\DB::raw("DATE_FORMAT(date_of_birth, '%m-%d')"), $daysInWeek);
                        } else {
                            $query->whereIn(\Illuminate\Support\Facades\DB::raw("to_char(date_of_birth, 'MM-DD')"), $daysInWeek);
                        }
                    } elseif ($isMonth) {
                        $currentMonth = Carbon::now()->format('m');
                        $query->whereNotNull('date_of_birth');
                        $dbType = \Illuminate\Support\Facades\DB::connection()->getDriverName();
                        if ($dbType === 'sqlite') {
                            $query->where(\Illuminate\Support\Facades\DB::raw("strftime('%m', date_of_birth)"), $currentMonth);
                        } elseif ($dbType === 'mysql' || $dbType === 'mariadb') {
                            $query->where(\Illuminate\Support\Facades\DB::raw("DATE_FORMAT(date_of_birth, '%m')"), $currentMonth);
                        } else {
                            $query->where(\Illuminate\Support\Facades\DB::raw("to_char(date_of_birth, 'MM')"), $currentMonth);
                        }
                    } elseif ($isToday) {
                        $todayStr = Carbon::now()->format('m-d');
                        $query->whereNotNull('date_of_birth');
                        $dbType = \Illuminate\Support\Facades\DB::connection()->getDriverName();
                        if ($dbType === 'sqlite') {
                            $query->where(\Illuminate\Support\Facades\DB::raw("strftime('%m-%d', date_of_birth)"), $todayStr);
                        } elseif ($dbType === 'mysql' || $dbType === 'mariadb') {
                            $query->where(\Illuminate\Support\Facades\DB::raw("DATE_FORMAT(date_of_birth, '%m-%d')"), $todayStr);
                        } else {
                            $query->where(\Illuminate\Support\Facades\DB::raw("to_char(date_of_birth, 'MM-DD')"), $todayStr);
                        }
                    } else {
                        $query->where('date_of_birth', $operator, $value);
                    }
                    break;
            }
        }

        return $query;
    }

    /**
     * Get matching customers from rules.
     */
    public function getCustomers(array $rules)
    {
        return $this->getCustomersQuery($rules)->get();
    }

    /**
     * Get total count of matching customers.
     */
    public function getCustomersCount(array $rules): int
    {
        return $this->getCustomersQuery($rules)->count();
    }

    /**
     * Get audience summary statistics for Step 5.
     */
    public function getAudienceStats(Builder $query): array
    {
        $customers = $query->get();
        $audienceSize = $customers->count();

        if ($audienceSize === 0) {
            return [
                'audience_size' => 0,
                'average_order_value' => 0,
                'average_customer_value' => 0,
                'average_engagement_score' => 0,
                'last_purchase_distribution' => [
                    '0-30_days' => 0,
                    '31-90_days' => 0,
                    '90+_days' => 0,
                ]
            ];
        }

        $avgCustomerValue = round($customers->avg('total_spent') ?? 0, 2);
        $avgEngagement = round($customers->avg('engagement_score') ?? 0, 1);

        // Calculate average order value for these customers
        $customerIds = $customers->pluck('id')->toArray();
        $avgOrderValue = round(Order::whereIn('customer_id', $customerIds)->avg('amount') ?? 0, 2);

        // Bucketed distribution of days since last purchase
        $distribution = [
            '0-30_days' => 0,
            '31-90_days' => 0,
            '90+_days' => 0,
        ];

        foreach ($customers as $customer) {
            if (!$customer->last_order_date) {
                $distribution['90+_days']++;
                continue;
            }

            $days = Carbon::parse($customer->last_order_date)->diffInDays(Carbon::now());
            if ($days <= 30) {
                $distribution['0-30_days']++;
            } elseif ($days <= 90) {
                $distribution['31-90_days']++;
            } else {
                $distribution['90+_days']++;
            }
        }

        return [
            'audience_size' => $audienceSize,
            'average_order_value' => $avgOrderValue,
            'average_customer_value' => $avgCustomerValue,
            'average_engagement_score' => $avgEngagement,
            'last_purchase_distribution' => $distribution,
        ];
    }

    /**
     * Helper to extract the rule value.
     */
    private function extractValue($rule)
    {
        if (is_array($rule) && isset($rule['value'])) {
            return $rule['value'];
        }
        return $rule;
    }

    /**
     * Helper to extract the rule operator.
     */
    private function extractOperator($rule, string $default = '=')
    {
        if (is_array($rule) && isset($rule['operator'])) {
            return $rule['operator'];
        }
        return $default;
    }

    /**
     * Invert comparison operator for date-based comparisons.
     */
    private function invertOperator(string $operator): string
    {
        switch ($operator) {
            case '>=': return '<=';
            case '<=': return '>=';
            case '>': return '<';
            case '<': return '>';
            default: return $operator;
        }
    }
}
