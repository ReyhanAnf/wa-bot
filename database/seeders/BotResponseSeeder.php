<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BotResponseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $responses = [
            [
                'keyword' => 'sayang',
                'response' => "Iya sayang? Eh maksudnya... ada yang bisa dibantu? 😳",
                'match_type' => 'contains',
                'is_active' => true,
            ],
            [
                'keyword' => 'kangen',
                'response' => "Aku juga kangen... sama kasur. 😴 Tapi kamu jangan lupa istirahat ya!",
                'match_type' => 'contains',
                'is_active' => true,
            ],
        ];

        foreach ($responses as $response) {
            \App\Models\BotResponse::create($response);
        }
    }
}
