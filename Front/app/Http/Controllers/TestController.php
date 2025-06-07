<?php
namespace App\Http\Controllers;

use App\Contracts\TestHosting;
use Inertia\Inertia;

class TestController extends Controller
{
    public function index(TestHosting $testHosting)
    {
        // dd('ok');
        return Inertia::render('Test/TestPage', ['test' => $testHosting->showString()]);
    }
}
