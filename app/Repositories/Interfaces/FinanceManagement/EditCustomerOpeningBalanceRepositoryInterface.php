<?php

namespace App\Repositories\interfaces\FinanceManagement;


interface EditCustomerOpeningBalanceRepositoryInterface{
    public function get_all($request);

    public function get_payment_by_id($request);
}