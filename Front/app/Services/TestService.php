<?php
namespace App\Services;

use App\Contracts\TestHosting;

class TestService implements TestHosting
{
    public function test()
    {
        return "test servisas veikia";
    }
    public function showString()
    {
        return $this->test();
    }
}
