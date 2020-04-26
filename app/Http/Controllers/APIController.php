<?php

namespace App\Http\Controllers;

use App\Repositories\ProductoRepo;

class APIController extends Controller {

    protected $productoRepo;

    public function __construct(ProductoRepo $productoRepo) {
        $this->productoRepo = $productoRepo;
    }

}
