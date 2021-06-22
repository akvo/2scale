<!DOCTYPE html>
<html>
<head>
    <!-- Required meta tags-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="2SCALE website">
    <meta name="author" content="Akvo">
    <meta name="path" content="{{env('APP_URL')}}">
    {{-- <meta name="country" content="{{$country_id}}">
    <meta name="partnership" content="{{$partnership_id}}">
    <meta name="start-date" content="{{$start}}">
    <meta name="end-date" content="{{$end}}"> --}}
    <meta name="keywords" content="2SCALE">
    <title>2SCALE</title>
    <link href="https://fonts.googleapis.com/css?family=Montserrat&display=swap" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
	<!-- Material Design Bootstrap -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/mdbootstrap/4.8.11/css/mdb.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.10.20/b-1.6.1/b-colvis-1.6.1/b-flash-1.6.1/b-html5-1.6.1/b-print-1.6.1/fh-3.1.6/kt-2.5.1/r-2.2.3/datatables.min.css"/>
    <link rel="stylesheet" href="{{mix('css/print.css')}}">
    <link href="{{ mix('/css/frame.css') }}" rel="stylesheet" media="all">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.1/axios.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/4.6.0/echarts-en.min.js" type="text/javascript"></script>
</head>
<style>
html,
body{
	margin:auto;
}
.loader-spinner {
    position:absolute;
    top: 45%;
}
.view.view-cascade {
    border-radius: 3px;
    margin: 5px;
}

.form-group.required .control-label:after {
    content:"*";
    color:red;
    margin-right: 5px;
}
</style>
<body>
    <!-- Jumbotron -->
    <div class="text-white text-center py-2 px-4">
        <div id="jumbotron" class="row"></div>
    </div>

    <!-- Jumbotron -->
    <div class="cover-container d-flex h-100 p-3 mx-auto flex-column">
        <main role="main" class="inner cover"></main>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="myModalAuth" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myModalAuthTitle">Title</h5>
                    <button id="myModalBtnClose" type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="myModalAuthBody">
                    ...
                </div>
            </div>
        </div>
    </div>
@include('components.footer')
    <!-- Global Dependencies -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <!-- Bootstrap tooltips -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.4/umd/popper.min.js"></script>
    <!-- Bootstrap core JavaScript -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <!-- MDB core JavaScript -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdbootstrap/4.8.11/js/mdb.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.10.20/b-1.6.1/b-colvis-1.6.1/b-flash-1.6.1/b-html5-1.6.1/b-print-1.6.1/fh-3.1.6/kt-2.5.1/r-2.2.3/datatables.min.js"></script>
    <!-- Bootstrap Select -->
    <script src="{{ mix('/js/report.js') }}"></script>
</body>
</html>
