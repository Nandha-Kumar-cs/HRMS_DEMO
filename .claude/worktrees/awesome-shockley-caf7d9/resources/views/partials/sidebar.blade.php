<nav id="sidebar">
    {{-- Brand --}}
    @php
        $sidebarLogoUrl = \Illuminate\Support\Facades\Cache::remember('app_favicon_url', 3600, function () {
            $entity = \App\Models\Entity::where('name', 'like', '%magneto%')
                ->orWhere('name', 'like', '%dynamics%')
                ->first();
            if ($entity && $entity->logo) {
                $path = public_path('storage/entities/' . $entity->logo);
                if (file_exists($path)) return asset('storage/entities/' . $entity->logo);
            }
            return null;
        });
    @endphp
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <div class="sidebar-brand-icon" style="{{ $sidebarLogoUrl ? 'background:transparent;padding:0;' : '' }}">
            @if($sidebarLogoUrl)
                <img src="{{ $sidebarLogoUrl }}" alt="Logo"
                     style="width:36px;height:36px;object-fit:contain;border-radius:6px;">
            @else
                &#9878;
            @endif
        </div>
        <div class="sidebar-brand-text"><span>HR</span>MS</div>
    </a>

    <ul class="sidebar-nav">

        {{-- Dashboard --}}
        <li>
            <a href="{{ route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
               title="Alt+D — Dashboard">
                <i class="fa fa-gauge nav-icon"></i>
                <span><sc class="sc">D</sc>ashboard</span>
            </a>
        </li>

        {{-- ── PEOPLE ──────────────────────────────────── --}}
        <div class="sidebar-section">People</div>

        @php $empActive = request()->routeIs('employees.*','offer-letters.*','confirmation-letters.*','increment-letters.*','employee-documents.*'); @endphp
        <li>
            <a class="nav-link {{ $empActive ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#empMenu" role="button"
               aria-expanded="{{ $empActive ? 'true' : 'false' }}"
               title="Alt+E — Employees">
                <i class="fa fa-users nav-icon"></i>
                <span><sc class="sc">E</sc>mployees</span>
                <i class="fa fa-chevron-down nav-chevron"></i>
            </a>
            <div class="collapse {{ $empActive ? 'show' : '' }}" id="empMenu">
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('employees.index') }}"
                           class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                            Employees List
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('offer-letters.index') }}"
                           class="nav-link {{ request()->routeIs('offer-letters.*') ? 'active' : '' }}">
                            Offer Letters
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('confirmation-letters.index') }}"
                           class="nav-link {{ request()->routeIs('confirmation-letters.*') ? 'active' : '' }}">
                            Confirmation Letters
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('increment-letters.index') }}"
                           class="nav-link {{ request()->routeIs('increment-letters.*') ? 'active' : '' }}">
                            Increment Letters
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('employee-documents.index') }}"
                           class="nav-link {{ request()->routeIs('employee-documents.*') ? 'active' : '' }}">
                            Documents
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        @php $assetActive = request()->routeIs('assets.*','no-due.*'); @endphp
        <li>
            <a class="nav-link {{ $assetActive ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#assetMenu" role="button"
               aria-expanded="{{ $assetActive ? 'true' : 'false' }}">
                <i class="fa fa-laptop nav-icon"></i>
                <span>Assets</span>
                <i class="fa fa-chevron-down nav-chevron"></i>
            </a>
            <div class="collapse {{ $assetActive ? 'show' : '' }}" id="assetMenu">
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('assets.index') }}"
                           class="nav-link {{ request()->routeIs('assets.*') ? 'active' : '' }}">
                            Company Assets
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('no-due.index') }}"
                           class="nav-link {{ request()->routeIs('no-due.*') ? 'active' : '' }}">
                            No Due Certificates
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- ── PAYROLL ──────────────────────────────────── --}}
        <div class="sidebar-section">Payroll</div>

        @php $payActive = request()->routeIs('salary-slips.*','loans.*','increments.*','promotions.*','employee-benefits.*','employee-bonuses.*'); @endphp
        <li>
            <a class="nav-link {{ $payActive ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#payMenu" role="button"
               aria-expanded="{{ $payActive ? 'true' : 'false' }}"
               title="Alt+P — Payroll">
                <i class="fa fa-money-bill-wave nav-icon"></i>
                <span><sc class="sc">P</sc>ayroll</span>
                <i class="fa fa-chevron-down nav-chevron"></i>
            </a>
            <div class="collapse {{ $payActive ? 'show' : '' }}" id="payMenu">
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('salary-slips.calculate') }}"
                           class="nav-link {{ request()->routeIs('salary-slips.calculate') ? 'active' : '' }}">
                            Salary Calculation
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('salary-slips.index') }}"
                           class="nav-link {{ request()->routeIs('salary-slips.index','salary-slips.show','salary-slips.create') ? 'active' : '' }}">
                            Salary Slips
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('loans.index') }}"
                           class="nav-link {{ request()->routeIs('loans.*') ? 'active' : '' }}">
                            Loans &amp; Advances
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('increments.index') }}"
                           class="nav-link {{ request()->routeIs('increments.*') ? 'active' : '' }}">
                            Increments
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('promotions.index') }}"
                           class="nav-link {{ request()->routeIs('promotions.*') ? 'active' : '' }}">
                            Promotions
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('employee-benefits.index') }}"
                           class="nav-link {{ request()->routeIs('employee-benefits.*') ? 'active' : '' }}">
                            Benefits
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('employee-bonuses.index') }}"
                           class="nav-link {{ request()->routeIs('employee-bonuses.*') ? 'active' : '' }}">
                            Bonuses &amp; Incentives
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- ── ATTENDANCE ──────────────────────────────── --}}
        @php $attActive = request()->routeIs('attendance.*','leave-requests.*','leave-status.*','leave-types.*','holidays.*','comp-offs.*','on-duties.*','leave-history.*'); @endphp
        <li>
            <a class="nav-link {{ $attActive ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#attMenu" role="button"
               aria-expanded="{{ $attActive ? 'true' : 'false' }}"
               title="Alt+A — Attendance">
                <i class="fa fa-calendar-check nav-icon"></i>
                <span><sc class="sc">A</sc>ttendance</span>
                <i class="fa fa-chevron-down nav-chevron"></i>
            </a>
            <div class="collapse {{ $attActive ? 'show' : '' }}" id="attMenu">
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('attendance.index') }}"
                           class="nav-link {{ request()->routeIs('attendance.index') ? 'active' : '' }}">
                            Mark Attendance
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('attendance.report') }}"
                           class="nav-link {{ request()->routeIs('attendance.report') ? 'active' : '' }}">
                            Attendance Report
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('leave-requests.index') }}"
                           class="nav-link {{ request()->routeIs('leave-requests.index','leave-requests.create','leave-requests.show','leave-requests.edit') ? 'active' : '' }}">
                            Leave Requests
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('leave-requests.history') }}"
                           class="nav-link {{ request()->routeIs('leave-requests.history') ? 'active' : '' }}">
                            Leave History
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('leave-status.index') }}"
                           class="nav-link {{ request()->routeIs('leave-status.*') ? 'active' : '' }}">
                            Leave Status
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('holidays.index') }}"
                           class="nav-link {{ request()->routeIs('holidays.*') ? 'active' : '' }}">
                            Holidays
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('comp-offs.index') }}"
                           class="nav-link {{ request()->routeIs('comp-offs.*') ? 'active' : '' }}">
                            Comp Offs
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('on-duties.index') }}"
                           class="nav-link {{ request()->routeIs('on-duties.*') ? 'active' : '' }}">
                            On Duty (OD)
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- ── REPORTS ──────────────────────────────────── --}}
        @php $repActive = request()->routeIs('reports.*'); @endphp
        <li>
            <a class="nav-link {{ $repActive ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#repMenu" role="button"
               aria-expanded="{{ $repActive ? 'true' : 'false' }}"
               title="Alt+R — Reports">
                <i class="fa fa-chart-bar nav-icon"></i>
                <span><sc class="sc">R</sc>eports</span>
                <i class="fa fa-chevron-down nav-chevron"></i>
            </a>
            <div class="collapse {{ $repActive ? 'show' : '' }}" id="repMenu">
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('reports.monthly-benefits') }}"
                           class="nav-link {{ request()->routeIs('reports.monthly-benefits') ? 'active' : '' }}">
                            Monthly Benefits
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.bonuses') }}"
                           class="nav-link {{ request()->routeIs('reports.bonuses') ? 'active' : '' }}">
                            Bonus Report
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.employee-history') }}"
                           class="nav-link {{ request()->routeIs('reports.employee-history') ? 'active' : '' }}">
                            Employee History
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.payroll-impact') }}"
                           class="nav-link {{ request()->routeIs('reports.payroll-impact') ? 'active' : '' }}">
                            Payroll Impact
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- ── LEARNING ──────────────────────────────────── --}}
        <div class="sidebar-section">Learning</div>

        <li>
            <a href="{{ route('training.index') }}"
               class="nav-link {{ request()->routeIs('training.*') ? 'active' : '' }}"
               title="Alt+T — Training">
                <i class="fa fa-graduation-cap nav-icon"></i>
                <span><sc class="sc">T</sc>raining</span>
            </a>
        </li>

        {{-- ── AUDIT (Admin only) ──────────────────────── --}}
        @if(auth()->user()?->role === 'admin')
        <div class="sidebar-section">Audit</div>
        <li>
            <a href="{{ route('activity-logs.index') }}"
               class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                <i class="fa fa-clock-rotate-left nav-icon"></i>
                <span>Activity Log</span>
            </a>
        </li>
        @endif

        {{-- ── SETTINGS — Admin/Manager only ──────────────── --}}
        @if(in_array(auth()->user()?->role, ['admin','manager']))
        <div class="sidebar-section">Settings</div>

        @php $settActive = request()->routeIs('salary-components.*','departments.*','designations.*','entities.*','leave-types.*','users.*','holiday-types.*','benefit-fund-types.*','roles.*','permissions.*','mobile-access.*','settings.ot.*','settings.grace.*'); @endphp
        <li>
            <a class="nav-link {{ $settActive ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#settMenu" role="button"
               aria-expanded="{{ $settActive ? 'true' : 'false' }}"
               title="Alt+S — Settings">
                <i class="fa fa-gear nav-icon"></i>
                <span><sc class="sc">S</sc>ettings</span>
                <i class="fa fa-chevron-down nav-chevron"></i>
            </a>
            <div class="collapse {{ $settActive ? 'show' : '' }}" id="settMenu">
                <ul class="sidebar-submenu">
                    @if(auth()->user()?->role === 'admin')
                    <li>
                        <a href="{{ route('users.index') }}"
                           class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            User Management
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('roles.index') }}"
                           class="nav-link {{ request()->routeIs('roles.*','permissions.*') ? 'active' : '' }}">
                            Roles &amp; Permissions
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('mobile-access.index') }}"
                           class="nav-link {{ request()->routeIs('mobile-access.*') ? 'active' : '' }}">
                            Mobile Access
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('entities.index') }}"
                           class="nav-link {{ request()->routeIs('entities.*') ? 'active' : '' }}">
                            Entities
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('salary-components.index') }}"
                           class="nav-link {{ request()->routeIs('salary-components.*') ? 'active' : '' }}">
                            Salary Components
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('departments.index') }}"
                           class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}">
                            Departments
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('designations.index') }}"
                           class="nav-link {{ request()->routeIs('designations.*') ? 'active' : '' }}">
                            Designations
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('leave-types.index') }}"
                           class="nav-link {{ request()->routeIs('leave-types.*') ? 'active' : '' }}">
                            Leave Types
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('holiday-types.index') }}"
                           class="nav-link {{ request()->routeIs('holiday-types.*') ? 'active' : '' }}">
                            Holiday Types
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('benefit-fund-types.index') }}"
                           class="nav-link {{ request()->routeIs('benefit-fund-types.*') ? 'active' : '' }}">
                            Benefit Fund Types
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('settings.ot.show') }}"
                           class="nav-link {{ request()->routeIs('settings.ot.*') ? 'active' : '' }}">
                            OT Settings
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('settings.grace.show') }}"
                           class="nav-link {{ request()->routeIs('settings.grace.*') ? 'active' : '' }}">
                            Grace Settings
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </li>
        @endif

    </ul>

    {{-- Bottom controls --}}
    <div style="padding:10px 12px;border-top:1px solid #1e293b;margin-top:auto;display:flex;align-items:center;gap:8px;">
        {{-- Collapse toggle --}}
        <button id="sidebarCollapseBtn" title="Collapse sidebar"
                style="background:none;border:1px solid #334155;color:#64748b;font-size:12px;cursor:pointer;padding:5px 8px;border-radius:5px;flex-shrink:0;line-height:1;transition:all .18s">
            <i class="fa fa-angles-left" id="sidebarCollapseIcon"></i>
        </button>
        {{-- Keyboard shortcut hint (hidden when collapsed) --}}
        <button onclick="window.dispatchEvent(new KeyboardEvent('keydown',{key:'?',altKey:true}))"
                class="sidebar-bottom-text"
                style="background:none;border:none;color:#334155;font-size:11px;cursor:pointer;flex:1;text-align:left;padding:0;white-space:nowrap;overflow:hidden">
            <i class="fa fa-keyboard me-1"></i> <sc class="sc">?</sc> Keyboard shortcuts
        </button>
    </div>
</nav>
