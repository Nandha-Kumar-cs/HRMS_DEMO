<nav id="topbar" class="d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
        <button id="sidebarToggle" class="btn btn-sm btn-outline-secondary d-md-none">
            <i class="fa fa-bars"></i>
        </button>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Home</a></li>
                @yield('breadcrumb')
            </ol>
        </nav>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="text-muted small">
            <i class="fa fa-calendar-days me-1"></i>{{ now()->format('d M Y') }}
        </div>
        <div class="dropdown">
            <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white"
                     style="width:32px;height:32px;font-size:.8rem">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">{{ auth()->user()->email }}</h6></li>
                <li>
                    <span class="dropdown-item-text small">
                        @php $roleColors = ['admin' => 'danger', 'manager' => 'primary', 'staff' => 'secondary']; @endphp
                        <span class="badge bg-{{ $roleColors[auth()->user()->role] ?? 'secondary' }}">
                            {{ ucfirst(auth()->user()->role) }}
                        </span>
                    </span>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fa fa-right-from-bracket me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
