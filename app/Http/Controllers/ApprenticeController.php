<?php

namespace App\Http\Controllers;

use App\Models\Apprentice;
use App\Models\Course;
use Illuminate\Http\Request;

class ApprenticeController extends Controller
{
    public function index()
    {
        $apprentices = Apprentice::with(['course.trainingCenter', 'computer'])->get();

        return view('Apprentice.index', compact('apprentices'));
    }

    public function create()
    {
        $courses = Course::all();
        $apprentices = Apprentice::all();

        return view('Apprentice.create', compact('courses', 'apprentices'));
    }

    public function enrollmentForm(Course $course)
    {
        return view('Apprentice.enroll', compact('course'));
    }

    public function enroll(Request $request, Course $course)
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

    public function store(Request $request)
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
    
    public function destroy(Apprentice $apprentice)
    {
        $apprentice->delete();
        
        return redirect()->route('apprentices.index')->with('success', 'Aprendiz eliminado correctamente.');
    }
}

