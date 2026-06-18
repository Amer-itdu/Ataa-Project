<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrphanRequest;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\StoreUniversityRequest;
use App\Http\Requests\AcceptRequestRequest;
use App\Models\Beneficiary;
use App\Models\Orphan;
use App\Models\RequestModel;
use App\Models\Patient;
use App\Models\SchoolStudent;
use App\Models\UniversityStudent;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PATIENT REQUEST
    |--------------------------------------------------------------------------
    */
    public function storePatientRequest(StorePatientRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        // 1) Beneficiary data
        if ($isAdmin) {
            $beneficiaryData = [
                'full_name' => $request->full_name,
                'address'   => $request->address,
                'email'     => $request->email,
                'phone'     => $request->phone,
            ];

            $requiredAmount = $request->required_amount;
            $status = 'accepted';
        } else {

            if ($request->is_self === "true") {
                $beneficiaryData = [
                    'full_name' => $request->full_name,
                    'address'   => $request->address,
                    'email'     => $user->email,
                    'phone'     => $user->phone,
                ];
            } else {
                $beneficiaryData = [
                    'full_name' => $request->full_name,
                    'address'   => $request->address,
                    'email'     => $request->email,
                    'phone'     => $request->phone,
                ];
            }

            $requiredAmount = $request->required_amount ?? 0;
            $status = 'pending';
        }

        // 2) Beneficiary
        $beneficiary = Beneficiary::updateOrCreate(
            ['full_name' => $request->full_name],
            $beneficiaryData
        );

        // 3) personal_picture (admin only)
        $personalPicturePath = null;
        if ($isAdmin && $request->hasFile('personal_picture')) {
            $personalPicturePath = $request->file('personal_picture')
                ->store('personal_pictures', 'public');
        }

        // 4) RequestModel
        $requestModel = RequestModel::create([
            'user_id'          => $user->id,
            'beneficiary_id'   => $beneficiary->id,
            'request_type'     => 'patient',
            'status'           => $status,
            'title'            => $isAdmin ? $request->title : null,
            'description'      => $request->description,
            'personal_picture' => $personalPicturePath,
            'required_amount'  => $requiredAmount,
            'status_request'   => 'open',
        ]);

        // 5) Files
        $medicalReportPath = $request->file('medical_report')
            ->store('medical_reports', 'public');

        $nationalIdPath = $request->file('national_id')
            ->store('national_ids', 'public');

        // 6) Patient record
        $patient = Patient::create([
            'request_id'      => $requestModel->id,
            'medical_report'  => $medicalReportPath,
            'national_id'     => $nationalIdPath,
        ]);

        return response()->json([
            'message'     => 'Patient request created successfully',
            'beneficiary' => $beneficiary,
            'request'     => $requestModel,
            'patient'     => $patient,
        ], 201);
    }
    /*
    |--------------------------------------------------------------------------
    | SCHOOL REQUEST
    |--------------------------------------------------------------------------
    */
    public function storeSchoolRequest(StoreSchoolRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        // 1) Beneficiary
        if ($isAdmin) {
            $beneficiaryData = [
                'full_name' => $request->full_name,
                'address'   => $request->address,
                'email'     => $request->email,
                'phone'     => $request->phone,
            ];

            $requiredAmount = $request->required_amount;
            $status = 'accepted';
        } else {

            $beneficiaryData = [
                'full_name' => $request->full_name,
                'address'   => $request->address,
                'email'     => $user->email,
                'phone'     => $user->phone,
            ];

            $requiredAmount = 0;
            $status = 'pending';
        }

        // 2) Beneficiary
        $beneficiary = Beneficiary::updateOrCreate(
            ['full_name' => $request->full_name],
            $beneficiaryData
        );

        // 3) personal_picture (admin only)
        $personalPicturePath = null;
        if ($isAdmin && $request->hasFile('personal_picture')) {
            $personalPicturePath = $request->file('personal_picture')
                ->store('personal_pictures', 'public');
        }

        // 4) RequestModel
        $requestModel = RequestModel::create([
            'user_id'          => $user->id,
            'beneficiary_id'   => $beneficiary->id,
            'request_type'     => 'school',
            'status'           => $status,
            'title'            => $isAdmin ? $request->title : null,
            'description'      => $request->description,
            'personal_picture' => $personalPicturePath,
            'required_amount'  => $requiredAmount,
            'status_request'   => 'open',
        ]);

        // 5) Files
        $familyBookPhotoPath = $request->file('family_book_photo')
            ->store('family_book_photos', 'public');

        // 6) School record
        $school = SchoolStudent::create([
            'request_id'        => $requestModel->id,
            'academic_grade'    => $request->academic_grade,
            'school_name'       => $request->school_name,
            'family_book_photo' => $familyBookPhotoPath,
        ]);

        return response()->json([
            'message'     => 'School request created successfully',
            'beneficiary' => $beneficiary,
            'request'     => $requestModel,
            'school'      => $school,
        ], 201);
    }



    //|--------------------------------------------------------------------------
    //| UNIVERSITY REQUEST
    //|--------------------------------------------------------------------------s
    public function storeUniversityRequest(StoreUniversityRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        // 1) Beneficiary
        if ($isAdmin) {
            $beneficiaryData = [
                'full_name' => $request->full_name,
                'address'   => $request->address,
                'email'     => $request->email,
                'phone'     => $request->phone,
            ];

            $requiredAmount = $request->required_amount;
            $status = 'accepted';
        } else {

            $beneficiaryData = [
                'full_name' => $request->full_name,
                'address'   => $request->address,
                'email'     => $user->email,
                'phone'     => $user->phone,
            ];

            $requiredAmount = 0;
            $status = 'pending';
        }

        // 2) Beneficiary
        $beneficiary = Beneficiary::updateOrCreate(
            ['full_name' => $request->full_name],
            $beneficiaryData
        );

        // 3) personal_picture (admin only)
        $personalPicturePath = null;
        if ($isAdmin && $request->hasFile('personal_picture')) {
            $personalPicturePath = $request->file('personal_picture')
                ->store('personal_pictures', 'public');
        }

        // 4) RequestModel
        $requestModel = RequestModel::create([
            'user_id'          => $user->id,
            'beneficiary_id'   => $beneficiary->id,
            'request_type'     => 'university',
            'status'           => $status,
            'title'            => $isAdmin ? $request->title : null,
            'description'      => $request->description,
            'personal_picture' => $personalPicturePath,
            'required_amount'  => $requiredAmount,
            'status_request'   => 'open',
        ]);

        // 5) Files
        $universityIdPath = $request->file('university_id_photo')
            ->store('university_id_photos', 'public');

        // 6) University record
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
    public function storeOrphanRequest(StoreOrphanRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        // 1) بيانات المستفيد
        if ($isAdmin) {

            $beneficiaryData = [
                'full_name' => $request->full_name,
                'address'   => $request->address,
                'email'     => null, // ممنوع
                'phone'     => $request->phone,
            ];

            $requiredAmount = $request->required_amount;
            $status = 'accepted';
        } else {

            $beneficiaryData = [
                'full_name' => $request->full_name,
                'address'   => $request->address,
                'email'     => null, // ممنوع
                'phone'     => $request->phone,
            ];

            $requiredAmount = 0;
            $status = 'pending';
        }

        // 2) إنشاء أو تحديث المستفيد
        $beneficiary = Beneficiary::updateOrCreate(
            ['full_name' => $request->full_name],
            $beneficiaryData
        );

        // 3) personal_picture فقط للأدمن
        $personalPicturePath = null;

        if ($isAdmin && $request->hasFile('personal_picture')) {
            $personalPicturePath = $request->file('personal_picture')
                ->store('personal_pictures', 'public');
        }

        // 4) إنشاء الطلب
        $requestModel = RequestModel::create([
            'user_id'          => $user->id,
            'beneficiary_id'   => $beneficiary->id,
            'request_type'     => 'orphan',
            'status'           => $status,
            'title'            => $isAdmin ? $request->title : null,
            'description'      => $request->description,
            'personal_picture' => $personalPicturePath,
            'required_amount'  => $requiredAmount,
            'status_request'   => 'open',
        ]);

        // 5) رفع الملفات
        $familyBookletPath = $request->file('family_booklet')
            ->store('family_booklets', 'public');

        $deathCertificatePath = $request->file('father_death_certificate')
            ->store('death_certificates', 'public');

        // 6) إنشاء سجل اليتيم
        $orphan = Orphan::create([
            'request_id'               => $requestModel->id,
            'family_booklet'           => $familyBookletPath,
            'father_death_certificate' => $deathCertificatePath,
        ]);

        return response()->json([
            'message'     => 'Orphan request created successfully',
            'beneficiary' => $beneficiary,
            'request'     => $requestModel,
            'orphan'      => $orphan,
        ], 201);
    }
    public function getPendingRequests()
    {
        $requests = RequestModel::with([
            'beneficiary',
            'patient',
            'orphan',
            'schoolStudent',
            'universityStudent'
        ])
            ->where('status', 'pending')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $requests
        ]);
    }
    public function getPendingPatients()
    {
        $requests = RequestModel::with(['beneficiary', 'patient'])
            ->where('status', 'pending')
            ->where('request_type', 'patient')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $requests
        ]);
    }
    public function getPendingOrphans()
    {
        $requests = RequestModel::with(['beneficiary', 'orphan'])
            ->where('status', 'pending')
            ->where('request_type', 'orphan')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $requests
        ]);
    }
    public function getPendingSchool()
    {
        $requests = RequestModel::with(['beneficiary', 'schoolStudent'])
            ->where('status', 'pending')
            ->where('request_type', 'school')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $requests
        ]);
    }
    public function getPendingUniversity()
    {
        $requests = RequestModel::with(['beneficiary', 'universityStudent'])
            ->where('status', 'pending')
            ->where('request_type', 'university')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $requests
        ]);
    }
    public function getOpenAcceptedRequests()
    {
        $requests = RequestModel::with([
            'beneficiary',
            'patient.donations',
            'orphan.donations',
            'schoolStudent.donations',
            'universityStudent.donations'
        ])
            ->where('status', 'accepted')          // الحالة مقبولة
            ->where('status_request', 'open')      // ولسا مفتوحة
            ->get()
            ->map(function ($req) {

                // تحديد نوع الحالة
                $target = match ($req->request_type) {
                    'patient'    => $req->patient,
                    'orphan'     => $req->orphan,
                    'school'     => $req->schoolStudent,
                    'university' => $req->universityStudent,
                };

                // مجموع التبرعات
                $donated = $target->donations()
                    ->where('status', 'approved')
                    ->sum('amount');

                // المطلوب
                $required = $req->required_amount;

                // نسبة التقدم
                $progress = $required > 0
                    ? round(($donated / $required) * 100, 2)
                    : 0;

                // كم باقي
                $remaining = max($required - $donated, 0);

                // إضافة القيم للنتيجة
                $req->donated_amount = $donated;
                $req->remaining_amount = $remaining;
                $req->progress_percentage = $progress;

                return $req;
            });

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }
    public function getOpenAcceptedPatients()
    {
        $requests = RequestModel::with(['patient.donations', 'beneficiary'])
            ->where('status', 'accepted')
            ->where('status_request', 'open')
            ->where('request_type', 'patient')
            ->get()
            ->map(function ($req) {

                $target = $req->patient;

                $donated = $target->donations()->where('status', 'approved')->sum('amount');
                $required = $req->required_amount;

                $req->donated_amount = $donated;
                $req->remaining_amount = max($required - $donated, 0);
                $req->progress_percentage = $required > 0 ? round(($donated / $required) * 100, 2) : 0;

                return $req;
            });

        return response()->json($requests);
    }
    public function getOpenAcceptedOrphans()
    {
        $requests = RequestModel::with(['orphan.donations', 'beneficiary'])
            ->where('status', 'accepted')
            ->where('status_request', 'open')
            ->where('request_type', 'orphan')
            ->get()
            ->map(function ($req) {

                $target = $req->orphan;

                $donated = $target->donations()->where('status', 'approved')->sum('amount');
                $required = $req->required_amount;

                $req->donated_amount = $donated;
                $req->remaining_amount = max($required - $donated, 0);
                $req->progress_percentage = $required > 0 ? round(($donated / $required) * 100, 2) : 0;

                return $req;
            });

        return response()->json($requests);
    }
    public function getOpenAcceptedSchoolStudents()
    {
        $requests = RequestModel::with(['schoolStudent.donations', 'beneficiary'])
            ->where('status', 'accepted')
            ->where('status_request', 'open')
            ->where('request_type', 'school')
            ->get()
            ->map(function ($req) {

                $target = $req->schoolStudent;

                $donated = $target->donations()->where('status', 'approved')->sum('amount');
                $required = $req->required_amount;

                $req->donated_amount = $donated;
                $req->remaining_amount = max($required - $donated, 0);
                $req->progress_percentage = $required > 0 ? round(($donated / $required) * 100, 2) : 0;

                return $req;
            });

        return response()->json($requests);
    }
    public function getOpenAcceptedUniversityStudents()
    {
        $requests = RequestModel::with(['universityStudent.donations', 'beneficiary'])
            ->where('status', 'accepted')
            ->where('status_request', 'open')
            ->where('request_type', 'university')
            ->get()
            ->map(function ($req) {

                $target = $req->universityStudent;

                $donated = $target->donations()->where('status', 'approved')->sum('amount');
                $required = $req->required_amount;

                $req->donated_amount = $donated;
                $req->remaining_amount = max($required - $donated, 0);
                $req->progress_percentage = $required > 0 ? round(($donated / $required) * 100, 2) : 0;

                return $req;
            });

        return response()->json($requests);
    }
    public function closeRequest($id)
    {
        $req = RequestModel::findOrFail($id);

        // إذا كانت مسكرة مسبقاً
        if ($req->status_request === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Request already closed.'
            ], 400);
        }

        // إغلاق الحالة
        $req->update([
            'status_request' => 'closed'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Request closed successfully.',
            'request_id' => $req->id
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCEPT REQUEST & UPDATE INFO
    |--------------------------------------------------------------------------
    */
    public function acceptRequest(AcceptRequestRequest $request, $id)
    {
        // التحقق من أن المستخدم أدمن
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can accept requests.'
            ], 403);
        }

        // البحث عن الطلب
        $requestModel = RequestModel::findOrFail($id);

        // التحقق من أن الحالة بحالة pending أو مرفوضة سابقاً
        if ($requestModel->status === 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'This request is already accepted.'
            ], 400);
        }

        // إعداد البيانات المراد تحديثها
        $updateData = [
            'status' => 'accepted',
        ];

        // تحديث title إذا تم توفيره
        if ($request->has('title') && !empty($request->title)) {
            $updateData['title'] = $request->title;
        }

        // تحديث description إذا تم توفيره
        if ($request->has('description') && !empty($request->description)) {
            $updateData['description'] = $request->description;
        }

        // تحديث required_amount إذا تم توفيره
        if ($request->has('required_amount') && $request->required_amount !== null) {
            $updateData['required_amount'] = $request->required_amount;
        }

        // تحديث personal_picture إذا تم رفع صورة جديدة
        if ($request->hasFile('personal_picture')) {
            // حذف الصورة القديمة إذا كانت موجودة
            if ($requestModel->personal_picture) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($requestModel->personal_picture);
            }

            // رفع الصورة الجديدة
            $personalPicturePath = $request->file('personal_picture')
                ->store('personal_pictures', 'public');
            $updateData['personal_picture'] = $personalPicturePath;
        }

        // تحديث الطلب
        $requestModel->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Request accepted and updated successfully.',
            'request' => $requestModel
        ], 200);
    }
}
