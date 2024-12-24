<?php

namespace App\Http\Controllers\Dashboard;

use JeroenNoten\LaravelAdminLte\AdminLte;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
}
