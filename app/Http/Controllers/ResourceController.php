<?php

namespace App\Http\Controllers;

use App\Models\Apprentice;
use App\Models\Area;
use App\Models\Computer;
use App\Models\Contact;
use App\Models\Course;
use App\Models\Gallery;
use App\Models\Teacher;
use App\Models\Training_Center;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResourceController extends Controller
{
    // Relaciona cada URL con el modelo que representa su tabla.
    private array $models = [
        'areas' => Area::class,
        'contacts' => Contact::class,
        'galleries' => Gallery::class,
        'training-centers' => Training_Center::class,
        'computers' => Computer::class,
        'courses' => Course::class,
        'teachers' => Teacher::class,
        'apprentices' => Apprentice::class,
    ];

    public function index(string $resource)
    {
        // GET /api/v1/recurso: devuelve todos los registros.
        $model = $this->model($resource);
        $query = $model::query();

        if ($resource === 'galleries') {
            $query->orderBy('order');
        }

        return response()->json($query->get());
    }

    public function show(Request $request, string $id)
    {
        // GET /api/v1/recurso/{id}: devuelve un registro concreto.
        $resource = $request->route('resource');

        return response()->json($this->model($resource)::findOrFail($id));
    }

    public function store(Request $request, string $resource)
    {
        // POST /api/v1/recurso: crea un registro con los datos recibidos.
        $model = $this->model($resource);
        $item = $model::create($request->all());

        return response()->json($item, 201);
    }

    public function update(Request $request, string $id)
    {
        // PUT /api/v1/recurso/{id}: actualiza un registro existente.
        $resource = $request->route('resource');
        $item = $this->model($resource)::findOrFail($id);
        $item->update($request->all());

        return response()->json($item);
    }

    public function destroy(Request $request, string $id)
    {
        // DELETE /api/v1/recurso/{id}: elimina un registro existente.
        $resource = $request->route('resource');
        $item = $this->model($resource)::findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => Str::headline($resource) . ' eliminado correctamente.',
        ]);
    }

    private function model(string $resource): string
    {
        // Evita consultar modelos que no hayan sido autorizados en la API.
        abort_unless(isset($this->models[$resource]), 404, 'Recurso no encontrado.');

        return $this->models[$resource];
    }
}
