<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    protected static array $companies = [
        ['name' => 'Tata Motors', 'slug' => 'tata-motors'],
        ['name' => 'Mahindra Logistics', 'slug' => 'mahindra-logistics'],
        ['name' => 'ABB Transport', 'slug' => 'abb-transport'],
        ['name' => 'Reliance Freight', 'slug' => 'reliance-freight'],
        ['name' => 'Adani Ports', 'slug' => 'adani-ports'],
        ['name' => 'Blue Dart', 'slug' => 'blue-dart'],
        ['name' => 'VRL Logistics', 'slug' => 'vrl-logistics'],
        ['name' => 'TCI Express', 'slug' => 'tci-express'],
        ['name' => 'Safexpress', 'slug' => 'safexpress'],
        ['name' => 'Jindal Steel', 'slug' => 'jindal-steel'],
        ['name' => 'Ultratech Cement', 'slug' => 'ultratech-cement'],
        ['name' => 'Ambuja Cements', 'slug' => 'ambuja-cements'],
    ];

    public function run(): void
    {
        foreach (self::$companies as $company) {
            Company::create(array_merge($company, [
                'gstin' => fake()->optional()->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}[Z]{1}[0-9A-Z]{1}'),
                'contact_info' => fake()->optional()->phoneNumber(),
                'is_active' => true,
            ]));
        }
    }
}
