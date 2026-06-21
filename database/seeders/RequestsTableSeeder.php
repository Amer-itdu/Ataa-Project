<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{
    User,
    Beneficiary,
    RequestModel,
    Patient,
    Orphan,
    SchoolStudent,
    UniversityStudent,
    Governorate,
    Region
};

class RequestsTableSeeder extends Seeder
{
    public function run(): void
    {
        $marwa = User::where('email', 'marwa.alsaour@example.com')->first();
        $salam = User::where('email', 'salam.labbad@example.com')->first();

        // Helper to get governorate + region
        $getLocation = function ($govName, $regionName) {
            $gov = Governorate::where('name', $govName)->first();
            $region = Region::where('name', $regionName)->first();
            return [$gov?->id, $region?->id];
        };

        /*
        |--------------------------------------------------------------------------
        | MARWA REQUESTS
        |--------------------------------------------------------------------------
        */
        if ($marwa) {

            // Beneficiary 1
            [$gov1, $reg1] = $getLocation('Damascus', 'Mezzeh');

            $b1 = Beneficiary::updateOrCreate(
                ['national_id' => '111111111'],
                [
                    'full_name'      => 'Khaled Mansour',
                    'governorate_id' => $gov1,
                    'region_id'      => $reg1,
                    'email'          => 'khaled.m@example.com',
                    'phone'          => '0999001111',
                ]
            );

            // Patient Request
            $r1 = RequestModel::updateOrCreate(
                [
                    'user_id'        => $marwa->id,
                    'beneficiary_id' => $b1->id,
                    'request_type'   => 'patient',
                ],
                [
                    'status'         => 'pending',
                    'description'    => 'Patient request for Khaled.',
                    'required_amount'=> 500,
                    'status_request' => 'open',
                ]
            );

            Patient::updateOrCreate(
                ['request_id' => $r1->id],
                [
                    'medical_report'       => 'report.pdf',
                    'national_id_document' => 'id.jpg',
                ]
            );

            // School Request
            $r2 = RequestModel::updateOrCreate(
                [
                    'user_id'        => $marwa->id,
                    'beneficiary_id' => $b1->id,
                    'request_type'   => 'school',
                ],
                [
                    'status'         => 'pending',
                    'description'    => 'School support request for Khaled.',
                    'required_amount'=> 0,
                    'status_request' => 'open',
                ]
            );

            SchoolStudent::updateOrCreate(
                ['request_id' => $r2->id],
                [
                    'academic_grade'    => '7th Grade',
                    'school_name'       => 'Al-Amal School',
                    'family_book_photo' => 'family.jpg',
                ]
            );

            // Beneficiary 2
            [$gov2, $reg2] = $getLocation('Homs', 'Waer');

            $b2 = Beneficiary::updateOrCreate(
                ['national_id' => '222222222'],
                [
                    'full_name'      => 'Rama Saeed',
                    'governorate_id' => $gov2,
                    'region_id'      => $reg2,
                    'email'          => 'rama.s@example.com',
                    'phone'          => '0999002222',
                ]
            );

            // Patient Request 2
            $r3 = RequestModel::updateOrCreate(
                [
                    'user_id'        => $marwa->id,
                    'beneficiary_id' => $b2->id,
                    'request_type'   => 'patient',
                ],
                [
                    'status'         => 'pending',
                    'description'    => 'Patient request for Rama.',
                    'required_amount'=> 700,
                    'status_request' => 'open',
                ]
            );

            Patient::updateOrCreate(
                ['request_id' => $r3->id],
                [
                    'medical_report'       => 'report2.pdf',
                    'national_id_document' => 'id2.jpg',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SALAM REQUESTS
        |--------------------------------------------------------------------------
        */
        if ($salam) {

            // Beneficiary 3
            [$gov3, $reg3] = $getLocation('Latakia', 'Saliba');

            $b3 = Beneficiary::updateOrCreate(
                ['national_id' => '333333333'],
                [
                    'full_name'      => 'Salma Ahmed',
                    'governorate_id' => $gov3,
                    'region_id'      => $reg3,
                    'email'          => 'salma.a@example.com',
                    'phone'          => '0999003333',
                ]
            );

            // Orphan Request
            $r4 = RequestModel::updateOrCreate(
                [
                    'user_id'        => $salam->id,
                    'beneficiary_id' => $b3->id,
                    'request_type'   => 'orphan',
                ],
                [
                    'status'         => 'pending',
                    'description'    => 'Orphan request for Salma.',
                    'required_amount'=> 0,
                    'status_request' => 'open',
                ]
            );

            Orphan::updateOrCreate(
                ['request_id' => $r4->id],
                [
                    'family_booklet'           => 'family.pdf',
                    'father_death_certificate' => 'death.pdf',
                ]
            );

            // University Request
            $r5 = RequestModel::updateOrCreate(
                [
                    'user_id'        => $salam->id,
                    'beneficiary_id' => $b3->id,
                    'request_type'   => 'university',
                ],
                [
                    'status'         => 'pending',
                    'description'    => 'University support request for Salma.',
                    'required_amount'=> 0,
                    'status_request' => 'open',
                ]
            );

            UniversityStudent::updateOrCreate(
                ['request_id' => $r5->id],
                [
                    'academic_year'       => '3rd Year',
                    'university_id_photo' => 'uni.jpg',
                    'support_type'        => 'tuitionassistance',
                ]
            );

            // Beneficiary 4
            [$gov4, $reg4] = $getLocation('Aleppo', 'Jamiliyah');

            $b4 = Beneficiary::updateOrCreate(
                ['national_id' => '444444444'],
                [
                    'full_name'      => 'Omar Saad',
                    'governorate_id' => $gov4,
                    'region_id'      => $reg4,
                    'email'          => 'omar.s@example.com',
                    'phone'          => '0999004444',
                ]
            );

            // Orphan Request 2
            $r6 = RequestModel::updateOrCreate(
                [
                    'user_id'        => $salam->id,
                    'beneficiary_id' => $b4->id,
                    'request_type'   => 'orphan',
                ],
                [
                    'status'         => 'pending',
                    'description'    => 'Orphan request for Omar.',
                    'required_amount'=> 0,
                    'status_request' => 'open',
                ]
            );

            Orphan::updateOrCreate(
                ['request_id' => $r6->id],
                [
                    'family_booklet'           => 'family2.pdf',
                    'father_death_certificate' => 'death2.pdf',
                ]
            );
        }
    }
}
