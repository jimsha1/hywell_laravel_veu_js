<?php

use App\Models\AutoIncrementMaster;

function apiResponse($errorcode, $errormessage, $data = null, $http_status_code = 200)
{
    $response = [
        'errorcode' => $errorcode,
        'errormessage' => $errormessage,
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    return response()->json($response, $http_status_code);
}

function generatePaginationData($result)
{
    return [
        'total' => $result->total(),
        'per_page' => $result->perPage(),
        'current_page' => $result->currentPage(),
        'last_page' => $result->lastPage(),
        'from' => $result->firstItem(),
        'to' => $result->lastItem()
    ];
}

function successResponse($data, $message, $errorcode, $code = 200)
{
    return response()->json([
        'status' => 'Success',
        'code' => $errorcode,
        'message' => $message,
        'data' => $data
    ], $code);
}

function errorResponse($data, $message, $errorcode, $code = 200)
{
    return response()->json([
        'status' => 'Error',
        'code' => $errorcode,
        'message' => $message,
        'errors' => $data
    ], $code);
}



function applyDateFilter($query, $startDate, $endDate, $column = 'created_at')
{
    if (!empty($startDate) && !empty($endDate)) {
        $query->whereBetween($column, [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    } elseif (!empty($startDate)) {
        $query->where($column, '>=', $startDate . ' 00:00:00');
    } elseif (!empty($endDate)) {
        $query->where($column, '<=', $endDate . ' 23:59:59');
    }

    return $query;
}

function applySearchFilter($query, $search, $columns = ['order_code'])
{
    if (!empty($search)) {
        $query->where(function ($q) use ($search, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', '%' . $search . '%');
            }
        });
    }

    return $query;
}

function applySearchFilterWithRelation($query, $search, $columns = ['order_code'])
{
    if (!empty($search)) {
        $query->where(function ($q) use ($search, $columns) {
            foreach ($columns as $column) {
                if (strpos($column, '.') !== false) {
                    // Handle relation search like: customer.name
                    [$relation, $field] = explode('.', $column, 2);
                    $q->orWhereHas($relation, function ($relQuery) use ($field, $search) {
                        $relQuery->where($field, 'like', '%' . $search . '%');
                    });
                } else {
                    $q->orWhere($column, 'like', '%' . $search . '%');
                }
            }
        });
    }

    return $query;
}



function generateAutoCode($modelInstance, $fieldName = 'order_code', $autoCodeKey = 'AI_ORDER_NO')
{
    try {
        $auto = AutoIncrementMaster::where('auto_code', $autoCodeKey)->first();

        if (!$auto) {
            return errorResponse(null, "", 100);
        }

        $currentNo = $auto->auto_increment_general_value;
        $auto->auto_increment_general_value = $currentNo + 1;
        $auto->save();

        $generatedCode = $auto->auto_prefix . $currentNo;

        $modelInstance->$fieldName = $generatedCode;
        $modelInstance->save();

        return $generatedCode;
    } catch (Exception $e) {
        return errorResponse(null, "", 100);
    }
}



function uploadImage($image, $folder)
{
    if ($image instanceof \Illuminate\Http\UploadedFile) {
        $imagePath = $image->store('uploads/' . $folder, 'public');
        return $imagePath;
    }
    return null;
}


