<aside class="sidebar-navegacion">
    <div class="image-container">
        <a href="{{ route('dashboard') }}" title="Volver al panel principal">
            <img src="{{ asset('Imagenes/logo.png') }}" alt="Logo Valle de San Remo" class="img-logo">
        </a>
    </div>
    <nav>
        <ul>
            <li>
                <a href="{{ route('dashboard') }}" class="item-menu {{ request()->routeIs('dashboard*') ? 'activo' : '' }}">
                    <i class="fas fa-home"></i> Inicio
                </a>
            </li>
            @if (auth()->user()->rol === 'vigilante')
                <li>
                    <a href="{{ route('registro-paquetes') }}" class="item-menu {{ request()->routeIs('registro-paquetes') ? 'activo' : '' }}">
                        <i class="fas fa-plus-circle"></i> Registrar Paquete
                    </a>
                </li>
                <li>
                    <a href="{{ route('consultar') }}" class="item-menu {{ request()->routeIs('consultar') ? 'activo' : '' }}">
                        <i class="fas fa-search"></i> Consultar Entregas
                    </a>
                </li>
            @elseif (auth()->user()->rol === 'admin')
                <li>
                    <a href="{{ route('residentes') }}" class="item-menu {{ request()->routeIs('residentes') ? 'activo' : '' }}">
                        <i class="fas fa-user-friends"></i> Residentes
                    </a>
                </li>
                <li><a href="{{ route('guardas') }}" class="item-menu {{ request()->routeIs('guardas*') ? 'activo' : '' }}"><i class="fas fa-shield-alt"></i> Vigilantes</a></li>
                <li><a href="{{ route('apartamentos') }}" class="item-menu {{ request()->routeIs('apartamentos*') ? 'activo' : '' }}"><i class="fas fa-building"></i> Apartamentos</a></li>
                <li><a href="{{ route('reportes') }}" class="item-menu {{ request()->routeIs('reportes*') ? 'activo' : '' }}"><i class="fas fa-chart-bar"></i> Reportes</a></li>
            @else
                <li>
                    <a href="{{ route('mis-paquetes') }}" class="item-menu {{ request()->routeIs('mis-paquetes') ? 'activo' : '' }}">
                        <i class="fas fa-boxes-stacked"></i> Mis paquetes
                    </a>
                </li>
            @endif
            <li>
                <a href="{{ route('historial') }}" class="item-menu {{ request()->routeIs('historial') ? 'activo' : '' }}">
                    <i class="fas fa-history"></i> Historial
                </a>
            </li>
            @if (auth()->user()->rol === 'admin')
                <li>
                    <a href="{{ route('configuracion') }}" class="item-menu {{ request()->routeIs('configuracion*') ? 'activo' : '' }}">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                </li>
            @endif
            <li>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button type="submit" class="item-menu"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</button>
                </form>
            </li>
        </ul>
    </nav>
</aside>
