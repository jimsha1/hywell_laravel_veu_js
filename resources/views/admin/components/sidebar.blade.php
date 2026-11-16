<div class="sidebar" id="sidebar">
    <div class="modern-profile p-3 pb-0">

        <div class="sidebar-nav mb-3">
            <ul class="nav nav-tabs nav-tabs-solid nav-tabs-rounded nav-justified bg-transparent" role="tablist">
                <li class="nav-item"><a class="nav-link active border-0" href="#">Menu</a></li>
                <li class="nav-item"><a class="nav-link border-0" href="chat.html">Chats</a></li>
                <li class="nav-item"><a class="nav-link border-0" href="email.html">Inbox</a></li>
            </ul>
        </div>
    </div>
    <div class="sidebar-header p-3 pb-0 pt-2">

        <div class="d-flex align-items-center justify-content-between menu-item mb-3">
            <div class="me-3">
                <a href="calendar.html" class="btn btn-icon border btn-menubar">
                    <i class="ti ti-layout-grid-remove"></i>
                </a>
            </div>
            <div class="me-3">
                <a href="chat.html" class="btn btn-icon border btn-menubar position-relative">
                    <i class="ti ti-brand-hipchat"></i>
                </a>
            </div>
            <div class="me-3 notification-item">
                <a href="activities.html" class="btn btn-icon border btn-menubar position-relative me-1">
                    <i class="ti ti-bell"></i>
                    <span class="notification-status-dot"></span>
                </a>
            </div>
            <div class="me-0">
                <a href="email.html" class="btn btn-icon border btn-menubar">
                    <i class="ti ti-message"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li class="clinicdropdown">
                    <a href="{{ route('admin-dashboard') }}">
                        <img id="user_image" class="img-fluid" alt="Profile">
                        <div class="user-names">
                            <h5 id="user_name">Adrian Davies</h5>
                            <h6 id="user_type">Tech Lead</h6>
                        </div>
                    </a>
                </li>
            </ul>

            <ul>
                <li>
                    <h6 class="submenu-hdr">Main Menu</h6>
                    <ul>
                        <li>
                            <a href="{{ route('admin-dashboard') }}"
                                class="subdrop {{ Request::routeIs('admin-dashboard') ? 'active' : '' }}">
                                <i class="ti ti-layout-2"></i><span>Dashboard</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <ul>
                <li>
                    <ul>
                        @canany(['customers'])
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="{{ Request::routeIs('customer.*', 'qr_code.*', 'credit-payment.index', 'edit-order-payment.index', 'edit-opening-balance-payment.index') ? 'active' : '' }}">
                                    <i class="ti ti-users mr-4"></i>
                                    <span>Customer Management</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul
                                    style="{{ Request::routeIs('customer.*', 'qr_code.*', 'credit-payment.index', 'edit-order-payment.index', 'edit-opening-balance-payment.index') ? 'display: block;' : '' }}">
                                    @can('customers')
                                        <li>
                                            <a href="{{ route('customer.index') }}"
                                                class="subdrop {{ Request::routeIs('customer.index') ? 'active' : '' }}">
                                                <span>Customers</span>
                                            </a>
                                        </li>
                                    @endcan
                                    @can('customers')
                                        <li>
                                            <a href="{{ route('credit-payment.index') }}"
                                                class="subdrop {{ Request::routeIs('credit-payment.index') ? 'active' : '' }}">
                                                <span>Customers Credit Payment</span>
                                            </a>
                                        </li>
                                    @endcan
                                    @can('customers')
                                        <li>
                                            <a href="{{ route('edit-order-payment.index') }}"
                                                class="subdrop {{ Request::routeIs('edit-order-payment.index') ? 'active' : '' }}">
                                                <span>Order Payment</span>
                                            </a>
                                        </li>
                                    @endcan
                                    @can('customers')
                                        <li>
                                            <a href="{{ route('edit-opening-balance-payment.index') }}"
                                                class="subdrop {{ Request::routeIs('edit-opening-balance-payment.index') ? 'active' : '' }}">
                                                <span>Opening Balance Payment</span>
                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                            </li>
                        @endcanany
                    </ul>
                </li>
            </ul>

            <ul>
                <li>
                    <ul>
                        @canany(['suppliers'])
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="{{ Request::routeIs('suppliers.*') ? 'active' : '' }}">
                                    <i class="ti ti-shopping-cart mr-4"></i>
                                    <span>Supplier Management</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul style="{{ Request::routeIs('suppliers.*') ? 'display: block;' : '' }}">
                                    @can('suppliers')
                                        <li>
                                            <a href="{{ route('suppliers.index') }}"
                                                class="{{ Request::routeIs('suppliers.index') ? 'active' : '' }}">
                                                <span>Suppliers</span>
                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                            </li>
                        @endcanany
                    </ul>
                </li>
            </ul>
            <ul>
                <li>
                    <ul>
                        @canany(['purchase'])
                            <li class="submenu">
                                <a href="javascript:void(0);" class="{{ Request::routeIs('purchase.*', 'purchase-return.*') ? 'active' : '' }}">
                                    <i class="ti ti-shopping-cart mr-4"></i>
                                    <span>Purchase Management</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul style="{{ Request::routeIs('purchase.*', 'purchase-return.*') ? 'display: block;' : '' }}">
                                    @can('purchase')
                                        <li>
                                            <a href="{{ route('purchase.index') }}"
                                                class="{{ Request::routeIs('purchase.index') ? 'active' : '' }}">
                                                <span>Purchases</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('purchase-return.index') }}"
                                                class="{{ Request::routeIs('purchase-return.index') ? 'active' : '' }}">
                                                <span>Purchase Return</span>
                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                            </li>
                        @endcanany
                    </ul>
                </li>
            </ul>

            {{-- <ul>

                <li>
                    <ul>

                        <li>
                            <a href="{{ route('outside-credit-payment.index') }}"
                                class="subdrop {{ Request::routeIs('outside-credit-payment.index') ? 'active' : '' }}">
                              
                                <i class="ti ti-users mr-4"></i><span>Outside Salesman Credit Payment</span>
                            </a>
                        </li>

                    </ul>
                </li>

            </ul> --}}
            <ul>
                <li>
                    <ul>

                        <li class="submenu">
                            <a href="javascript:void(0);"
                                class="{{ Request::routeIs('outside-credit-payment.index', 'outside-credit-payment.index') ? 'active' : '' }}">
                                <i class="ti ti-report mr-4"></i>
                                <span>Outside Salesman</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul
                                style="{{ Request::routeIs('open-close.index', 'purchase-report.index', 'order-report.index', 'sales-report.index', 'stock-report.index', 'commission-report.index', 'customer-credit-report.index', 'employee-payment-collection-report.index', 'daily-sales-report.index', 'supplier-payment-report.index', 'product-wise-sales-report.index', 'customer-payment-report.index') ? 'display: block;' : '' }}">
                                <li>

                                    <a href="{{ route('outside-credit-payment.index') }}"
                                        class="subdrop {{ Request::routeIs('outside-credit-payment.index') ? 'active' : '' }}">
                                        <span>Credit Payment</span>
                                    </a>



                                </li>
                               
                                <li>
                                    <a href="{{ route('outside-customer-credit-report.index') }}"
                                        class="subdrop {{ Request::routeIs('outside-customer-credit-report.index') ? 'active' : '' }}">
                                        <span>Customer Credit Report</span>
                                    </a>
                                </li>

                                
                            </ul>
                        </li>

                    </ul>
                </li>
            </ul>
            {{-- <ul>
                <li>
                    <a href="{{ route('outside-credit-payment.index') }}"
                        class="subdrop{{ Request::routeIs('outside-credit-payment.index') ? 'active' : '' }}">
                        <i ti ti-layout-2"></i>
                        <span>Outside Salesman Credit Payment</span>
                    </a>
                </li>
            </ul> --}}
            <ul>
                <li>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);"
                                class="{{ Request::routeIs('admin-order.*', 'admin-order-list', 'sales-return.index') ? 'active' : '' }}">
                                <i class="ti ti-shopping-cart mr-4"></i>
                                <span>Order Management</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul
                                style="{{ Request::routeIs('admin-order.*', 'admin-order-list', 'sales-return.index') ? 'display: block;' : '' }}">

                                <li>
                                    <a href="{{ route('admin-order.index') }}"
                                        class="{{ Request::routeIs('admin-order.index') ? 'active' : '' }}">
                                        <span>Create Order</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin-order-list') }}"
                                        class="{{ Request::routeIs('admin-order-list') ? 'active' : '' }}">
                                        <span>Order List</span>
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('sales-return.index') }}"
                                        class="{{ Request::routeIs('sales-return.index') ? 'active' : '' }}">
                                        <span>Sales Return</span>
                                    </a>
                                </li>

                            </ul>
                        </li>
                    </ul>
                </li>
            </ul>

            <ul>
                @canany(['approve_load'])
                    <li>
                        <ul>
                            @can('approve_load')
                                <li>
                                    <a href="{{ route('approved-load.index') }}"
                                        class="subdrop {{ Request::routeIs('approved-load.index') ? 'active' : '' }}">
                                        {{-- <i class="ti ti-layout-2"></i><span>Approve Load</span> --}}
                                        <i class="ti ti-layout-2"></i><span>Vehicle Load</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan
            </ul>

            {{-- <ul>
                @canany(['return_load'])
                    <li>
                        <ul>
                            @can('return_load')
                                <li>
                                    <a href="{{ route('return-load.index') }}"
                                        class="subdrop {{ Request::routeIs('return-load.index') ? 'active' : '' }}">
                                        <i class="ti ti-layout-2"></i><span>Return Load</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan
            </ul> --}}

            <ul>
                @canany(['open_stock'])
                    <li>
                        <ul>
                            @can('open_stock')
                                <li>
                                    <a href="{{ route('opening-stock.index') }}"
                                        class="subdrop {{ Request::routeIs('opening-stock.index') ? 'active' : '' }}">
                                        <i class="ti ti-layout-2"></i><span>Opening Stock</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan
            </ul>

            <ul>
                <li>
                    <ul>
                        @canany(['create_role', 'create_user'])
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="{{ Request::routeIs('manage.users.*', 'manage.roles.*', 'employee.index') ? 'active' : '' }}">
                                    <i class="ti ti-users mr-4"></i>
                                    <span>User Management</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul
                                    style="{{ Request::routeIs('manage.users.*', 'manage.roles.*', 'employee.index') ? 'display: block;' : '' }}">
                                    <li>
                                        @can('create_user')
                                            <a href="{{ route('manage.users.index') }}"
                                                class="subdrop {{ Request::routeIs('manage.users.index') ? 'active' : '' }}">
                                                <span>Manage Users</span>
                                            </a>
                                        @endcan
                                    </li>
                                    <li>
                                        @can('create_role')
                                            <a href="{{ route('manage.roles.index') }}"
                                                class="subdrop {{ Request::routeIs('manage.roles.index') ? 'active' : '' }}">
                                                <span>Roles & Permissions</span>
                                            </a>
                                        @endcan
                                    </li>
                                    <li>
                                        <a href="{{ route('employee.index') }}"
                                            class="subdrop {{ Request::routeIs('employee.index') ? 'active' : '' }}">
                                            <span>Employees</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcanany
                    </ul>
                </li>
            </ul>

            <ul>
                <li>
                    <ul>
                        @canany(['product_category', 'products', 'vehicles', 'special_price'])
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="{{ Request::routeIs('product-category.*', 'products.*', 'dispenser.*', 'vehicle.*', 'special-price.*', 'global-tax.index', 'commission-type.index') ? 'active' : '' }}">
                                    <i class="ti ti-package mr-4"></i>
                                    <span>MasterData Management</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul
                                    style="{{ Request::routeIs('product-category.*', 'products.*', 'vehicle.*', 'special-price.*', 'global-tax.index', 'commission-type.index') ? 'display: block;' : '' }}">

                                    <li>
                                        @can('product_category')
                                            <a href="{{ route('product-category.index') }}"
                                                class="subdrop {{ Request::routeIs('product-category.index') ? 'active' : '' }}">
                                                <span>Product Category</span>
                                            </a>
                                        @endcan
                                    </li>
                                    <li>
                                        @can('products')
                                            <a href="{{ route('products.index') }}"
                                                class="subdrop {{ Request::routeIs('products.index') ? 'active' : '' }}">
                                                <span>Products</span>
                                            </a>
                                        @endcan
                                    </li>
                                    <li>
                                        @can('vehicles')
                                            <a href="{{ route('vehicle.index') }}"
                                                class="subdrop {{ Request::routeIs('vehicle.index') ? 'active' : '' }}">
                                                <span>Vehicle</span>
                                            </a>
                                        @endcan
                                    </li>
                                    <li>
                                        @can('special_price')
                                            <a href="{{ route('special-price.index') }}"
                                                class="subdrop {{ Request::routeIs('special-price.index') ? 'active' : '' }}">
                                                <span>Special Price</span>
                                            </a>
                                        @endcan
                                    </li>
                                    <li>
                                        <a href="{{ route('global-tax.index') }}"
                                            class="subdrop {{ Request::routeIs('global-tax.index') ? 'active' : '' }}">
                                            <span>Global Tax</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('commission-type.index') }}"
                                            class="subdrop {{ Request::routeIs('commission-type.index') ? 'active' : '' }}">
                                            <span>Commission Type</span>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                        @endcanany
                    </ul>
                </li>
            </ul>

            <ul>
                <li>
                    <ul>
                        @canany(['open_close_report', 'purchase_report', 'order_report', 'sales_report', 'stock_report',
                            'commission_report'])
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="{{ Request::routeIs('open-close.index', 'purchase-report.index', 'order-report.index', 'sales-report.index', 'stock-report.index', 'commission-report.index', 'customer-credit-report.index', 'employee-payment-collection-report.index', 'daily-sales-report.index', 'supplier-payment-report.index', 'product-wise-sales-report.index', 'customer-payment-report.index') ? 'active' : '' }}">
                                    <i class="ti ti-report mr-4"></i>
                                    <span>Report Management</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul
                                    style="{{ Request::routeIs('open-close.index', 'purchase-report.index', 'order-report.index', 'sales-report.index', 'stock-report.index', 'commission-report.index', 'customer-credit-report.index', 'employee-payment-collection-report.index', 'daily-sales-report.index', 'supplier-payment-report.index', 'product-wise-sales-report.index', 'customer-payment-report.index') ? 'display: block;' : '' }}">
                                    <li>
                                        @can('open_close_report')
                                            <a href="{{ route('open-close.index') }}"
                                                class="subdrop {{ Request::routeIs('open-close.index') ? 'active' : '' }}">
                                                <span>Open Close Report</span>
                                            </a>
                                        @endcan
                                    </li>
                                    <li>
                                        @can('purchase_report')
                                            <a href="{{ route('purchase-report.index') }}"
                                                class="subdrop {{ Request::routeIs('purchase-report.index') ? 'active' : '' }}">
                                                <span>Purchase Report</span>
                                            </a>
                                        @endcan
                                    </li>
                                    <li>
                                        @can('order_report')
                                            <a href="{{ route('order-report.index') }}"
                                                class="subdrop {{ Request::routeIs('order-report.index') ? 'active' : '' }}">
                                                <span>Order Report</span>
                                            </a>
                                        @endcan
                                    </li>
                                    <li>
                                        @can('sales_report')
                                            <a href="{{ route('sales-report.index') }}"
                                                class="subdrop {{ Request::routeIs('sales-report.index') ? 'active' : '' }}">
                                                <span>Sales Report</span>
                                            </a>
                                        @endcan
                                    </li>
                                    <li>
                                        @can('stock_report')
                                            <a href="{{ route('stock-report.index') }}"
                                                class="subdrop {{ Request::routeIs('stock-report.index') ? 'active' : '' }}">
                                                <span>Godown Stock Transfer Report</span>
                                            </a>
                                        @endcan
                                    </li>

                                    <li>
                                        {{-- @can('original_stock_report') --}}
                                        <a href="{{ route('original-stock-report.index') }}"
                                            class="subdrop {{ Request::routeIs('original-stock-report.index') ? 'active' : '' }}">
                                            <span>Stock Report</span>
                                        </a>
                                        {{-- @endcan --}}
                                    </li>
                                    <li>
                                        @can('commission_report')
                                            <a href="{{ route('commission-report.index') }}"
                                                class="subdrop {{ Request::routeIs('commission-report.index') ? 'active' : '' }}">
                                                <span>Commission Report</span>
                                            </a>
                                        @endcan
                                    </li>
                                    <li>
                                        <a href="{{ route('customer-credit-report.index') }}"
                                            class="subdrop {{ Request::routeIs('customer-credit-report.index') ? 'active' : '' }}">
                                            <span>Customer Credit Report</span>
                                        </a>
                                    </li>

                                    <li>

                                        <a href="{{ route('customer-due-report.index') }}"
                                            class="subdrop {{ Request::routeIs('customer-due-report.index') ? 'active' : '' }}">
                                            <span>Customer Due Report</span>
                                        </a>

                                    </li>
                                    <li>
                                        <a href="{{ route('daily-sales-report.index') }}"
                                            class="subdrop {{ Request::routeIs('daily-sales-report.index') ? 'active' : '' }}">
                                            <span>Daily Sales Report</span>
                                        </a>
                                    </li>
                                    {{-- <li>
                                        <a href="{{ route('employee-payment-collection-report.index') }}"
                                            class="subdrop {{ Request::routeIs('employee-payment-collection-report.index') ? 'active' : '' }}">
                                            <span>Payment Collection Report</span>
                                        </a>
                                    </li> --}}
                                    <li>
                                        <a href="{{ route('customer-payment-report.index') }}"
                                            class="subdrop {{ Request::routeIs('customer-payment-report.index') ? 'active' : '' }}">
                                            <span>Customer Payment Report</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('supplier-credit-report.index') }}"
                                            class="subdrop {{ Request::routeIs('supplier-credit-report.index') ? 'active' : '' }}">
                                            <span>Supplier Credit Report</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('supplier-payment-report.index') }}"
                                            class="subdrop {{ Request::routeIs('supplier-payment-report.index') ? 'active' : '' }}">
                                            <span>Supplier Payment Report</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('product-wise-sales-report.index') }}"
                                            class="subdrop {{ Request::routeIs('product-wise-sales-report.index') ? 'active' : '' }}">
                                            <span>Product Wise Sales Report</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcanany
                    </ul>
                </li>
            </ul>


            <ul>
                <li>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);"
                                class="{{ Request::routeIs('accounts.index', 'expenses.index', 'payroll.index', 'employee.index', 'accounting-reports.index') ? 'active' : '' }}">
                                <i class="ti ti-calculator mr-4"></i>
                                <span>Payroll Management</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul
                                style="{{ Request::routeIs('accounts.index', 'expenses.index', 'payroll.index', 'employee.index', 'accounting-reports.index') ? 'display: block;' : '' }}">


                                {{-- <li>
                                    <a href="{{ route('attendance.index') }}"
                                        class="subdrop {{ Request::routeIs('attendance.index') ? 'active' : '' }}">
                                        <span>Attendance</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('leaves.index') }}"
                                        class="subdrop {{ Request::routeIs('leave.index') ? 'active' : '' }}">
                                        <span>Leaves</span>
                                    </a>
                                </li> --}}
                                <li>
                                    <a href="{{ route('advance.index') }}"
                                        class="subdrop {{ Request::routeIs('advance.index') ? 'active' : '' }}">
                                        <span>Advance Salary</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('payroll.index') }}"
                                        class="subdrop {{ Request::routeIs('payroll.index') ? 'active' : '' }}">
                                        <span>Payroll</span>
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('commission.index') }}"
                                        class="subdrop {{ Request::routeIs('commission.index') ? 'active' : '' }}">
                                        <span>Commission</span>
                                    </a>
                                </li>


                            </ul>
                        </li>
                    </ul>
                </li>
            </ul>
            <ul>
                <li>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);"
                                class="{{ Request::routeIs('accounts.index', 'payroll.index', 'employee.index', 'accounting-reports.index') ? 'active' : '' }}">
                                <i class="ti ti-calculator mr-4"></i>
                                <span>Accounts Management</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul
                                style="{{ Request::routeIs('accounts.index', 'expenses.index', 'payroll.index', 'employee.index', 'accounting-reports.index') ? 'display: block;' : '' }}">
                                <li>
                                    <a href="{{ route('accounts.index') }}"
                                        class="subdrop {{ Request::routeIs('accounts.index') ? 'active' : '' }}">
                                        <span>Chart of Accounts</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('financial_years.index') }}"
                                        class="subdrop {{ Request::routeIs('financial_years.index') ? 'active' : '' }}">
                                        <span>Financial Years</span>
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('opening_balance.index') }}"
                                        class="subdrop {{ Request::routeIs('opening_balance.index') ? 'active' : '' }}">
                                        <span>Opening Balance</span>
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('vouchers.index') }}"
                                        class="subdrop {{ Request::routeIs('vouchers.index') ? 'active' : '' }}">
                                        <span>Voucher</span>
                                    </a>
                                </li>


                                <li>
                                    <a href="{{ route('accounting-reports.index') }}"
                                        class="subdrop {{ Request::routeIs('accounting-reports.index') ? 'active' : '' }}">
                                        <span>Accounting Reports</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
