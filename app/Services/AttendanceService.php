<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    public function storeAttendance($data = null)
    {

        try {
            DB::beginTransaction();


           if (strtolower($data->status) == 'absent') {
                $checkIn = null;
                $checkOut = null;
            } else {
                $checkIn = $data->check_in;
                $checkOut = $data->check_out;
            }

            $user = Employee::where('id', $data->employee_id)->first();
            $userId = $user->user_id;
            $Attendancestore = Attendance::updateOrCreate([
                'id' => $data->id
            ], [
                'employee_id' => $data->employee_id,
                'user_id' => $userId,

                'date' => $data->date,


                'check_in' => $checkIn,
                'check_out' => $checkOut,

                'status' => $data->status,

            ]);

            if ($Attendancestore) {
                $errormessage = $data->id > 0 ? 'Attendance Updated Successfully.' : 'Attendance  Created Successfully.';
                $errorcode = 0;
            } else {
                $errormessage = 'Database error...';
                $errorcode = 15;
            }

            DB::commit();



            return successResponse($Attendancestore, $errormessage, $errorcode, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function employeeListForAttendance($data = null, $platform = 'web')
    {


        $employee = new EmployeeService();
        $employeeDetails = $employee->getAllEmployee($data, 'attendance');

        $result = Attendance::where('date', $data->date)->orderBy('employee_id', 'DESC')->get();


        $employeelist = [];

        foreach ($employeeDetails as $employee) {

            $employeelist[] = [
                'id' => $employee['id'],
                'attendance_id' => null,
                'employee_name' => $employee['name'],
                'employee_code' => $employee['employee_code'],
                'check_in' => '--:--',
                'check_out' => '--:--',

                'attendance_date' => $data->date,
                'attendance_status' => null,
                'attendance_updated' => null,
            ];


            foreach ($result as $attendanceEntry) {


                if ($employee['id'] == $attendanceEntry->employee_id) {
                    $employeelist[count($employeelist) - 1] = [
                        'id' => $employee['id'],
                        'attendance_id' => $attendanceEntry->id,
                        'employee_name' => $employee['name'],
                        'employee_code' => $employee['employee_code'],
                        'check_in' => $attendanceEntry->check_in,
                        'check_out' => $attendanceEntry->check_out,

                        'attendance_date' => $attendanceEntry->date,
                        'attendance_status' => $attendanceEntry->status,
                        'attendance_updated' => $attendanceEntry->updated_at,
                    ];
                }
            }
        }

        if (!empty($data->employee_id)) {
            $employeelist = collect($employeelist)->where('employee_id', $data->employee_id)->toArray();
        }
        if (!empty($data->attendance_status)) {
            $employeelist = collect($employeelist)->where('attendance_status', $data->attendance_status)->toArray();
        }


        $result = $employeelist;

        $response = [
            'employees' => $result,
        ];

        return successResponse($response, 'Attendance retrieved successfully.', 0, 200);
    }
    // public function updateAttendance($data = null, $platform = 'web')
    // {

    //     try {
    //         DB::beginTransaction();

    //         $category = ProductCategory::find($data->id);
    //         if (!$category) {
    //             return errorResponse(null, 'Category not found', 404, 404);
    //         }

    //         $category->update([
    //             'category_name'      => $data->category_name,
    //             'ar_category_name'   => $data->ar_category_name,
    //             'status'            => $data->status,
    //             'updated_by' => $data->user_id,
    //         ]);

    //         DB::commit();
    //         return successResponse(null, 'Category updated successfully.', 0, 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }
    // public function listAllAttendance($data, $platform = 'web')
    // {
    //     try {
    //         $query = ProductCategory::query();

    //         if ($data->search) {
    //             $query->where(function ($q) use ($data) {
    //                 $q->where('category_name', 'LIKE', "%{$data->search}%")
    //                     ->orWhere('category_code', 'LIKE', "%{$data->search}%");
    //             });
    //         }

    //         if ($platform == 'web') {
    //             $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
    //             $response = [
    //                 'category'  => $result->items(),
    //                 'pagination' => generatePaginationData($result),
    //             ];
    //             return successResponse($response, 'Category retrieved successfully.', 0, 200);
    //         } else {
    //             $result = $query->where('status', 1)->orderBy('created_at', 'DESC')->get();
    //             return successResponse($result, 'Category retrieved successfully.', 0, 200);
    //         }
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }
    // public function listAttendance($data, $platform = 'web')
    // {
    //     try {
    //         $query = ProductCategory::query();

    //         if ($data->search) {
    //             $query->where(function ($q) use ($data) {
    //                 $q->where('category_name', 'LIKE', "%{$data->search}%")
    //                     ->orWhere('category_code', 'LIKE', "%{$data->search}%");
    //             });
    //         }

    //         if ($platform == 'web') {
    //             $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
    //             $response = [
    //                 'category'  => $result->items(),
    //                 'pagination' => generatePaginationData($result),
    //             ];
    //             return successResponse($response, 'Category retrieved successfully.', 0, 200);
    //         } else {
    //             $result = $query->where('status', 1)->orderBy('created_at', 'DESC')->get();
    //             return successResponse($result, 'Category retrieved successfully.', 0, 200);
    //         }
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }
    // public function deleteAttendance($data)
    // {
    //     try {
    //         DB::beginTransaction();
    //         $category = ProductCategory::findOrFail($data->id);
    //         if ($category) {
    //             $category->deleted_by = $data->user_id;
    //             $category->save();
    //             $category->delete();
    //             DB::commit();
    //             return successResponse(null, 'Category deleted successfully.', 0, 200);
    //         } else {
    //             return errorResponse(null, 'Category not found', 404, 404);
    //         }
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }
}
