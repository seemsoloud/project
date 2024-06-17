<?php

namespace App\Controllers;

use App\Kernel\Controller\Controller;
use App\Services\CategoryService;

class BestController extends Controller
{
    private CategoryService $service;

    public function index(): void
    {
        $this->view('best');
    }
}
