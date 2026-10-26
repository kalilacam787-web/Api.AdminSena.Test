<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
use Illuminate\Validation\Rule;

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
        if ($resource === 'apprentices') {
            $request->validate(['computer_id' => 'prohibited']);
        }

        $data = $resource === 'computers'
            ? $request->validate([
                'serial' => ['required', 'regex:/^[0-9]{5,20}$/', 'unique:computers,serial'],
                'number' => 'required|integer|min:1|max:2147483647|unique:computers,number',
                'brand' => 'required|string|max:100',
            ])
            : $request->all();
        $item = $model::create($data);

        return response()->json($item, 201);
    }

    public function update(Request $request, string $id)
    {
        // PUT /api/v1/recurso/{id}: actualiza un registro existente.
        $resource = $request->route('resource');
        $item = $this->model($resource)::findOrFail($id);
        if ($resource === 'apprentices') {
            $request->validate(['computer_id' => 'prohibited']);
        }

        $data = $resource === 'computers'
            ? $request->validate([
                'serial' => ['sometimes', 'required', 'regex:/^[0-9]{5,20}$/', Rule::unique('computers', 'serial')->ignore($item->id)],
                'number' => ['sometimes', 'required', 'integer', 'min:1', 'max:2147483647', Rule::unique('computers', 'number')->ignore($item->id)],
                'brand' => 'sometimes|required|string|max:100',
            ])
            : $request->all();
        $item->update($data);

        return response()->json($item);
    }

    public function destroy(Request $request, string $id)
    {
        // DELETE /api/v1/recurso/{id}: elimina un registro existente.
        $resource = $request->route('resource');
        $item = $this->model($resource)::findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => Str::headline($resource).' eliminado correctamente.',
        ]);
    }

    private function model(string $resource): string
    {
        // Evita consultar modelos que no hayan sido autorizados en la API.
        abort_unless(isset($this->models[$resource]), 404, 'Recurso no encontrado.');

        return $this->models[$resource];
    }
}
