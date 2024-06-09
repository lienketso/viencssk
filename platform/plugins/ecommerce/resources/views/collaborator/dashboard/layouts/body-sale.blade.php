<div class="screen-overlay"></div>

<main class="main-wrap">
    <header class="main-header navbar">
        <div class="col-search">
        </div>
        <div class="col-nav">
            <button class="btn btn-icon btn-mobile me-auto" data-trigger="#offcanvas_aside">
                <i class="material-icons md-apps"></i>
            </button>
            <ul class="nav">
                <li class="nav-item">
                    <a href="#" class="requestfullscreen nav-link btn-icon"><i class="material-icons md-cast"></i></a>
                </li>
                <li class="dropdown nav-item">
                    <a class="dropdown-toggle" data-bs-toggle="dropdown" href="#" id="dropdownAccount" aria-expanded="false">
                        <img class="img-xs rounded-circle" src="{{ auth('customer')->user()->avatar_url }}" alt="{{ auth('customer')->user()->name }}" />
                        <span class="d-none d-sm-inline-block vendor-name">{{ auth('customer')->user()->name }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end float-end" aria-labelledby="dropdownAccount">
                        <a class="dropdown-item" href="{{ route('customer.overview') }}"><i class="material-icons md-perm_identity"></i>Tài khoản</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="{{ route('customer.logout') }}"><i class="material-icons md-exit_to_app"></i>Đăng xuất</a>
                    </div>
                </li>
            </ul>
        </div>
    </header>
    <section class="content-main">
        <div id="main">
            @yield('content')
        </div>
    </section>

    <footer class="font-xs px-4">
        <div class="row pb-15 pt-15">
            <div class="col-sm-6">
                {{ theme_option('copyright') }}
            </div>
        </div>
    </footer>
</main>
