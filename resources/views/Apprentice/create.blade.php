@extends('layouts.app')

@section('title', 'Registrar Aprendiz')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('apprentices.store') }}" method="POST" class="row g-3">
                <h1 class="h4">Registrar Aprendiz</h1>
                @csrf

                <div class="col-md-6">
                    <label for="name" class="form-label">Nombre</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label for="document_number" class="form-label">Número de cédula</label>
                    <input type="text" id="document_number" name="document_number" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Correo</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" minlength="8" required>
                </div>

                <div class="col-md-6">
                    <label for="cell_number" class="form-label">Número celular</label>
                    <input type="text" id="cell_number" name="cell_number" class="form-control">
                </div>

                <div class="col-md-6">
                    <label for="course_id" class="form-label">Curso</label>
                    <select id="course_id" name="course_id" class="form-select" required>
                        <option value="">Seleccionar curso</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->course_number }} - {{ $course->day }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('apprentices.index') }}" class="btn btn-outline-primary ms-2">Ver registros</a>
                    <a href="{{ url('/') }}" class="btn btn-secondary ms-2">Volver</a>
                </div>
            </form>
        </div>
    </div>

    <h2 class="mt-4">Aprendices registrados</h2>
    <ul class="list-group">
        @foreach ($apprentices as $apprentice)
            <li class="list-group-item">ID: {{ $apprentice->id }} - Nombre: {{ $apprentice->name }} - Email: {{ $apprentice->email }} - Celular: {{ $apprentice->cell_number }} - Curso_id: {{ $apprentice->course_id }} - Computador_id: {{ $apprentice->computer_id }}</li>
        @endforeach
    </ul>
@endsection
