@extends ('template')

@section ('content')

<div class="bg-white shadow-sm flex-display selector-bar justify-content-center select-dropdown-wrapper">
@if(!isset($saved_survey))
	<nav class="nav nav-md-4 nav-selector">
        <select id="survey-parent" class="selectpicker" data-style="" data-live-search="true">
		  <option value="select-init">Select Category</option>
            @foreach($surveys['forms'] as $index => $form)
            <option
                value="parent{{ $index }}">
                {{ $form['name'] }}
            </option>
            @endforeach
		</select>
	</nav>
	<nav class="nav nav-md-4 nav-selector">
        <select data-url="{{ $surveys['url'] }}" id="select-survey" data-style="" class="selectpicker" data-live-search="true">
            <option id="survey-init" value="survey-init">Select Questionnaire</option>
            @php
                $childrens = collect();
            @endphp
            @foreach($surveys['forms'] as $index => $form)
                @foreach($form["list"] as $list)
                    @php
                        $list['parent'] = $index;
                        $childrens->push($list);
                    @endphp
                @endforeach
            @endforeach

            @foreach($childrens as $list)
                <option
                    style="display:none"
                    class="parent{{ $list['parent'] }}"
                    data-tokens="{{ $list['name'] }}"
                    data-id="{{ $list['form_id'] }}"
                    value="{{ $list['form_id'] }}">
                    {{ $list['name'] }}
                </option>
            @endforeach
		</select>
	</nav>
@endif
</div>

<div class="instruction-text-wrapper">(Select the survey form and proceed with data capturing)</div>

@if(isset($saved_survey))
<iframe id="akvo-flow-web" class="has-filter" src="{{ $saved_survey }}" frameborder=0 width="100%" ></iframe>
@else
<iframe id="akvo-flow-web" class="has-filter" src="/frame/blank/survey" frameborder=0 width="100%"></iframe>
@endif

@endsection
