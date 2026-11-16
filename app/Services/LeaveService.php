<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use DateTime;
use Illuminate\Support\Facades\DB;


class LeaveService
{
    public function storeLeaveRequest($data = null)
    {


        $datetime1 = new DateTime($data->leave_start_date);

        $datetime2 = new DateTime($data->leave_end_date);
        $interval = $datetime1->diff($datetime2);
        $days = $interval->format('%a');

        DB::beginTransaction();


        $leaveStore = Leave::updateOrCreate([
            'id' => $data->leave_request_id
        ], [
            'leave_start' => $data->leave_start_date,
            'leave_end' => $data->leave_end_date,
            'reason' => $data->leave_reason,
            'employee_id' => $data->employee_id,
            'user_id' => $data->user_id,

            'leave_type' => $data->leave_type,
            'duration' => $days,



        ]);


        if ($leaveStore) {
            $errormessage = $data->leave_request_id > 0 ? 'Leave Request Updated Successfully.' : 'Leave Request  Created Successfully.';
            $errorcode = 0;
        } else {
            $errormessage = 'Database error...';
            $errorcode = 15;
        }

        DB::commit();


        // if ($platform == 'web') {
        //     return apiResponse($errorcode, $errormessage, $data);
        // } else {
        //     // return apiResponse($errorcode, $errormessage);
        // }
        // } catch (\Exception $e) {
        //     Log::debug($e);
        //     DB::rollBack();
        //     return apiResponse(25,  $e);
        // }
        return successResponse($leaveStore, $errormessage, $errorcode, 200);
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
    public function listLeaveRequest($data = null, $platform = 'web')
    {

        // $countLeaves = Attendance::where('employee_id', $emp_id)->where('status', 'Absent')->count();

      
        $result = Leave::with('employee');

        $startDate = $data->leave_start_date;
        $endDate = $data->leave_end_date;

        if (!empty($data->employee_name)) {
            $result->where('employee_id', $data->employee_name);
        }
        if (!empty($startDate) && !empty($endDate)) {
            $result->whereDate('leave_start', '<=', $endDate)
                ->whereDate('leave_end', '>=', $startDate);
        } elseif (!empty($startDate)) {
            $result->whereDate('leave_start', '<=', $startDate)
                ->whereDate('leave_end', '>=', $startDate);
        } elseif (!empty($endDate)) {
            $result->whereDate('leave_start', '<=', $endDate)
                ->whereDate('leave_end', '>=', $endDate);
        }

        if (!empty($data->leave_status)) {

            $result->where('status', $data->leave_status);
        }


        if (!empty($data->leave_type)) {

            $result->where('leave_type', $data->leave_type);
        }

        $result = $result->orderBy('id', $data->sort_order ?? 'DESC')->paginate(config('settings.pagination.perpage.admin'));

        $response['pagination'] = generatePaginationData($result);
        $response['data'] = $result;
        return apiResponse(0, 'success', $response);
    }

    public function updateStatus($data = null)
    {

    
        DB::beginTransaction();
        $leaveStore = Leave::where('id', $data->leave_request_id)->update(
            [
                'status' => $data->leave_status,
                'comments' => $data->leave_comment,

            ]
        );



        if ($leaveStore) {
            $errormessage = $data->leave_request_id > 0 ? 'Leave Request Updated Successfully.' : 'Leave Request  Created Successfully.';
            $errorcode = 0;
        } else {
            $errormessage = 'Database error...';
            $errorcode = 15;
        }

        DB::commit();

        return successResponse($leaveStore, $errormessage, $errorcode, 200);
        // if ($platform == 'web') {
        //     return apiResponse($errorcode, $errormessage, $data);
        // } else {
        //     // return apiResponse($errorcode, $errormessage);
        // }
        // } catch (\Exception $e) {
        //     Log::debug($e);
        //     DB::rollBack();
        //     return apiResponse(25,  $e);
        // }
    }
}
