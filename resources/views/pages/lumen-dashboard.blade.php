@extends ('template')

@section ('content')

<div class="bg-white shadow-sm flex-display selector-bar select-dropdown-wrapper justify-content-center">
    <nav class="nav nav-md-8 nav-selector">
        <select id="lumen-uii-dropdown" class="selectpicker" data-style="" data-live-search="true">
            <option value="0" selected>Select UII</option>
            @foreach($config as $item)
            <option
                data-tokens="{{ $item['label'] }}"
                data-id="{{ $item['label'] }}"
                value="{{ $item['label'] }}">
                {{ $item['label'] }}
            </option>
            @endforeach
        </select>
    </nav>
</div>

<div class="instruction-text-wrapper">(Select UII to show visual)</div>

<iframe
    id="lumen-dashboard-data-frame"
    class="lumen-iframe"
    src="/frame/blank/lumen-dashboard"
    frameborder=0
    width="100%"
></iframe>

@endsection
