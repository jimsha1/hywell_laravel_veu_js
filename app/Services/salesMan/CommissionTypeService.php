<?php

namespace App\Services\salesMan;

use App\Models\CommisionTypeMaster;
use Exception;

class CommissionTypeService
{
    public function getAllCommissionTypes($request)
    {
        try {
            $commisions = CommisionTypeMaster::where('status',1)->get();
            return successResponse($commisions, 'All Commission Types', 0);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 102);
        }
    }


}