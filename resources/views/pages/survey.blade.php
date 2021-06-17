@extends ('template')

@section ('content')

<div class="bg-white shadow-sm selector-bar">
@if(!isset($saved_survey))
	<nav class="nav">
        <select data-url="{{ $surveys['url'] }}" id="select-survey" class="selectpicker" data-style="" data-live-search="true">
		  <option>Select Survey</option>
            @foreach ($surveys['forms'] as $form)
              <optgroup label="{{ $form['name'] }}">
                @foreach($form["list"] as $list)
                <option
                    data-tokens="{{ $list['name'] }}"
                    data-id="{{ $list['form_id'] }}"
                    value="{{ $list['form_id'] }}">
                    {{ $list['name'] }}
                </option>
                @endforeach
              </optgroup>
            @endforeach
		</select>
    </nav>
@endif
</div>

@if(isset($saved_survey))
<iframe id="akvo-flow-web" class="has-filter" src="{{ $saved_survey }}" frameborder=0 width="100%" ></iframe>
@else
<iframe id="akvo-flow-web" class="has-filter" src="/frame/blank" frameborder=0 width="100%"></iframe>
@endif

@endsection
