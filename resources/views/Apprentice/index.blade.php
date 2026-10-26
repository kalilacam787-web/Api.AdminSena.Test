@extends('layouts.app')

@section('title', 'Aprendices registrados')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0">Aprendices registrados</h1>
        <a href="{{ route('apprentices.create') }}" class="btn btn-primary">Registrar nuevo</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            @if ($apprentices->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Datos personales</th>
                                <th>Email</th>
                                <th>Documento</th>
                                <th>Celular</th>
                                <th>Programa</th>
                                <th>Centro de formación</th>
                                <th>Computador</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($apprentices as $apprentice)
                                <tr>
                                    <td>{{ $apprentice->name }} {{ $apprentice->last_name }}</td>
                                    <td>{{ $apprentice->email }}</td>
                                    <td>{{ $apprentice->document_type ?? 'Documento' }}: {{ $apprentice->document_number }}</td>
                                    <td>{{ $apprentice->cell_number }}</td>
                                    <td>
                                        @if ($apprentice->course)
                                            {{ $apprentice->course->name ?? $apprentice->course->course_number }}
                                        @else
                                            <span class="text-muted">Sin curso</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($apprentice->course?->trainingCenter)
                                            {{ $apprentice->course->trainingCenter->name }}
                                        @else
                                            <span class="text-muted">Sin centro</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($apprentice->computer)
                                            {{ $apprentice->computer->brand }} #{{ $apprentice->computer->number }}
                                        @else
                                            <span class="text-muted">Sin computador</span>
                                        @endif
                                    </td>
                                    <td><form action="{{ route('apprentices.destroy', $apprentice) }}" method="POST" onsubmit="return confirm('¿Eliminar este aprendiz?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button></form></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">No hay aprendices registrados.</p>
            @endif
        </div>
    </div>
@endsection
