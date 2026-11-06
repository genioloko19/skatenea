import { useEffect, useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { Background } from './Background';
import { Header } from './Header';
import { Sidebar } from './Sidebar';
import { ControlPanelModal } from '../controlpanel/ControlPanelModal';
import { useAuth } from '../../auth/AuthContext';
import { useTheme } from '../../theme/ThemeProvider';

export function AppShell() {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [controlPanelOpen, setControlPanelOpen] = useState(false);
  const { preferences } = useAuth();
  const { applyPreferences } = useTheme();
  const location = useLocation();

  useEffect(() => {
    setSidebarOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    if (preferences) applyPreferences(preferences);
  }, [preferences, applyPreferences]);

  return (
    <div className="app-shell">
      <Background />
      <a className="skip-link" href="#main-content">Saltar al contenido</a>
      <Header
        onOpenControlPanel={() => setControlPanelOpen(true)}
        onToggleSidebar={() => setSidebarOpen((open) => !open)}
        sidebarOpen={sidebarOpen}
      />
      <Sidebar
        open={sidebarOpen}
        onClose={() => setSidebarOpen(false)}
        onOpenControlPanel={() => setControlPanelOpen(true)}
      />
      <main id="main-content" className="app-main" tabIndex={-1}>
        <div key={location.pathname} className="route-transition">
          <Outlet />
        </div>
      </main>
      <footer className="app-footer">
        <span>© {new Date().getFullYear()} SKATENEA. Transparencia y trazabilidad para rifas.</span>
        <span className="app-footer__links">
          <a href="http://127.0.0.1:8000/docs/api/openapi.yaml" target="_blank" rel="noreferrer">API</a>
          <a href="#privacidad">Privacidad</a>
          <a href="#soporte">Soporte</a>
        </span>
      </footer>
      <ControlPanelModal open={controlPanelOpen} onClose={() => setControlPanelOpen(false)} />
    </div>
  );
}
