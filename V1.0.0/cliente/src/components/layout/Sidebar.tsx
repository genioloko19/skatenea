import { NavLink } from 'react-router-dom';
import {
  BadgeCheck,
  BarChart3,
  ClipboardCheck,
  Home,
  KeyRound,
  ScrollText,
  Settings2,
  ShieldCheck,
  Ticket,
  UserRound,
  Users,
  Wallet,
  X,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { useAuth } from '../../auth/AuthContext';
import { cn } from '../../lib/cn';

interface SidebarProps {
  open: boolean;
  onClose: () => void;
  onOpenControlPanel: () => void;
}

interface NavItem {
  to: string;
  label: string;
  icon: ReactNode;
  permission?: string;
  end?: boolean;
}

export function Sidebar({ open, onClose, onOpenControlPanel }: SidebarProps) {
  const { user, hasPermission } = useAuth();

  const items: NavItem[] = [
    { to: '/app', label: 'Inicio', icon: <Home size={19} />, end: true },
    { to: '/app/raffles', label: 'Rifas', icon: <Ticket size={19} />, permission: 'raffles.read' },
    { to: '/app/tickets', label: 'Boletas', icon: <Wallet size={19} />, permission: 'tickets.read' },
    { to: '/app/buyers', label: 'Compradores', icon: <Users size={19} />, permission: 'buyers.read' },
    { to: '/app/approvals', label: 'Aprobaciones', icon: <ClipboardCheck size={19} />, permission: 'raffles.approve' },
    { to: '/app/users', label: 'Usuarios', icon: <ShieldCheck size={19} />, permission: 'users.read' },
    { to: '/app/audit', label: 'Auditoría', icon: <ScrollText size={19} />, permission: 'audit.read' },
    { to: '/app/profile', label: 'Mi perfil', icon: <UserRound size={19} />, permission: 'profile.update' },
  ];

  const visible = items.filter((item) => !item.permission || hasPermission(item.permission));

  return (
    <>
      {open && <button type="button" className="sidebar-scrim" aria-label="Cerrar menú" onClick={onClose} />}
      <aside id="sidebar" className={cn('app-sidebar glass', open && 'is-open')} aria-label="Navegación principal">
        <div className="app-sidebar__top">
          <div className="app-sidebar__badge">
            <BadgeCheck size={16} />
            <span>v1.0.0</span>
          </div>
          <button type="button" className="icon-btn app-sidebar__close" onClick={onClose} aria-label="Cerrar menú">
            <X size={18} />
          </button>
        </div>

        <nav className="app-nav">
          <span className="app-nav__label">Espacio de trabajo</span>
          {visible.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              className={({ isActive }) => cn('app-nav__link', isActive && 'is-active')}
              onClick={onClose}
            >
              {item.icon}
              <span>{item.label}</span>
            </NavLink>
          ))}
        </nav>

        <nav className="app-nav app-nav--secondary" aria-label="Accesos rápidos">
          <span className="app-nav__label">Sistema</span>
          <button type="button" className="app-nav__link" onClick={onOpenControlPanel}>
            <Settings2 size={19} />
            <span>Panel de control</span>
          </button>
          <NavLink to="/app/raffles" className="app-nav__link" onClick={onClose}>
            <BarChart3 size={19} />
            <span>Reportes</span>
          </NavLink>
          {hasPermission('api_keys.read') && (
            <button type="button" className="app-nav__link" onClick={onOpenControlPanel}>
              <KeyRound size={19} />
              <span>API e integraciones</span>
            </button>
          )}
        </nav>

        <div className="app-sidebar__footer">
          <div className="app-sidebar__status">
            <span className={cn('status-dot', user?.status === 'active' && 'is-online')} aria-hidden="true" />
            <span>{user ? 'Sesión activa' : 'Sin sesión'}</span>
          </div>
          <small>SKATENEA SGR · V1.0.0 alfa</small>
        </div>
      </aside>
    </>
  );
}
