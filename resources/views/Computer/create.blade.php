@extends('layouts.app')

@section('title', 'Registrar Computador')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('computers.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                @csrf

                <div class="col-md-6">
                    <label for="serial" class="form-label">ID / Serial del equipo</label>
                    <input type="text" id="serial" name="serial" class="form-control" value="{{ old('serial') }}" inputmode="numeric" pattern="[0-9]{5,20}" minlength="5" maxlength="20" required>
                    <div class="form-text">Ingresa el serial numérico del equipo (mínimo 5 dígitos).</div>
                    @error('serial')<div class="text-danger">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="brand" class="form-label">Marca</label>
                    <input type="text" id="brand" name="brand" class="form-control" value="{{ old('brand') }}" required>
                    @error('brand')<div class="text-danger">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="image" class="form-label">Foto del computador</label>
                    <input type="file" id="image" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    <div class="form-text">Opcional. JPG, PNG o WebP; máximo 5 MB.</div>
                    @error('image')<div class="text-danger">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Registrar</button>
                    <a href="{{ route('computers.index') }}" class="btn btn-outline-primary ms-2">Ver registros</a>
                </div>
            </form>
        </div>
    </div>
@endsection