<nav class="navbar navbar-expand-lg fixed-top navbar-dark">
  <img class="navbar-brand" src="{{ asset('/images/2scale-logo.png') }}"/>
  <button class="navbar-toggler p-0 border-0" type="button" data-toggle="offcanvas">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="navbar-collapse offcanvas-collapse">
    <ul class="navbar-nav ml-auto">
      <li class="nav-item {{Route::is('home') ? 'active' : ''}}" id="authError" data={{session('status')}}>
        <a class="nav-link" href="/">Home</a>
        <?php Session::forget('status'); ?>
      </li>

      <li class="nav-item {{Route::is('impactreach') ? 'active' : '' }}">
          <a class="nav-link" href="/impactreach">Impact and Reach</a>
      </li>
      <li class="nav-item {{Route::is('countries') ? 'active' : '' }}">
        <a class="nav-link" href="/countries">Countries</a>
      </li>
      <li class="nav-item {{Route::is('partnership') ? 'active' : '' }}">
        <a class="nav-link" href="/partnership">Partnerships</a>
      </li>

      @if (Auth::check())
        <li class="nav-item {{Route::is('partners') ? 'active' : '' }}">
          <a class="nav-link" href="/partners">Partners</a>
        </li>
        <li class="nav-item {{Route::is('database') ? 'active' : '' }}">
            <a class="nav-link" href="/database">Database</a>
        </li>
        <li class="nav-item {{Route::is('survey') ? 'active' : '' }}">
            <a class="nav-link" href="/survey">Forms</a>
        </li>
        <li class="nav-item {{Route::is('report') ? 'active' : '' }}">
          <a class="nav-link" href="/report">Reports</a>
        </li>
        <li class="nav-item {{Route::is('uii-visuals') ? 'active' : '' }}">
          <a class="nav-link" href="/uii-visuals">UII-Lumen</a>
        </li>
        <li class="nav-item {{Route::is('support') ? 'active' : '' }}">
            <a class="nav-link" href="/support">Support</a>
        </li>
        <li class="nav-item {{Route::is('logout') ? 'active' : '' }}">
            <a class="nav-link" href="/logout">Logout</a>
        </li>
      @else
        <li class="nav-item {{Route::is('login') ? 'active' : '' }}">
            <a class="nav-link" href="/login">Login</a>
        </li>
      @endif
    </ul>
  </div>
</nav>
