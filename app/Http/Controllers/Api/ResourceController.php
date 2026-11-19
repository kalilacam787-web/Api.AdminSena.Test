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
use App\Models\User;
use App\Services\ComputerImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

trait AdminControllerActions
{
    public function adminProfile()
    {
        return view('Admin.profile', ['user' => auth()->user()]);
    }

    public function adminUpdate(Request $request)
    {
        $data = $request->validate([
            'document_type' => ['required', 'in:CC,TI,CE,PP,NIT'],
            'document_number' => ['required', 'digits_between:6,15'],
        ], [
            'document_number.digits_between' => 'El documento debe tener entre 6 y 15 números.',
            'document_number.digits' => 'El documento solo puede contener números.',
        ]);

        $request->user()->update($data);

        return back()->with('success', 'Perfil actualizado correctamente.');
    }
}

trait ApprenticeControllerActions
{
    public function apprenticeIndex()
    {
        $apprentices = Apprentice::with(['course.trainingCenter', 'computer'])->get();

        return view('Apprentice.index', compact('apprentices'));
    }

    public function apprenticeCreate()
    {
        $courses = Course::all();
        $apprentices = Apprentice::all();

        return view('Apprentice.create', compact('courses', 'apprentices'));
    }

    public function apprenticeEnrollmentForm(Course $course)
    {
        return view('Apprentice.enroll', compact('course'));
    }

    public function apprenticeEnroll(Request $request, Course $course)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'document_type' => 'required|in:CC,TI,PAS',
            'document_number' => 'required|string|max:30|unique:apprentices,document_number',
            'email' => 'required|email|max:255',
        ]);

        Apprentice::create([
            'name' => $validated['name'],
            'last_name' => $validated['last_name'],
            'document_type' => $validated['document_type'],
            'document_number' => $validated['document_number'],
            'email' => $validated['email'],
            'cell_number' => '',
            'course_id' => $course->id,
        ]);

        return redirect()->route('courses.index')->with('success', 'Inscripción enviada correctamente.');
    }

    public function apprenticeStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document_number' => 'required|string|max:30|unique:apprentices,document_number',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:255',
            'cell_number' => 'nullable|string|max:30',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        Apprentice::create($validated);

        return redirect()->route('apprentices.index')->with('success', 'Aprendiz registrado correctamente.');
    }

    public function apprenticeDestroy(Apprentice $apprentice)
    {
        $apprentice->delete();

        return redirect()->route('apprentices.index')->with('success', 'Aprendiz eliminado correctamente.');
    }
}

trait AreaControllerActions
{
    public function areaIndex()
    {
        $areas = Area::all();

        return view('Area.index', compact('areas'));
    }

    public function areaCreate()
    {
        $areas = Area::all();

        return view('Area.create', compact('areas'));
    }

    public function areaStore(Request $request)
    {
        $area = Area::create($request->all());

        return redirect()->route('areas.index')->with('success', 'Área registrada correctamente.');
    }
}

trait AuthControllerActions
{
    public function authShowApprenticeAccess()
    {
        return view('Auth.apprentice-access');
    }

    public function authApprenticeAccess(Request $request)
    {
        $validated = $request->validate([
            'document_type' => 'required|in:CC,TI,PAS',
            'document_number' => 'required|string|max:30',
            'password' => 'required|string',
        ]);

        $user = User::where('document_type', $validated['document_type'])
            ->where('document_number', $validated['document_number'])
            ->first();

        if ($user && Hash::check($validated['password'], $user->password)) {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended(route('apprentices.index'));
        }

        $teacher = Teacher::where('document_number', $validated['document_number'])
            ->where('access_code', $validated['password'])
            ->first();

        if ($teacher) {
            $request->session()->regenerate();
            $request->session()->put('instructor_id', $teacher->id);

            return redirect()->intended(route('apprentices.index'));
        }

        $apprentice = Apprentice::where('document_type', $validated['document_type'])
            ->where('document_number', $validated['document_number'])
            ->first();

        if ($apprentice && $apprentice->password && Hash::check($validated['password'], $apprentice->password)) {
            $request->session()->regenerate();
            $request->session()->put('apprentice_id', $apprentice->id);

            return redirect()->intended(route('apprentices.index'));
        }

        return back()->withErrors(['document_number' => 'Los datos de acceso no coinciden con un registro autorizado.'])->withInput();
    }

    /**
     * Show login form
     */
    public function authShowLogin()
    {
        return view('Auth.login');
    }

    /**
     * Handle login
     */
    public function authLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $request->session()->forget('instructor_id');

            return redirect()->intended('/')->with('success', 'Bienvenido');
        }

        $teacher = Teacher::where('email', $credentials['email'])
            ->where('access_code', $credentials['password'])
            ->first();

        if ($teacher) {
            $request->session()->regenerate();
            $request->session()->put('instructor_id', $teacher->id);

            return redirect()->route('teachers.index')->with('success', 'Bienvenido, instructor.');
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Show register form
     */
    public function authShowRegister()
    {
        return view('Auth.register');
    }

    /**
     * Handle register
     */
    public function authRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return redirect('/')->with('success', 'Registro exitoso. Bienvenido');
    }

    /**
     * Handle logout
     */
    public function authLogout(Request $request)
    {
        Auth::logout();
        $request->session()->forget('instructor_id');

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Sesión cerrada correctamente.');
    }
}

trait ComputerControllerActions
{
    public function computerIndex()
    {
        // Carga asignaciones para mostrarlas sin consultas adicionales.
        $computer = Computer::with(['apprentice', 'teacher'])->orderBy('id')->get();

        return view('Computer.index', [
            'computer' => $computer,
            'apprentices' => Apprentice::orderBy('name')->get(),
            'teachers' => Teacher::orderBy('name')->get(),
        ]);
    }

    public function computerCreate()
    {
        return view('Computer.create');
    }

    public function computerStore(Request $request, ComputerImageStorage $images)
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
        $computer = null;
        try {
            DB::transaction(function () use (&$data, &$computer) {
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
                $computer = Computer::create($data);

                if (! empty($data['image_public_id'])) {
                    $computer->imagenes()->create([
                        'path' => $data['image_path'],
                        'disk' => 'cloudinary',
                        'public_id' => $data['image_public_id'],
                    ]);
                }
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

    public function computerUploadImage(Request $request, Computer $computer, ComputerImageStorage $images)
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
            DB::transaction(function () use ($computer, $asset) {
                $computer->save();
                $computer->imagenes()->delete();
                $computer->imagenes()->create([
                    'path' => $asset['url'],
                    'disk' => 'cloudinary',
                    'public_id' => $asset['public_id'],
                ]);
            });
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

        $this->computerDeletePreviousImage($images, $previousImage, $previousPublicId);

        return redirect()->route('computers.index')->with('success', 'Imagen del computador actualizada correctamente.');
    }

    public function computerDeleteImage(Computer $computer, ComputerImageStorage $images)
    {
        $this->computerDeletePreviousImage($images, $computer->image_path, $computer->image_public_id);

        $computer->update(['image_path' => null, 'image_public_id' => null]);
        $computer->imagenes()->delete();

        return redirect()->route('computers.index')->with('success', 'Imagen del computador eliminada correctamente.');
    }

    public function computerAssign(Request $request, Computer $computer)
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

    public function computerDestroy(Computer $computer, ComputerImageStorage $images)
    {
        $this->computerDeletePreviousImage($images, $computer->image_path, $computer->image_public_id);
        $computer->imagenes()->delete();
        $computer->delete();

        return redirect()->route('computers.index')->with('success', 'Computador eliminado correctamente.');
    }

    private function computerDeletePreviousImage(ComputerImageStorage $images, ?string $imagePath, ?string $publicId): void
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

trait ContactControllerActions
{
    /**
     * Display a listing of the resource.
     */
    public function contactIndex()
    {
        $contacts = Contact::all();

        return view('Contact.index', compact('contacts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function contactCreate()
    {
        return view('Contact.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function contactStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone_fixed' => 'nullable|string',
            'phone_mobile' => 'nullable|string',
            'message' => 'nullable|string',
        ]);

        Contact::create($validated);

        return redirect()->route('contacts.index')->with('success', 'Contacto creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function contactShow(Contact $contact)
    {
        return view('Contact.show', compact('contact'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function contactEdit(Contact $contact)
    {
        return view('Contact.edit', compact('contact'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function contactUpdate(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone_fixed' => 'nullable|string',
            'phone_mobile' => 'nullable|string',
            'message' => 'nullable|string',
            'status' => 'in:pending,resolved',
        ]);

        $contact->update($validated);

        return redirect()->route('contacts.index')->with('success', 'Contacto actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function contactDestroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Contacto eliminado correctamente.');
    }
}

trait CourseControllerActions
{
    public function courseIndex()
    {
        $courses = Course::all();

        return view('Course.index', compact('courses'));
    }

    public function courseInformation(Course $course)
    {
        $offerImages = [
            'ADS-001' => 'imagene/Imagenes SENA/57640 (1).jpeg',
            'SIS-002' => 'imagene/Imagenes SENA/images.jpg',
            'ADM-003' => 'imagene/Imagenes SENA/Imagen sena.jpg',
            'CON-004' => 'imagene/Imagenes SENA/images.png',
        ];
        $offerDescriptions = [
            'ADS-001' => 'Aprende a analizar necesidades, diseñar soluciones y desarrollar aplicaciones de software para transformar ideas en herramientas digitales.',
            'SIS-002' => 'Fortalece tus habilidades para instalar, mantener y brindar soporte a equipos, redes y sistemas tecnológicos.',
            'ADM-003' => 'Desarrolla competencias para organizar procesos, gestionar documentos y apoyar de forma eficiente las actividades administrativas.',
            'CON-004' => 'Conoce los fundamentos contables y financieros para registrar operaciones, analizar información y apoyar la toma de decisiones.',
            'ELE-005' => 'Aprende principios de electricidad residencial, instalaciones seguras y mantenimiento de sistemas eléctricos básicos.',
            'GAS-006' => 'Explora técnicas de cocina, preparación de alimentos y buenas prácticas para crear experiencias gastronómicas.',
            'DIS-007' => 'Desarrolla tu creatividad con herramientas de diseño gráfico, composición visual y producción de piezas multimedia.',
            'LOG-008' => 'Comprende la planificación, organización y control de procesos logísticos y cadenas de suministro.',
            'AUT-009' => 'Acércate al control de procesos industriales mediante fundamentos de automatización, sensores y sistemas de operación.',
        ];
        $offerHighlights = [
            'ADS-001' => ['Lógica de programación y bases de datos', 'Diseño y desarrollo de aplicaciones', 'Pruebas y solución de problemas de software'],
            'SIS-002' => ['Mantenimiento de equipos y sistemas operativos', 'Instalación y soporte de redes', 'Atención de incidentes tecnológicos'],
            'ADM-003' => ['Organización de documentos y procesos', 'Servicio al cliente y comunicación empresarial', 'Herramientas ofimáticas para la gestión'],
            'CON-004' => ['Registro de operaciones contables', 'Manejo de soportes y documentos financieros', 'Análisis básico de costos e información'],
            'ELE-005' => ['Circuitos e instalaciones residenciales', 'Uso seguro de herramientas eléctricas', 'Mantenimiento de sistemas básicos'],
            'GAS-006' => ['Técnicas de preparación y cocción', 'Higiene y manipulación de alimentos', 'Presentación y creatividad gastronómica'],
            'DIS-007' => ['Principios de composición y color', 'Herramientas de diseño digital', 'Creación de piezas para medios visuales'],
            'LOG-008' => ['Inventarios y control de almacenes', 'Compras, distribución y transporte', 'Planeación de cadenas de suministro'],
            'AUT-009' => ['Sensores y sistemas de control', 'Fundamentos de automatización industrial', 'Diagnóstico de procesos y equipos'],
        ];
        $offerImage = $offerImages[$course->course_number] ?? 'imagene/Imagenes SENA/57640 (1).jpeg';
        $offerDescription = $offerDescriptions[$course->course_number] ?? 'Conoce una oportunidad de formación práctica para fortalecer tus habilidades y prepararte para nuevos retos laborales.';
        $offerLearning = $offerHighlights[$course->course_number] ?? ['Formación práctica para el trabajo', 'Desarrollo de habilidades técnicas', 'Acompañamiento durante el proceso formativo'];

        return view('Course.information', compact('course', 'offerImage', 'offerDescription', 'offerLearning'));
    }

    public function courseCreate()
    {
        $areas = Area::all();
        $training_centers = Training_Center::all();
        $courses = Course::all();

        return view('Course.create', compact('areas', 'training_centers', 'courses'));
    }

    public function courseStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'schedule' => 'required|string|max:100',
            'duration_months' => 'required|integer|min:1|max:14',
            'course_number' => 'nullable|string|max:50',
            'day' => 'nullable|string|max:100',
            'area_id' => 'nullable|exists:areas,id',
            'training_center_id' => 'nullable|exists:training__centers,id',
        ]);

        $validated['course_number'] = $validated['course_number'] ?? strtoupper(substr($validated['name'], 0, 3));
        $validated['day'] = $validated['day'] ?? $validated['schedule'];
        Course::create($validated);

        return redirect()->route('courses.index')->with('success', 'Curso registrado correctamente.');
    }
}

trait GalleryControllerActions
{
    /**
     * Display a listing of the resource.
     */
    public function galleryIndex()
    {
        $galleries = Gallery::orderBy('order')->get();

        return view('Gallery.index', compact('galleries'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function galleryCreate()
    {
        return view('Gallery.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function galleryStore(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        if ($request->hasFile('image')) {
            $imageName = time().'.'.$request->image->extension();
            $request->image->move(public_path('images/gallery'), $imageName);
            $validated['image_path'] = 'images/gallery/'.$imageName;
        }

        Gallery::create($validated);

        return redirect()->route('galleries.index')->with('success', 'Imagen agregada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function galleryShow(Gallery $gallery)
    {
        return view('Gallery.show', compact('gallery'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function galleryEdit(Gallery $gallery)
    {
        return view('Gallery.edit', compact('gallery'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function galleryUpdate(Request $request, Gallery $gallery)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if (file_exists(public_path($gallery->image_path))) {
                unlink(public_path($gallery->image_path));
            }

            $imageName = time().'.'.$request->image->extension();
            $request->image->move(public_path('images/gallery'), $imageName);
            $validated['image_path'] = 'images/gallery/'.$imageName;
        }

        $gallery->update($validated);

        return redirect()->route('galleries.index')->with('success', 'Imagen actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function galleryDestroy(Gallery $gallery)
    {
        // Delete image file
        if (file_exists(public_path($gallery->image_path))) {
            unlink(public_path($gallery->image_path));
        }

        $gallery->delete();

        return redirect()->route('galleries.index')->with('success', 'Imagen eliminada correctamente.');
    }
}

trait TeacherControllerActions
{
    public function teacherIndex()
    {
        $teachers = Teacher::all();

        return view('Teacher.index', compact('teachers'));
    }

    public function teacherCreate()
    {
        $areas = Area::all();
        $trainingCenters = Training_Center::all();
        $teachers = Teacher::all();

        return view('Teacher.create', compact('areas', 'trainingCenters', 'teachers'));
    }

    public function teacherStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document_number' => 'required|string|max:30|unique:teachers,document_number',
            'email' => 'required|email|max:255',
            'access_code' => 'required|string|max:100|unique:teachers,access_code',
            'area_id' => 'nullable|exists:areas,id',
            'training_center_id' => 'nullable|exists:training__centers,id',
        ]);

        Teacher::create($validated);

        return redirect()->route('teachers.index')->with('success', 'Instructor registrado correctamente.');
    }
}

trait TrainingCenterControllerActions
{
    public function trainingCenterIndex(Request $request)
    {
        $search = trim((string) $request->query('location', ''));
        $locationOptions = Training_Center::query()
            ->get(['name', 'location'])
            ->flatMap(fn ($center) => [$center->name, $center->location])
            ->unique()
            ->values();
        $trainingCenters = Training_Center::with('courses')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->get();
        $trainingCentersByLocation = $trainingCenters->groupBy(function ($center) {
            $parts = collect(explode(',', $center->location))
                ->map(fn ($part) => trim($part))
                ->filter()
                ->values();

            if ($parts->count() >= 2) {
                return $parts->slice(-2)->implode(', ');
            }

            return $center->location;
        });

        return view('Training_Center.index', compact('trainingCenters', 'trainingCentersByLocation', 'search', 'locationOptions'));
    }

    public function trainingCenterCreate()
    {
        $trainingCenters = Training_Center::all();

        return view('Training_Center.create', compact('trainingCenters'));
    }

    public function trainingCenterStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        Training_Center::create($validated);

        return redirect()->route('training-centers.index')->with('success', 'Centro de formación registrado correctamente.');
    }
}

class ResourceController extends Controller
{
    use AdminControllerActions, ApprenticeControllerActions, AreaControllerActions, AuthControllerActions, ComputerControllerActions, ContactControllerActions, CourseControllerActions, GalleryControllerActions, TeacherControllerActions, TrainingCenterControllerActions;

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
