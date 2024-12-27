<?php

namespace App\Http\Controllers\Dashboard;

use JeroenNoten\LaravelAdminLte\AdminLte;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \PhpOffice\PhpSpreadsheet\Spreadsheet;
use \PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Str;
use Exception;
use Auth;

class MainController extends Controller
{
    protected $adminlte;
    
    /**
     * Constructor for the controller.
     * This middleware ensures that the user is authenticated before accessing the controller's methods.
     * It also initializes the `$adminlte` property with the provided AdminLte instance.
     *
     * @param AdminLte $adminlte The AdminLte instance used to manage the admin panel layout and menu.
     * @return void
     */
    public function __construct(AdminLte $adminlte)
    {
        $this->middleware('auth');
        $this->adminlte = $adminlte;
    }

    /**
     * Show the home page.
     * Redirects to the login page if the user is not authenticated.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Contracts\Support\Renderable
     */
    public function home()
    {
        if (!Auth::check()) 
        {
            return redirect()->route('login');
        }
        return $this->dashboard();
    }
    /**
     * Show the application dashboard.
     * It retrieves the sidebar menu and dynamically adds a new item to the submenu if "Imported Data" is found.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function dashboard()
    {
        $menu = $this->adminlte->menu('sidebar');
       
        $key = array_keys(array_filter($menu, fn($item) => isset($item['text']) && $item['text'] === 'Imported Data'))[0] ?? null;
        
        if ($key !== null && isset($menu[$key]['submenu'])) 
        {
            // Dinamički dodajte stavku u submenu
            $menu[$key]['submenu'][] = [
                'text' => 'New Item',
                'url' => '#',
                "href" => "#",
                "active" => false,
                "class" => ""
            ];
        }
        
        return view('dashboard/main');
    }

    public function create_files($model, $type)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Create the method name based on $model and $type
        $method_name = "{$model}_{$type}";
        
        // Check if the method exists in the current class
        if (method_exists($this, $method_name)) 
        {
            // Call the method dynamically
            $this->$method_name($sheet);
        } 
        else 
        {
            throw new Exception("Invalid method: $method_name");
        }

        $writer = new Xlsx($spreadsheet);

        $filePath = 'import_test_files/' . $model . '/'. $type .'_file1.xlsx';

        $writer->save($filePath);

        return Str::title("$model $type file created");
    }

    protected function orders_valid($sheet)
    {
        $sheet->fromArray([
            ['Order Date', 'Channel', 'SKU', 'Item Description', 'Origin', 'SO#', 'Cost', 'Shipping Cost', 'Total Price'],
            ['01.12.2023', 'PT', 'N12345-99', 'Item 1', 'USA', 'SO001', 100.00, 10.00, 110.00],
            ['02.12.2023', 'Amazon', 'N12345-88', 'Item 2', 'USA', 'SO002', 150.00, 15.00, 165.00],
            ['03.12.2023', 'PT', 'N12345-77', 'Item 3', 'USA', 'SO003', 200.00, 20.00, 220.00],
        ]);
    }
    protected function orders_errors($sheet)
    {
        $sheet->fromArray([
            ['Order Date', 'Channel', 'SKU', 'Item Description', 'Origin', 'SO#', 'Cost', 'Shipping Cost', 'Total Price'],
            ['01.12.2023', 'InvalidChannel', 'N12345-99', 'Item 1', 'USA', 'SO001', 100.00, 10.00, 110.00], // Invalid channel
            ['02.12.2023', 'Amazon', 'N12345-88', 'Item 2', 'USA', 'SO002', 100.00, 65.00, 165.00], // Invalid cost
            ['03.12.2023', 'PT', 'N12345-77', 'Item 3', 'USA', 'SO003', 200.00, 20.00, 220.00], // Invalid shipping cost
        ]);
    }
    protected function orders_invalid($sheet)
    {
        $sheet->fromArray([
            ['Order Date', 'Channel', 'SKU', 'Item Description', 'Origin', 'SO#', 'Cost', 'Shipping Cost', 'Total Price'],
            ['InvalidDate', 'InvalidChannel', 'InvalidSKU', 'Item 1', 'USA', 'SO001', 'InvalidCost', 'InvalidShippingCost', 'InvalidTotalPrice'], // Invalid data
        ]);
    }
    protected function items_valid($sheet)
    {
        $sheet->fromArray([
            ['Item ID', 'Name', 'Category', 'Price', 'Stock'],
            ['ITEM123', 'Item 1', 'Category 1', 100.00, 10],
            ['ITEM124', 'Item 2', 'Category 2', 150.00, 15],
            ['ITEM125', 'Item 3', 'Category 3', 200.00, 20],
        ]);
    }
    protected function items_errors($sheet)
    {
        $sheet->fromArray([
            ['Item ID', 'Name', 'Category', 'Price', 'Stock'],
            ['ITEM126', 'Item 4', 'Category 1', 75.00, 10], 
            ['ITEM127', 'Item 5', 'Category 2', 150.00, 30], 
            ['ITEM128', '', 'Category 3', 200.00, 20], // Missing name
        ]);
    }
    protected function items_invalid($sheet)
    {
        $sheet->fromArray([
            ['Item ID', 'Name', 'Category', 'Price', 'Stock'],
            ['InvalidID', '', 'InvalidCategory', 'InvalidPrice', 'InvalidStock'], // Invalid data
        ]);
    }
    protected function clients_valid($sheet)
    {
        $sheet->fromArray([
            ['Client ID', 'Name', 'Email', 'Phone'],
            ['CLIENT123', 'Client 1', 'client1@example.com', '1234567890'],
            ['CLIENT124', 'Client 2', 'client2@example.com', '2345678901'],
            ['CLIENT125', 'Client 3', 'client3@example.com', '3456789012'],
        ]);
    }
    protected function clients_errors($sheet)
    {
        $sheet->fromArray([
            ['Client ID', 'Name', 'Email', 'Phone'],
            ['CLIENT126', 'Client 4', 'invalidemail', '1234567890'], // Invalid email
            ['CLIENT127', 'Client 5', 'client5@example.com', '2345678901'], 
            ['CLIENT128', 'Client 6', 'client6@example.com', 'invalidphone'], // Invalid phone
        ]);
    }
    protected function clients_invalid($sheet)
    {
        $sheet->fromArray([
            ['Client ID', 'Name', 'Email', 'Phone'],
            ['InvalidID', '', 'invalidemail', 'invalidphone'], // Invalid data
        ]);
    }
    protected function sales_valid($sheet)
    {
        $sheet->fromArray([
            ['Sale ID', 'Client ID', 'Sale Date', 'Total'],
            ['SALE123', 'CLIENT123', '2023-12-01', 100.00],
            ['SALE124', 'CLIENT124', '2023-12-02', 150.00],
            ['SALE125', 'CLIENT125', '2023-12-03', 200.00],
        ]);
    }
    protected function sales_errors($sheet)
    {
        $sheet->fromArray([
            ['Sale ID', 'Client ID', 'Sale Date', 'Total'],
            ['SALE126', 'CLIENT126', '2023-12-01', 100.00], 
            ['SALE127', 'CLIENT127', 'invalid_date', 150.00], // Invalid date
            ['SALE128', 'CLIENT128', '2023-12-03', 75.00], // Invalid total
        ]);
    }
    protected function sales_invalid($sheet)
    {
        $sheet->fromArray([
            ['Sale ID', 'Client ID', 'Sale Date', 'Total'],
            ['InvalidID', 'InvalidClientID', 'InvalidDate', 'InvalidTotal'], // Invalid data
        ]);
    }
}
