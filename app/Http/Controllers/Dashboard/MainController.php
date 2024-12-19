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
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(AdminLte $adminlte)
    {
        $this->middleware('auth');
        $this->adminlte = $adminlte;
    }

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
