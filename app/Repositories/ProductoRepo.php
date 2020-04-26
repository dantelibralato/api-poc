<?php

namespace App\Repositories;

use App\Entities\Producto;
use App\Entities\Venta;
use App\Entities\Moneda;
use Validator;
//use Response;
use DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Psr7\Response;

class ProductoRepo extends BaseRepo {

    const TIPO_CAMBIO_DEFAULT = 3;
    const TIPO_MONEDA_PEN = 'PEN';
    const TIPO_MONEDA_USD = 'USD';

    private function validarDatos($request) {
        $messages = [
            'id_producto.required' => 'id_producto es requerido.',
            'moneda.required' => 'moneda es requerido.',
            'usuario_consulta.required' => 'usuario_consulta es requerido.',
        ];

        $rules = [
            'id_producto' => 'required',
            'moneda' => 'required',
            'usuario_consulta' => 'required',
        ];

        return Validator::make($request, $rules, $messages);
    }

    public function obtener($data = []) {
        $validado = $this->validarDatos($data);

        if (!$validado->fails()) {
            $_ventas = $this->obtenerVentas($data);
            $ventas = $this->procesarVentas($data, $_ventas);
            $ventas->map(function($item) {
                \Log::info('calculo', [$item['descripcion_producto'], $item['anio_mes'], $item['monto_total'], $item['descripcion_moneda']]);
            });
            \Log::info('response', [$ventas]);
            return $ventas;
        } else {
            \Log::info('response', [$this->getRequestError($validado->errors())]);
            return $this->getRequestError($validado->errors());
        }
    }

    protected function obtenerVentas(Array $data = []) {
        $id_producto = intval(Arr::get($data, 'id_producto', ''));
        $anio = intval(Arr::get($data, 'anio', ''));
        $mes = intval(Arr::get($data, 'mes', ''));

        $ventas = DB::table('tabla_ventas AS tv')
                ->leftJoin('tabla_producto AS tp', 'tv.id_producto', '=', 'tp.id_producto')
                ->leftJoin('tabla_moneda AS tm', 'tv.id_moneda', '=', 'tm.id_moneda')
                ->where('tp.id_producto', '=', $id_producto)
        ;

        if (!empty($anio)) {
            $ventas->where(DB::raw("to_char(tv.fecha, 'YYYY')::int"), '=', $anio);
        }

        if (!empty($mes)) {
            $ventas->where(DB::raw("to_char(tv.fecha, 'MM')::int"), '=', $mes);
        }

        $ventas->select([
            'tp.id_producto', 'tp.descripcion_producto',
            'tm.iso_moneda', 'tm.descripcion_moneda',
            'tv.monto', DB::raw("to_char(tv.fecha, 'YYYYMM') AS anio_mes"),
            DB::raw("to_char(tv.fecha, 'DD/MM/YYYY') AS fecha")
        ]);

        return $ventas->get();
    }

    protected function procesarVentas($_data, $datos) {
        $iso_moneda = Arr::get($_data, 'moneda', '');
        $moneda = $this->obtenerMoneda($iso_moneda);

        $_fechas = $datos->pluck('fecha')->unique();
        $fechas = $this->obtenerTipoCambioPorFechaSingle($_fechas);

        $ventas = $datos->map(function ($dato) use ($iso_moneda, $moneda, $fechas) {
                    $_monto = 0;
                    if ($iso_moneda === $dato->iso_moneda) {
                        $_monto = floatval($dato->monto);
                    } else {
                        $tipo_cambio = floatval(Arr::get($fechas, $dato->fecha));
                        if ($iso_moneda === self::TIPO_MONEDA_PEN) {
                            $_monto = floatval($dato->monto) * $tipo_cambio;
                        } elseif ($iso_moneda === self::TIPO_MONEDA_USD) {
                            $_monto = round(floatval($dato->monto) / $tipo_cambio, 2);
                        }
                    }

                    $monto_estandar = $_monto;

                    return [
                        'descripcion_producto' => $dato->descripcion_producto,
                        'descripcion_moneda' => $moneda,
                        'anio_mes' => $dato->anio_mes,
                        'monto_estandar' => $monto_estandar
                    ];
                })
                ->sortByDesc('anio_mes')
                ->all()
        ;

        $data = collect($this->groupByPartAndType($ventas))
                ->map(function($item) {
            $item['monto_total'] = round($item['monto_total'], 2);
            return $item;
        })
        ;

        return $data;
    }

    protected function obtenerMoneda($iso_moneda) {
        $moneda = Moneda::where('iso_moneda', '=', $iso_moneda)
                ->select(['descripcion_moneda'])
                ->first()
                ->toArray();
        ;

        $descripcion_moneda = Arr::get($moneda, 'descripcion_moneda', '');

        return $descripcion_moneda;
    }

    protected function groupByPartAndType($input) {
        $output = [];

        foreach ($input as $value) {
            $output_element = &$output[$value['descripcion_producto'] . '_' . $value['descripcion_moneda'] . '_' . $value['anio_mes']];
            $output_element['descripcion_producto'] = $value['descripcion_producto'];
            $output_element['descripcion_moneda'] = $value['descripcion_moneda'];
            $output_element['anio_mes'] = $value['anio_mes'];
            !isset($output_element['monto_total']) && $output_element['monto_total'] = 0;
            $output_element['monto_total'] += $value['monto_estandar'];
        }

        return array_values($output);
    }

    protected function obtenerTipoCambioPorFecha($_fecha) {
        $tipo_cambio = self::TIPO_CAMBIO_DEFAULT;

        if (!empty($_fecha)) {
            $d = Carbon::createFromFormat('d/m/Y', $_fecha);
            $fecha = $d->format('Ymd');
            $response = Http::get($this->getUrlTipoCambio() . $fecha);
            $item = $response->json();
            $tipo_cambio = floatval(Arr::get($item, $_fecha, self::TIPO_CAMBIO_DEFAULT));
        }

        return $tipo_cambio;
    }

    protected function obtenerTipoCambioPorFechas($_fechas) {
        $data = [];
        foreach ($_fechas as $key => $_fecha) {
            if (!empty($_fecha)) {
                $d = Carbon::createFromFormat('d/m/Y', $_fecha);
                $fecha = $d->format('Ymd');
                $_fechas[$key] = $fecha;
            }
        }
        $response = Http::post($this->getUrlTipoCambio() . 'buscarPorFechas', ["fechas" => $_fechas]);
        $items = $response->json();

        foreach ($items as $key => $item) {
            $tipo_cambio = self::TIPO_CAMBIO_DEFAULT;

            if (!empty($item)) {
                $tipo_cambio = floatval($item);
            }
            $items[$key] = $tipo_cambio;
        }

        $data = $items;
        return $data;
    }

    protected function obtenerTipoCambioPorFechaSingle($_fechas = []) {
        $fechas = [];
        foreach ($_fechas as $key => $_fecha) {
            if (!empty($_fecha)) {
                $d = Carbon::createFromFormat('d/m/Y', $_fecha);
                $fecha = $d->format('Ymd');
                $fechas[$key] = $fecha;
            }
        }
        $client = new Client();
        $promises = (function () use ($fechas, $client) {
                    foreach ($fechas as $fecha) {
                        \Log::info('', [$fecha]);
                        yield $client->getAsync($this->getUrlTipoCambio() . $fecha);
                    }
                })();

        $tipocambio = [];
        $eachPromise = new EachPromise($promises, [
            // how many concurrency we are use
            'concurrency' => 10,
            'fulfilled' => function (Response $response) use(&$tipocambio) {
                if ($response->getStatusCode() == 200) {
                    $dato = json_decode($response->getBody(), true);
                    // processing response of user here
                    $tipocambio[] = $dato;
                }
            },
            'rejected' => function ($reason) {
                \Log::info('Promise.rejected', [$reason]);
                // handle promise rejected here
            }
        ]);

        $eachPromise->promise()->wait();
        return $tipocambio;
    }

}
