<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $indianFirstNamesMale = [
            'Aarav', 'Kabir', 'Vivaan', 'Aditya', 'Vihaan', 'Arjun', 'Sai', 'Reyansh', 'Ishan', 
            'Shaurya', 'Aman', 'Sahil', 'Karan', 'Rahul', 'Rohan', 'Vikram', 'Sanjay', 'Nikhil', 
            'Dev', 'Abhishek', 'Gaurav', 'Manish', 'Rajesh', 'Sandeep', 'Amit', 'Vijay', 'Kunal',
            'Rishi', 'Karthik', 'Pranav', 'Siddharth', 'Varun', 'Yash', 'Rudra', 'Dhruv'
        ];

        $indianFirstNamesFemale = [
            'Aaradhya', 'Ananya', 'Diya', 'Pihu', 'Pari', 'Ira', 'Sana', 'Riya', 'Neha', 'Priya', 
            'Divya', 'Anjali', 'Pooja', 'Kiran', 'Maya', 'Sneha', 'Shruti', 'Tanvi', 'Preeti', 
            'Nisha', 'Zara', 'Meera', 'Ridhi', 'Simran', 'Ishita', 'Kriti', 'Aditi', 'Shreya', 
            'Nisha', 'Swati', 'Kajal', 'Deepika', 'Priyanka', 'Kavya', 'Ritu'
        ];

        $indianLastNames = [
            'Sharma', 'Patel', 'Verma', 'Gupta', 'Singh', 'Kumar', 'Joshi', 'Mehta', 'Shah', 
            'Rao', 'Nair', 'Iyer', 'Reddy', 'Choudhury', 'Das', 'Banerjee', 'Chatterjee', 
            'Kulkarni', 'Patil', 'Deshmukh', 'Bhat', 'Sen', 'Roy', 'Bose', 'Pillai', 'Menon',
            'Sethi', 'Malhotra', 'Kapoor', 'Khanna', 'Mishra', 'Trivedi', 'Pandey', 'Dwivedi'
        ];

        $cities = ['Mumbai', 'Bengaluru', 'Delhi', 'Pune', 'Hyderabad', 'Chennai', 'Kolkata', 'Ahmedabad'];

        $customers = [];

        for ($i = 0; $i < 500; $i++) {
            $gender = rand(0, 1) === 0 ? 'Male' : 'Female';
            $firstName = $gender === 'Male' 
                ? $indianFirstNamesMale[array_rand($indianFirstNamesMale)]
                : $indianFirstNamesFemale[array_rand($indianFirstNamesFemale)];
            $lastName = $indianLastNames[array_rand($indianLastNames)];
            
            $name = $firstName . ' ' . $lastName;
            $email = strtolower($firstName . '.' . $lastName . rand(10, 999) . '@example.com');
            
            // Indian mobile number start digits
            $phone = rand(700, 999) . rand(100, 999) . rand(1000, 9999);
            
            $city = $cities[array_rand($cities)];
            
            // Random birth date between 18 and 60 years ago
            $dob = Carbon::now()->subYears(rand(18, 60))->subDays(rand(0, 365));

            $customers[] = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'city' => $city,
                'gender' => $gender,
                'date_of_birth' => $dob->format('Y-m-d'),
                'total_spent' => 0.00,
                'last_order_date' => null,
                'engagement_score' => rand(10, 95),
                'created_at' => Carbon::now()->subDays(rand(10, 180)),
                'updated_at' => Carbon::now(),
            ];
        }

        // Chunk insert for speed
        foreach (array_chunk($customers, 100) as $chunk) {
            Customer::insert($chunk);
        }
    }
}
