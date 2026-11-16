<?php

namespace App\Repositories\Interfaces;

use Illuminate\Http\Request;

interface ProductWiseSalesRepositoryInterface
{
    public function getProductWiseSalesReport(Request $request);
}

