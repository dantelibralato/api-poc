<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class Moneda extends Model {

    protected $table = 'tabla_moneda';
    protected $primaryKey = 'id_moneda';
    protected $keyType = 'int';
    protected $fillable = ['id_moneda', 'iso_moneda', 'descripcion_moneda'];
    public $timestamps = false;

}
