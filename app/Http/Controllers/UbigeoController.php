<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\District;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class UbigeoController extends Controller
{
    /**
     * Message returned when the location catalog cannot be read.
     */
    public const UNAVAILABLE_MESSAGE = 'No se pudo cargar el catálogo de ubicaciones.';

    /**
     * Return the location catalog the property form needs to offer its selectors.
     */
    public function index(): JsonResponse
    {
        try {
            $departments = Department::query()->orderBy('code')->get(['code', 'name'])->toArray();
            $provinces = Province::query()
                ->orderBy('code')
                ->get(['code', 'name', 'department_code'])
                ->toArray();
            $districts = District::query()
                ->orderBy('code')
                ->get(['code', 'name', 'province_code'])
                ->toArray();
        } catch (Throwable $exception) {
            Log::error('Unable to read the location catalog.', [
                'exception' => $exception,
            ]);

            return $this->unavailable();
        }

        if ($departments === [] || $provinces === [] || $districts === []) {
            return $this->unavailable();
        }

        return response()->json([
            'departments' => $departments,
            'provinces' => $provinces,
            'districts' => $districts,
        ]);
    }

    private function unavailable(): JsonResponse
    {
        return response()->json(['message' => self::UNAVAILABLE_MESSAGE], 503);
    }
}
