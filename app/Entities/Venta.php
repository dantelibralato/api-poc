<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model {

    protected $table = 'tabla_ventas';
    #protected $primaryKey = 'id_venta';
    protected $keyType = 'int';
    protected $fillable = [
        'id_producto', 'id_moneda', 'moneda', 'fecha'
    ];
    public $timestamps = false;

}
