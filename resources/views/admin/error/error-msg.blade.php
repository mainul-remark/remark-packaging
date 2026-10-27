@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show position-relative" role="alert">
        @if ($errors->count() === 1)
            {{ $errors->first() }}
        @else
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <button type="button" class="close position-absolute" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif
