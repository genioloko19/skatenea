import { useEffect, useRef, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import {
  Bell,
  ChevronDown,
  Contrast,
  LogOut,
  Menu,
  Moon,
  Settings2,
  Sparkles,
  Sun,
  UserRound,
} from 'lucide-react';
import { useAuth } from '../../auth/AuthContext';
import { useTheme } from '../../theme/ThemeProvider';
import { roleLabel } from '../../lib/format';
import { cn } from '../../lib/cn';

interface HeaderProps {
  onOpenControlPanel: () => void;
  onToggleSidebar: () => void;
  sidebarOpen: boolean;
}

function initials(name: string): string {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('');
}

function currentLabel(pathname: string): string {
  if (pathname === '/app' || pathname === '/app/') return 'Inicio';
  if (pathname.includes('/raffles/')) return 'Detalle de rifa';
  if (pathname.includes('/raffles')) return 'Rifas';
  if (pathname.includes('/tickets')) return 'Boletas';
  if (pathname.includes('/buyers')) return 'Compradores';
  if (pathname.includes('/users')) return 'Usuarios';
  if (pathname.includes('/approvals')) return 'Aprobaciones';
  if (pathname.includes('/audit')) return 'Auditoría';
  if (pathname.includes('/profile')) return 'Perfil';
  return 'SKATENEA';
}

export function Header({ onOpenControlPanel, onToggleSidebar, sidebarOpen }: HeaderProps) {
  const { user, logout } = useAuth();
  const { mode, cycleMode } = useTheme();
  const navigate = useNavigate();
  const location = useLocation();
  const [menuOpen, setMenuOpen] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    setMenuOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    if (!menuOpen) return;
    const onPointerDown = (event: PointerEvent) => {
      if (!menuRef.current?.contains(event.target as Node)) setMenuOpen(false);
    };
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setMenuOpen(false);
    };
    document.addEventListener('pointerdown', onPointerDown);
    document.addEventListener('keydown', onKeyDown);
    return () => {
      document.removeEventListener('pointerdown', onPointerDown);
      document.removeEventListener('keydown', onKeyDown);
    };
  }, [menuOpen]);

  const ModeIcon = mode === 'light' ? Sun : mode === 'dark' ? Moon : Contrast;
  const modeLabel = mode === 'light' ? 'Modo claro' : mode === 'dark' ? 'Modo oscuro' : 'Alto contraste';

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  return (
    <header className="app-header">
      <div className="app-header__left">
        <button
          type="button"
          className="icon-btn app-header__menu"
          onClick={onToggleSidebar}
          aria-label={sidebarOpen ? 'Cerrar menú' : 'Abrir menú'}
          aria-expanded={sidebarOpen}
        >
          <Menu size={20} />
        </button>
        <Link to="/app" className="brand brand--compact" aria-label="SKATENEA, ir al inicio">
          <span className="brand__mark" aria-hidden="true"><Sparkles size={22} /></span>
          <span className="brand__text">
            <strong>SKATENEA</strong>
            <small>Gestão de rifas</small>
          </span>
        </Link>
        <span className="header-divider" aria-hidden="true" />
        <span className="app-header__current">{currentLabel(location.pathname)}</span>
      </div>

      <div className="app-header__right">
        <button
          type="button"
          className="icon-btn"
          onClick={cycleMode}
          aria-label={`${modeLabel}. Cambiar modo`}
          title={modeLabel}
        >
          <ModeIcon size={19} />
        </button>
        <button type="button" className="icon-btn" aria-label="Notificaciones" title="Notificaciones">
          <Bell size={19} />
          <span className="notification-dot" aria-hidden="true" />
        </button>

        {user && (
          <div className="user-menu" ref={menuRef}>
            <button
              type="button"
              className={cn('user-menu__trigger', menuOpen && 'is-open')}
              onClick={() => setMenuOpen((open) => !open)}
              aria-haspopup="menu"
              aria-expanded={menuOpen}
            >
              <span className="avatar" aria-hidden="true">{initials(user.full_name)}</span>
              <span className="user-menu__meta">
                <strong>{user.full_name}</strong>
                <small>{roleLabel(user.role)}</small>
              </span>
              <ChevronDown size={16} className={cn('user-menu__chevron', menuOpen && 'is-open')} />
            </button>

            {menuOpen && (
              <div className="user-menu__dropdown glass" role="menu" aria-label="Menú de usuario">
                <div className="user-menu__header">
                  <span className="avatar avatar--lg" aria-hidden="true">{initials(user.full_name)}</span>
                  <div>
                    <strong>{user.full_name}</strong>
                    <span>{user.email}</span>
                  </div>
                </div>
                <button type="button" role="menuitem" onClick={onOpenControlPanel}>
                  <Settings2 size={17} /> Panel de control
                </button>
                <button type="button" role="menuitem" onClick={() => navigate('/app/profile')}>
                  <UserRound size={17} /> Perfil del usuario
                </button>
                <div className="user-menu__separator" role="separator" />
                <button type="button" role="menuitem" className="is-danger" onClick={handleLogout}>
                  <LogOut size={17} /> Cerrar sesión
                </button>
              </div>
            )}
          </div>
        )}
      </div>
    </header>
  );
}
