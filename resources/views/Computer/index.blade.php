@extends('layouts.app')

@section('title', 'Computadores registrados')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Computadores registrados</h1>
        <a href="{{ route('computers.create') }}" class="btn btn-primary">Registrar nuevo</a>
    </div>

    <div class="card">
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if ($computer->isNotEmpty())
                <div class="table-responsive computer-table-wrapper">
                    <table class="table table-hover align-middle computer-table">
                        <colgroup>
                            <col class="computer-column-id">
                            <col class="computer-column-brand">
                            <col class="computer-column-image">
                            <col class="computer-column-assignment">
                            <col class="computer-column-actions">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Marca</th>
                                <th>Foto</th>
                                <th>Asignado a</th>
                                <th class="text-center">Funciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($computer as $item)
                                @php
                                    $selectedAssignment = $item->teacher_id
                                        ? 'teacher:' . $item->teacher_id
                                        : ($item->apprentice ? 'apprentice:' . $item->apprentice->id : '');
                                @endphp
                                <tr>
                                    <td>
                                        <input
                                            type="text"
                                            name="serial"
                                            class="form-control form-control-sm computer-serial-input"
                                            value="{{ $item->serial }}"
                                            aria-label="ID o serial del computador {{ $item->serial }}"
                                            inputmode="numeric"
                                            pattern="[0-9]{5,20}"
                                            minlength="5"
                                            maxlength="20"
                                            form="computer-assignment-{{ $item->id }}"
                                            required>
                                    </td>
                                    <td>{{ $item->brand }}</td>
                                    <td class="computer-image-cell">
                                        <div class="computer-image-content">
                                            @if ($item->image_path)
                                                <img src="{{ $item->image_url }}" alt="Foto del equipo {{ $item->serial }}" class="img-thumbnail computer-image-preview">
                                            @else
                                                <div class="computer-image-placeholder">Sin imagen</div>
                                            @endif
                                            <form action="{{ route('computers.image.upload', $item) }}" method="POST" enctype="multipart/form-data" class="computer-image-form">
                                                @csrf
                                                <input type="file" name="image" class="form-control form-control-sm computer-image-input" accept=".jpg,.jpeg,.png,.webp" aria-label="Subir foto del equipo {{ $item->serial }}" required>
                                                <button class="btn btn-sm btn-outline-primary computer-image-upload" type="submit">Subir</button>
                                            </form>
                                        </div>
                                        @if ($item->image_path)
                                            <form action="{{ route('computers.image.destroy', $item) }}" method="POST" class="computer-image-delete" onsubmit="return confirm('¿Eliminar la imagen de este computador?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-link text-danger" type="submit">Quitar imagen</button>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="computer-assignment-cell">
                                        <form id="computer-assignment-{{ $item->id }}" action="{{ route('computers.assignment', $item) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <select name="assigned_to" class="form-select form-select-sm" aria-label="Asignar computador {{ $item->serial }}">
                                                <option value="" @selected($selectedAssignment === '')>Sin asignar</option>
                                                <optgroup label="Aprendices">
                                                    @foreach ($apprentices as $apprentice)
                                                        <option value="apprentice:{{ $apprentice->id }}" @selected($selectedAssignment === 'apprentice:' . $apprentice->id)>
                                                            {{ $apprentice->name }} {{ $apprentice->last_name }} — {{ $apprentice->document_number }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                                <optgroup label="Instructores">
                                                    @foreach ($teachers as $teacher)
                                                        <option value="teacher:{{ $teacher->id }}" @selected($selectedAssignment === 'teacher:' . $teacher->id)>
                                                            {{ $teacher->name }} — {{ $teacher->document_number }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="computer-actions">
                                            <button class="btn btn-sm btn-primary" type="submit" form="computer-assignment-{{ $item->id }}">Guardar</button>
                                            <form action="{{ route('computers.destroy', $item) }}" method="POST" onsubmit="return confirm('¿Eliminar este computador?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No hay computadores registrados.</p>
            @endif
        </div>
    </div>
@endsection
