<?php

namespace DipenduRoy\LumenZipkin;

use App\Http\Controllers\Controller;

class LumenZipkinController extends Controller {
    
    public function __construct() {
        //
    }
    
    public static function grab()
    {
        $data       = 'this is test data';
        
        return $data;
    }
}