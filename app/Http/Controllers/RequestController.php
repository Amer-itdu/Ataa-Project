<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrphanRequest;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\StoreUniversityRequest;
use App\Http\Requests\AcceptRequestRequest;
use App\Models\Beneficiary;
use App\Models\Governorate;
use App\Models\Orphan;
use App\Models\Patient;
use App\Models\Region;
use App\Models\RequestModel;
use App\Models\SchoolStudent;
use App\Models\UniversityStudent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RequestController extends Controller
{
    private function ensureCanCreateRequest($user)
    {
        if (! $user || ($user->role !== 'admin' && $user->user_category !== 'beneficiary')) {
            return response()->json([
                'message' => 'Only beneficiaries or admins can create requests.',
            ], 403);
        }

        return null;
    }/*
    |--------------------------------------------------------------------------
    | PATIENT REQUEST
    |--------------------------------------------------------------------------
    */
    public function storePatientRequest(StorePatientRequest $request)
    {
        $user = Auth::user();
        $authorizationResponse = $this->ensureCanCreateRequest($user);

        if ($authorizationResponse) {
            return $authorizationResponse;
        }

        $isAdmin = $user->role === 'admin';

        // 1) Beneficiary data
        if ($isAdmin) {
            $beneficiaryData = [
                'full_name'      => $request->full_name,
                'governorate_id' => $request->governorate_id,
                'region_id'      => $request->region_id,
                'national_id'    => $request->national_id,
                'email'          => $request->email,
                'phone'          => $request->phone,
            ];

            $requiredAmount = $request->required_amount;
            $status = 'accepted';
        } else {

            if ($request->is_self === "true") {
                $beneficiaryData = [
                    'full_name'      => $request->full_name,
                    'governorate_id' => $request->governorate_id,
                    'region_id'      => $request->region_id,
                    'national_id'    => $request->national_id,
                    'email'          => $user->email,
                    'phone'          => $user->phone,
                ];
            } else {
                $beneficiaryData = [
                    'full_name'      => $request->full_name,
                    'governorate_id' => $request->governorate_id,
                    'region_id'      => $request->region_id,
                    'national_id'    => $request->national_id,
                    'email'          => $request->email,
                    'phone'          => $request->phone,
                ];
            }

            $requiredAmount = $request->required_amount ?? 0;
            $status = 'pending';
        }

        // 2) Beneficiary — updateOrCreate باستخدام national_id كمفتاح فريد
        $beneficiary = $this->findOrCreateBeneficiary($beneficiaryData);

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

        $nationalIdDocPath = $request->file('national_id_document')
            ->store('national_ids', 'public');

        // 6) Patient record
        $patient = Patient::create([
            'request_id'     => $requestModel->id,
            'medical_report' => $medicalReportPath,
            'national_id_document' => $nationalIdDocPath,

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
        $authorizationResponse = $this->ensureCanCreateRequest($user);

        if ($authorizationResponse) {
            return $authorizationResponse;
        }

        $isAdmin = $user->role === 'admin';

        // 1) Beneficiary
        if ($isAdmin) {
            $beneficiaryData = [
                'full_name'      => $request->full_name,
                'governorate_id' => $request->governorate_id,
                'region_id'      => $request->region_id,
                'national_id'    => $request->national_id,
                'email'          => $request->email,
                'phone'          => $request->phone,
            ];

            $requiredAmount = $request->required_amount;
            $status = 'accepted';
        } else {

            $beneficiaryData = [
                'full_name'      => $request->full_name,
                'governorate_id' => $request->governorate_id,
                'region_id'      => $request->region_id,
                'national_id'    => $request->national_id,
                'email'          => $user->email,
                'phone'          => $user->phone,
            ];

            $requiredAmount = 0;
            $status = 'pending';
        }

        // 2) Beneficiary
        $beneficiary = $this->findOrCreateBeneficiary($beneficiaryData);

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

    /*
|--------------------------------------------------------------------------
| UNIVERSITY REQUEST
|--------------------------------------------------------------------------
*/
    public function storeUniversityRequest(StoreUniversityRequest $request)
    {
        $user = Auth::user();
        $authorizationResponse = $this->ensureCanCreateRequest($user);

        if ($authorizationResponse) {
            return $authorizationResponse;
        }

        $isAdmin = $user->role === 'admin';

        // 1) Beneficiary
        if ($isAdmin) {
            $beneficiaryData = [
                'full_name'      => $request->full_name,
                'governorate_id' => $request->governorate_id,
                'region_id'      => $request->region_id,
                'national_id'    => $request->national_id,
                'email'          => $request->email,
                'phone'          => $request->phone,
            ];

            $requiredAmount = $request->required_amount;
            $status = 'accepted';
        } else {

            $beneficiaryData = [
                'full_name'      => $request->full_name,
                'governorate_id' => $request->governorate_id,
                'region_id'      => $request->region_id,
                'national_id'    => $request->national_id,
                'email'          => $user->email,
                'phone'          => $user->phone,
            ];

            $requiredAmount = 0;
            $status = 'pending';
        }

        // 2) Beneficiary
        $beneficiary = $this->findOrCreateBeneficiary($beneficiaryData);

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

    /*
|--------------------------------------------------------------------------
| ORPHAN REQUEST
|--------------------------------------------------------------------------
*/
    public function storeOrphanRequest(StoreOrphanRequest $request)
    {
        $user = Auth::user();
        $authorizationResponse = $this->ensureCanCreateRequest($user);

        if ($authorizationResponse) {
            return $authorizationResponse;
        }

        $isAdmin = $user->role === 'admin';

        // 1) بيانات المستفيد
        if ($isAdmin) {

            $beneficiaryData = [
                'full_name'      => $request->full_name,
                'governorate_id' => $request->governorate_id,
                'region_id'      => $request->region_id,
                'national_id'    => $request->national_id,
                'email'          => null, // ممنوع
                'phone'          => $request->phone,
            ];

            $requiredAmount = $request->required_amount;
            $status = 'accepted';
        } else {

            $beneficiaryData = [
                'full_name'      => $request->full_name,
                'governorate_id' => $request->governorate_id,
                'region_id'      => $request->region_id,
                'national_id'    => $request->national_id,
                'email'          => null, // ممنوع
                'phone'          => $request->phone,
            ];

            $requiredAmount = 0;
            $status = 'pending';
        }

        // 2) إنشاء أو تحديث المستفيد
        $beneficiary = $this->findOrCreateBeneficiary($beneficiaryData);

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

    /*
|--------------------------------------------------------------------------
| HELPER: Find or create Beneficiary by national_id
|--------------------------------------------------------------------------
*/
    private function findOrCreateBeneficiary(array $data): Beneficiary
    {
        // لو national_id موجود وغير فاضي → نبحث/نحدّث بناءً عليه
        if (!empty($data['national_id'])) {
            return Beneficiary::updateOrCreate(
                ['national_id' => $data['national_id']],
                $data
            );
        }

        // لو national_id فاضي/null → ننشئ سجل جديد دايماً
        // (تجنباً لتصادم كل المستفيدين اللي بدون national_id ببعض)
        return Beneficiary::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | PENDING REQUESTS (GENERAL)
    |--------------------------------------------------------------------------
    */
    public function getPendingRequests()
    {
        $requests = RequestModel::with([
            'beneficiary.governorate',
            'beneficiary.region',
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
        $requests = RequestModel::with(['beneficiary.governorate', 'beneficiary.region', 'patient'])
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
        $requests = RequestModel::with(['beneficiary.governorate', 'beneficiary.region', 'orphan'])
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
        $requests = RequestModel::with(['beneficiary.governorate', 'beneficiary.region', 'schoolStudent'])
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
        $requests = RequestModel::with(['beneficiary.governorate', 'beneficiary.region', 'universityStudent'])
            ->where('status', 'pending')
            ->where('request_type', 'university')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $requests
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | OPEN & ACCEPTED REQUESTS
    |--------------------------------------------------------------------------
    */
    public function getOpenAcceptedRequests()
    {
        $requests = RequestModel::with([
            'beneficiary.governorate',
            'beneficiary.region',
            'patient.donations',
            'orphan.donations',
            'schoolStudent.donations',
            'universityStudent.donations'
        ])
            ->where('status', 'accepted')
            ->where('status_request', 'open')
            ->get()
            ->map(function ($req) {

                $target = match ($req->request_type) {
                    'patient'    => $req->patient,
                    'orphan'     => $req->orphan,
                    'school'     => $req->schoolStudent,
                    'university' => $req->universityStudent,
                };

                $donated = $target->donations()->sum('amount');

                $required = $req->required_amount;

                $progress = $required > 0
                    ? round(($donated / $required) * 100, 2)
                    : 0;

                $remaining = max($required - $donated, 0);

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
        $requests = RequestModel::with(['patient.donations', 'beneficiary.governorate', 'beneficiary.region'])
            ->where('status', 'accepted')
            ->where('status_request', 'open')
            ->where('request_type', 'patient')
            ->get()
            ->map(function ($req) {

                $target = $req->patient;

                $donated = $target->donations()->sum('amount');
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
        $requests = RequestModel::with(['orphan.donations', 'beneficiary.governorate', 'beneficiary.region'])
            ->where('status', 'accepted')
            ->where('status_request', 'open')
            ->where('request_type', 'orphan')
            ->get()
            ->map(function ($req) {

                $target = $req->orphan;

                $donated = $target->donations()->sum('amount');
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
        $requests = RequestModel::with(['schoolStudent.donations', 'beneficiary.governorate', 'beneficiary.region'])
            ->where('status', 'accepted')
            ->where('status_request', 'open')
            ->where('request_type', 'school')
            ->get()
            ->map(function ($req) {

                $target = $req->schoolStudent;

                $donated = $target->donations()->sum('amount');
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
        $requests = RequestModel::with(['universityStudent.donations', 'beneficiary.governorate', 'beneficiary.region'])
            ->where('status', 'accepted')
            ->where('status_request', 'open')
            ->where('request_type', 'university')
            ->get()
            ->map(function ($req) {

                $target = $req->universityStudent;

                $donated = $target->donations()->sum('amount');
                $required = $req->required_amount;

                $req->donated_amount = $donated;
                $req->remaining_amount = max($required - $donated, 0);
                $req->progress_percentage = $required > 0 ? round(($donated / $required) * 100, 2) : 0;

                return $req;
            });

        return response()->json($requests);
    }
    public function filterRequests(\Illuminate\Http\Request $httpRequest)
    {
        $httpRequest->validate([
            'request_type'    => 'nullable|in:patient,orphan,school,university',
            'status'          => 'nullable|in:pending,accepted,rejected',
            'status_request'  => 'nullable|in:open,closed',
            'governorate_id'  => 'nullable|integer|exists:governorates,id',
            'region_id'       => 'nullable|integer|exists:regions,id',
        ]);

        $query = RequestModel::with([
            'beneficiary.governorate',
            'beneficiary.region',
            'patient.donations',
            'orphan.donations',
            'schoolStudent.donations',
            'universityStudent.donations'
        ]);

        if ($httpRequest->filled('status')) {
            $query->where('status', $httpRequest->status);
        }

        if ($httpRequest->filled('status_request')) {
            $query->where('status_request', $httpRequest->status_request);
        }

        if ($httpRequest->filled('request_type')) {
            $query->where('request_type', $httpRequest->request_type);
        }

        if ($httpRequest->filled('governorate_id')) {
            $query->whereHas('beneficiary', function ($q) use ($httpRequest) {
                $q->where('governorate_id', $httpRequest->governorate_id);
            });
        }

        if ($httpRequest->filled('region_id')) {
            $query->whereHas('beneficiary', function ($q) use ($httpRequest) {
                $q->where('region_id', $httpRequest->region_id);
            });
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($req) {

                $target = match ($req->request_type) {
                    'patient'    => $req->patient,
                    'orphan'     => $req->orphan,
                    'school'     => $req->schoolStudent,
                    'university' => $req->universityStudent,
                    default      => null,
                };

                if (!$target) {
                    $req->donated_amount = 0;
                    $req->remaining_amount = $req->required_amount;
                    $req->progress_percentage = 0;
                    return $req;
                }

                $donated  = $target->donations()->sum('amount');
                $required = $req->required_amount;

                $req->donated_amount = $donated;
                $req->remaining_amount = max($required - $donated, 0);
                $req->progress_percentage = $required > 0 ? round(($donated / $required) * 100, 2) : 0;

                return $req;
            });

        return response()->json([
            'success' => true,
            'count'   => $requests->count(),
            'data'    => $requests
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CLOSE REQUEST
    |--------------------------------------------------------------------------
    */
    public function closeRequest($id)
    {
        $req = RequestModel::findOrFail($id);

        if ($req->status_request === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Request already closed.'
            ], 400);
        }

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
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can accept requests.'
            ], 403);
        }

        $requestModel = RequestModel::findOrFail($id);

        if ($requestModel->status === 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'This request is already accepted.'
            ], 400);
        }

        $updateData = [
            'status' => 'accepted',
        ];

        if ($request->has('title') && !empty($request->title)) {
            $updateData['title'] = $request->title;
        }

        if ($request->has('description') && !empty($request->description)) {
            $updateData['description'] = $request->description;
        }

        if ($request->has('required_amount') && $request->required_amount !== null) {
            $updateData['required_amount'] = $request->required_amount;
        }

        if ($request->hasFile('personal_picture')) {
            if ($requestModel->personal_picture) {
                Storage::disk('public')->delete($requestModel->personal_picture);
            }

            $personalPicturePath = $request->file('personal_picture')
                ->store('personal_pictures', 'public');
            $updateData['personal_picture'] = $personalPicturePath;
        }

        $requestModel->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Request accepted and updated successfully.',
            'request' => $requestModel
        ], 200);
    }
}
