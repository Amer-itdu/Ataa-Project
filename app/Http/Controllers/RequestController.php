<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrphanRequest;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\StoreUniversityRequest;
use App\Models\Beneficiary;
use App\Models\Orphan;
use App\Models\RequestModel;
use App\Models\Patient;
use App\Models\SchoolStudent;
use App\Models\UniversityStudent;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    public function storePatientRequest(StorePatientRequest $request)
    {
        // 1) ابحث عن المستفيد أو أنشئه
        $beneficiary = Beneficiary::firstOrCreate(
            [
                'full_name'   => $request->full_name,
                'mother_name' => $request->mother_name,
            ],
            [
                'address' => $request->address,
                'email'   => $request->email,
                'phone'   => $request->phone,
            ]
        );

        // 2) أنشئ الطلب الأساسي
        $requestModel = RequestModel::create([
            'user_id'        => Auth::id(),
            'beneficiary_id' => $beneficiary->id,
            'request_type'   => 'patient',
            'status'         => 'pending',
            'description'    => $request->description,
        ]);

        // 3) رفع الملفات
        $medicalReportPath = $request->file('medical_report')
            ->store('medical_reports', 'public');

        $nationalIdPath = $request->file('national_id_photo')
            ->store('national_ids', 'public');

        // 4) إنشاء سجل المريض وربطه بالطلب
        $patient = Patient::create([
            'request_id'        => $requestModel->id,
            'medical_condition' => $request->medical_condition,
            'required_amount'   => $request->required_amount,
            'medical_report'    => $medicalReportPath,
            'national_id_photo' => $nationalIdPath,
        ]);

        return response()->json([
            'message'     => 'Patient request created successfully',
            'beneficiary' => $beneficiary,
            'request'     => $requestModel,
            'patient'     => $patient,
        ], 201);
    }
public function storeOrphanRequest(StoreOrphanRequest $request)
{
    // 1) ابحث عن المستفيد أو أنشئه
    $beneficiary = Beneficiary::firstOrCreate(
        [
            'full_name'   => $request->full_name,
            'mother_name' => $request->mother_name,
        ],
        [
            'address' => $request->address,
            'email'   => $request->email,
            'phone'   => $request->phone,
        ]
    );

    // 2) إنشاء الطلب الأساسي
    $requestModel = RequestModel::create([
        'user_id'        => Auth::id(),
        'beneficiary_id' => $beneficiary->id,
        'request_type'   => 'orphan',
        'status'         => 'pending',
        'description'    => $request->description,
    ]);

    // 3) رفع الملفات
    $familyBookletPath = $request->file('family_booklet')
        ->store('family_booklets', 'public');

    $deathCertificatePath = $request->file('father_death_certificate')
        ->store('death_certificates', 'public');

    // 4) إنشاء سجل اليتيم وربطه بالطلب
    $orphan = Orphan::create([
        'request_id'             => $requestModel->id,
        'family_booklet'         => $familyBookletPath,
        'father_death_certificate' => $deathCertificatePath,
    ]);

    return response()->json([
        'message'     => 'Orphan request created successfully',
        'beneficiary' => $beneficiary,
        'request'     => $requestModel,
        'orphan'      => $orphan,
    ], 201);
}

public function storeSchoolRequest(StoreSchoolRequest $request)
{
    // 1) ابحث عن المستفيد أو أنشئه
    $beneficiary = Beneficiary::firstOrCreate(
        [
            'full_name'   => $request->full_name,
            'mother_name' => $request->mother_name,
        ],
        [
            'address' => $request->address,
            'email'   => $request->email,
            'phone'   => $request->phone,
        ]
    );

    // 2) إنشاء الطلب الأساسي
    $requestModel = RequestModel::create([
        'user_id'        => Auth::id(),
        'beneficiary_id' => $beneficiary->id,
        'request_type'   => 'school',
        'status'         => 'pending',
        'description'    => $request->description,
    ]);

    // 3) رفع الملفات
    $familyBookPhotoPath = $request->file('family_book_photo')
        ->store('family_book_photos', 'public');

    // 4) إنشاء سجل الطالب المدرسي وربطه بالطلب
    $school = SchoolStudent::create([
        'request_id'      => $requestModel->id,
        'academic_grade'  => $request->academic_grade,
        'school_name'     => $request->school_name,
        'family_book_photo' => $familyBookPhotoPath,
    ]);

    return response()->json([
        'message'     => 'School student request created successfully',
        'beneficiary' => $beneficiary,
        'request'     => $requestModel,
        'school'      => $school,
    ], 201);
}


public function storeUniversityRequest(StoreUniversityRequest $request)
{
    // 1) ابحث عن المستفيد أو أنشئه
    $beneficiary = Beneficiary::firstOrCreate(
        [
            'full_name'   => $request->full_name,
            'mother_name' => $request->mother_name,
        ],
        [
            'address' => $request->address,
            'email'   => $request->email,
            'phone'   => $request->phone,
        ]
    );

    // 2) إنشاء الطلب الأساسي
    $requestModel = RequestModel::create([
        'user_id'        => Auth::id(),
        'beneficiary_id' => $beneficiary->id,
        'request_type'   => 'university',
        'status'         => 'pending',
        'description'    => $request->description,
    ]);

    // 3) رفع صورة بطاقة الجامعة
    $universityIdPath = $request->file('university_id_photo')
        ->store('university_id_photos', 'public');

    // 4) إنشاء سجل الطالب الجامعي وربطه بالطلب
    $universityStudent = UniversityStudent::create([
        'request_id'          => $requestModel->id,
        'academic_year'       => $request->academic_year,
        'university_id_photo' => $universityIdPath,
        'support_type'        => $request->support_type,
    ]);

    return response()->json([
        'message'           => 'University student request created successfully',
        'beneficiary'       => $beneficiary,
        'request'           => $requestModel,
        'universityStudent' => $universityStudent,
    ], 201);
}

    }
