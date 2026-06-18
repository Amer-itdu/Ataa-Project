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
    UniversityStudent
};

class RequestsTableSeeder extends Seeder
{
    public function run(): void
    {
        $marwa = User::where('email', 'marwa.alsaour@example.com')->first();
        $salam = User::where('email', 'salam.labbad@example.com')->first();

        /*
        |--------------------------------------------------------------------------
        | MARWA REQUESTS
        |--------------------------------------------------------------------------
        */
        if ($marwa) {

            // Beneficiary 1
            $b1 = Beneficiary::updateOrCreate(
                ['email' => 'khaled.m@example.com'],
                [
                    'full_name' => 'Khaled Mansour',
                    'address'   => 'Damascus',
                    'phone'     => '0999001111',
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
                ]
            );

            Patient::updateOrCreate(
                ['request_id' => $r1->id],
                [
                    'medical_report' => 'report.pdf',
                    'national_id'    => 'id.jpg',
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
            $b2 = Beneficiary::updateOrCreate(
                ['email' => 'rama.s@example.com'],
                [
                    'full_name' => 'Rama Saeed',
                    'address'   => 'Homs',
                    'phone'     => '0999002222',
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
                ]
            );

            Patient::updateOrCreate(
                ['request_id' => $r3->id],
                [
                    'medical_report' => 'report2.pdf',
                    'national_id'    => 'id2.jpg',
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
            $b3 = Beneficiary::updateOrCreate(
                ['email' => 'salma.a@example.com'],
                [
                    'full_name' => 'Salma Ahmed',
                    'address'   => 'Latakia',
                    'phone'     => '0999003333',
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
            $b4 = Beneficiary::updateOrCreate(
                ['email' => 'omar.s@example.com'],
                [
                    'full_name' => 'Omar Saad',
                    'address'   => 'Aleppo',
                    'phone'     => '0999004444',
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
