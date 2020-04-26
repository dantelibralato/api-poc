<?php

namespace App\Http\Controllers;

use App\Http\Controllers\APIController;
use Illuminate\Http\Request;

class ProductoController extends APIController {

    public function index(Request $request) {
        \Log::info('request', $request->input());
        return $this->productoRepo->obtener($request->input());
    }

    public function indexUrl(Request $request, $id_producto, $anio, $mes, $moneda, $usuario_consulta) {
        \Log::info('request', $request->input());
        return $this->productoRepo->obtener(compact('id_producto', 'anio', 'mes', 'moneda', 'usuario_consulta'));
    }

}
