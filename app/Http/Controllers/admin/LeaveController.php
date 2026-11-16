<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;

use App\Models\Leave;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{

    private LeaveService $leaveRequestService;

    public function __construct(LeaveService $leaveRequestService)
    {
        $this->leaveRequestService = $leaveRequestService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.pages.leaves.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        if (empty($request->leave_request_id)) {
            $this->validate($request, [
                'leave_reason' => 'string|required',
                'leave_type' => 'required|in:PAID,UNPAID',
                'leave_start_date' => 'required|date',
                'leave_end_date' => 'required|date|after_or_equal:leave_start_date',
                'leave_status' => 'required|in:Pending,Approved,Rejected,Cancelled'
            ]);
        } else {
            $this->validate($request, [
                'leave_reason' => 'string|required',
                'leave_type' => 'required|in:PAID,UNPAID',
                'leave_start_date' => 'required|date',
                'leave_end_date' => 'required|date|after_or_equal:leave_start_date',
                'leave_status' => 'required|in:Pending,Approved,Rejected,Cancelled'
            ]);
        }


        return $this->leaveRequestService->storeLeaveRequest($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        return $this->leaveRequestService->listLeaveRequest($request);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Leave $leave)
    {
        //
    }

    public function updateStatus(Request $request)
    {
        $this->validate($request, [
            'leave_status' => 'string|required',

        ]);


        return $this->leaveRequestService->updateStatus($request);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Leave $leave)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Leave $leave)
    {
        //
    }
}
