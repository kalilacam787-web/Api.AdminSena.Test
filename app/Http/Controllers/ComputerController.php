<?php

namespace App\Http\Controllers;

use App\Models\Apprentice;
use App\Models\Computer;
use App\Models\Teacher;
use App\Services\ComputerImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class ComputerController extends Controller
{
    public function index()
    {
        // Carga asignaciones para mostrarlas sin consultas adicionales.
        $computer = Computer::with(['apprentice', 'teacher'])->orderBy('id')->get();

        return view('Computer.index', [
            'computer' => $computer,
            'apprentices' => Apprentice::orderBy('name')->get(),
            'teachers' => Teacher::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('Computer.create');
    }

    public function store(Request $request, ComputerImageStorage $images)
    {
        $data = $request->validate([
            // El serial identifica el equipo; el consecutivo interno se genera al guardar.
            'serial' => ['required', 'regex:/^[0-9]{5,20}$/', 'unique:computers,serial'],
            'brand' => 'required|string|max:100',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $asset = $images->upload($request->file('image'));
            $data['image_path'] = $asset['url'];
            $data['image_public_id'] = $asset['public_id'];
        }

        unset($data['image']);
        try {
            DB::transaction(function () use (&$data) {
                $lastNumber = Computer::query()
                    ->lockForUpdate()
                    ->pluck('number')
                    ->filter(fn ($number) => ctype_digit((string) $number))
                    ->map(fn ($number) => (int) $number)
                    ->max() ?? 0;

                if ($lastNumber >= 2147483647) {
                    throw new \RuntimeException('No se pudo generar el consecutivo del computador.');
                }

                $data['number'] = $lastNumber + 1;
                Computer::create($data);
            });
        } catch (Throwable $exception) {
            if (! empty($data['image_public_id'])) {
                try {
                    $images->delete($data['image_public_id']);
                } catch (Throwable $cleanupException) {
                    Log::error('No se pudo limpiar la imagen de Cloudinary tras fallar el registro del computador.', [
                        'exception' => $cleanupException,
                    ]);
                }
            }

            throw $exception;
        }

        return redirect()->route('computers.index')->with('success', 'Computador registrado correctamente.');
    }

    public function uploadImage(Request $request, Computer $computer, ComputerImageStorage $images)
    {
        $image = $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ])['image'];

        $asset = $images->upload($image);
        $previousImage = $computer->image_path;
        $previousPublicId = $computer->image_public_id;
        $computer->image_path = $asset['url'];
        $computer->image_public_id = $asset['public_id'];
        try {
            $computer->save();
        } catch (Throwable $exception) {
            try {
                $images->delete($asset['public_id']);
            } catch (Throwable $cleanupException) {
                Log::error('No se pudo limpiar la imagen de Cloudinary tras fallar su registro en el computador.', [
                    'exception' => $cleanupException,
                ]);
            }

            throw $exception;
        }

        $this->deletePreviousImage($images, $previousImage, $previousPublicId);

        return redirect()->route('computers.index')->with('success', 'Imagen del computador actualizada correctamente.');
    }

    public function deleteImage(Computer $computer, ComputerImageStorage $images)
    {
        $this->deletePreviousImage($images, $computer->image_path, $computer->image_public_id);

        $computer->update(['image_path' => null, 'image_public_id' => null]);

        return redirect()->route('computers.index')->with('success', 'Imagen del computador eliminada correctamente.');
    }

    public function assign(Request $request, Computer $computer)
    {
        $rules = [
            'serial' => ['required', 'regex:/^[0-9]{5,20}$/', Rule::unique('computers', 'serial')->ignore($computer->id)],
            'assigned_to' => ['nullable', 'regex:/^(apprentice|teacher):[1-9][0-9]*$/'],
        ];

        $assignment = $request->input('assigned_to');
        if (is_string($assignment) && preg_match('/^(apprentice|teacher):([1-9][0-9]*)$/', $assignment, $matches)) {
            // Comprueba que el ID pertenezca a la tabla indicada.
            $table = $matches[1] === 'apprentice' ? 'apprentices' : 'teachers';
            $rules['assigned_to'][] = Rule::exists($table, 'id');
        }

        $data = $request->validate($rules);
        $assignment = $data['assigned_to'] ?? null;

        // Actualiza serial y asignación juntos para evitar cambios parciales.
        DB::transaction(function () use ($computer, $assignment, $data) {
            $computer = Computer::query()->lockForUpdate()->findOrFail($computer->id);
            $computer->serial = $data['serial'];
            Apprentice::where('computer_id', $computer->id)->update(['computer_id' => null]);
            $computer->teacher_id = null;

            if ($assignment !== null) {
                [$type, $id] = explode(':', $assignment, 2);
                if ($type === 'apprentice') {
                    Apprentice::whereKey($id)->update(['computer_id' => $computer->id]);
                } else {
                    $computer->teacher_id = $id;
                }
            }

            $computer->save();
        });

        return redirect()->route('computers.index')->with('success', 'Serial y asignación del computador actualizados correctamente.');
    }

    public function destroy(Computer $computer, ComputerImageStorage $images)
    {
        $this->deletePreviousImage($images, $computer->image_path, $computer->image_public_id);
        $computer->delete();

        return redirect()->route('computers.index')->with('success', 'Computador eliminado correctamente.');
    }

    private function deletePreviousImage(ComputerImageStorage $images, ?string $imagePath, ?string $publicId): void
    {
        if ($publicId) {
            $images->delete($publicId);

            return;
        }

        if ($imagePath && ! filter_var($imagePath, FILTER_VALIDATE_URL) && ! Storage::disk('public')->delete($imagePath)) {
            throw new \RuntimeException('No se pudo eliminar la imagen anterior del computador.');
        }
    }
}
