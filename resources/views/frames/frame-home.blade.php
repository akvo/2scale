<!DOCTYPE html>
<html>
<head>
  <!-- Required meta tags-->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="2SCALE website">
  <meta name="author" content="Akvo">
  <meta name="path" content="{{env('APP_URL')}}">
  <meta name="keywords" content="2SCALE">
  <title>2SCALE</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
  <!-- Material Design Bootstrap -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/mdbootstrap/4.8.11/css/mdb.min.css" rel="stylesheet">
  <link href="{{ asset('vendor/font-awesome-4.7/css/font-awesome.min.css') }}" rel="stylesheet" media="all">
  <link href="{{ mix('/css/frame.css') }}" rel="stylesheet" media="all">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/4.6.0/echarts-en.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.1/axios.min.js" type="text/javascript"></script>

  <style>
    html,
    body{
        margin:auto;
    }
    .loader-spinner {
        position:absolute;
        top: 45%;
    }
    #maps {
      height: 670px;
    }

    /* custom only for home page */
    .row {
      margin-left: 0px !important;
      margin-right: 0px !important;
    }
    </style>
</head>
<body class="text-center">
  {{-- Static carousel --}}
  <div id="home-carousel" class="carousel slide home-carousel-container" data-ride="carousel">
    <div class="carousel-inner">
      @for ($ind = 1; $ind <= 8; $ind++)
        <div class="carousel-item home-carousel-item {{$ind === 1 ? 'active' : ''}}">
          <img class="d-block w-100 home-carousel-img" src="{{ URL::to('/') }}/images/home-slider/slider-{{$ind}}.jpg" alt="slide-{{$ind}}">
          <div class="carousel-caption d-none d-md-block">
            <h2>Track the impact of Africa's largest Inclusive agribusiness incubator</h2>
          </div>
        </div>
      @endfor
    </div>
    <a class="carousel-control-prev" href="#home-carousel" role="button" data-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-next" href="#home-carousel" role="button" data-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="sr-only">Next</span>
    </a>
  </div>
  {{-- End of Static carousel --}}

  <div class="cover-container d-flex h-100 mx-auto flex-column">
    <main role="main" id="main" class="inner cover"></main>
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
  <!-- Bootstrap Select -->
  <script src="{{ mix('/js/home.js') }}"></script>
</body>
</html>
