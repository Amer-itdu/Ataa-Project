<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Orphan;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class DonationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $donorMap = [];

        foreach ([
            'milad.hamad@example.com' => false,
            'sedra.jlilaty@example.com' => false,
            'marwa.alsaour@example.com' => true,
            'salam.labbad@example.com' => false,
        ] as $email => $anonymous) {
            $user = User::where('email', $email)->first();

            if (!$user) {
                continue;
            }

            $donorMap[$email] = Donor::updateOrCreate(
                ['user_id' => $user->id],
                ['anonymous' => $anonymous]
            );
        }

        $campaignA = Campaign::query()->first();
        $campaignB = Campaign::query()->skip(1)->first();
        $patient = Patient::query()->first();
        $orphan = Orphan::query()->first();

        $records = [
            [
                'donor' => $donorMap['milad.hamad@example.com'] ?? null,
                'type' => Campaign::class,
                'id' => $campaignA?->id,
                'amount' => 120.50,
                'original_amount' => 450,
                'original_currency' => 'SAR',
            ],
            [
                'donor' => $donorMap['sedra.jlilaty@example.com'] ?? null,
                'type' => Campaign::class,
                'id' => $campaignB?->id,
                'amount' => 85.00,
                'original_amount' => 320,
                'original_currency' => 'AED',
            ],
            [
                'donor' => $donorMap['marwa.alsaour@example.com'] ?? null,
                'type' => Patient::class,
                'id' => $patient?->id,
                'amount' => 50.00,
                'original_amount' => 200,
                'original_currency' => 'SYP',
            ],
            [
                'donor' => $donorMap['salam.labbad@example.com'] ?? null,
                'type' => Orphan::class,
                'id' => $orphan?->id,
                'amount' => 200.00,
                'original_amount' => 750,
                'original_currency' => 'EGP',
            ],
        ];

        foreach ($records as $record) {
            if (!$record['donor'] || !$record['id']) {
                continue;
            }

            Donation::firstOrCreate(
                [
                    'donor_id' => $record['donor']->id,
                    'donationable_id' => $record['id'],
                    'donationable_type' => $record['type'],
                    'original_amount' => $record['original_amount'],
                    'original_currency' => $record['original_currency'],
                ],
                [
                    'amount' => $record['amount'],
                    'currency' => 'USD',
                ]
            );
        }

        foreach (Campaign::query()->get() as $campaign) {
            $campaign->update([
                'amount_collected' => (float) $campaign->donations()->sum('amount'),
            ]);
        }
    }
}
