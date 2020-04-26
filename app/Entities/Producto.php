<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model {

    protected $table = 'tabla_producto';
    protected $primaryKey = 'id_producto';
    protected $keyType = 'int';
    protected $fillable = ['id_producto', 'descripcion_producto'];
    public $timestamps = false;

}
