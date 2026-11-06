import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  ArrowRight,
  CalendarDays,
  Contrast,
  Moon,
  ShieldCheck,
  Sparkles,
  Sun,
  Ticket,
  Trophy,
  Users,
} from 'lucide-react';
import { api } from '../api/client';
import type { Raffle } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { useTheme } from '../theme/ThemeProvider';
import { Background } from '../components/layout/Background';
import { Badge } from '../components/ui/Badge';
import { Card, CardBody } from '../components/ui/Card';
import { EmptyState, PageLoader } from '../components/ui/Feedback';
import { currency, formatDate, percent } from '../lib/format';

export function PublicLandingPage() {
  const { isAuthenticated, user } = useAuth();
  const { mode, cycleMode } = useTheme();
  const [raffles, setRaffles] = useState<Raffle[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.raffles({ status: 'active', limit: 6 })
      .then((payload) => setRaffles(payload.raffles))
      .catch(() => setRaffles([]))
      .finally(() => setLoading(false));
  }, []);

  const ModeIcon = mode === 'light' ? Sun : mode === 'dark' ? Moon : Contrast;
  const totalTickets = raffles.reduce((sum, raffle) => sum + raffle.stats.paid, 0);

  return (
    <div className="public-page">
      <Background />
      <header className="public-header">
        <Link to="/" className="brand" aria-label="SKATENEA inicio">
          <span className="brand__mark" aria-hidden="true"><Sparkles size={24} /></span>
          <span className="brand__text">
            <strong>SKATENEA</strong>
            <small>Transparencia en cada boleta</small>
          </span>
        </Link>
        <nav className="public-nav" aria-label="Navegación pública">
          <a href="#rifas">Rifas</a>
          <a href="#como-funciona">Cómo funciona</a>
          <a href="#confianza">Confianza</a>
        </nav>
        <div className="public-header__actions">
          <button type="button" className="icon-btn" onClick={cycleMode} aria-label="Cambiar modo de color">
            <ModeIcon size={19} />
          </button>
          {isAuthenticated ? (
            <Link to="/app" className="btn btn--primary btn--md">Ir al panel <ArrowRight size={16} /></Link>
          ) : (
            <>
              <Link to="/login" className="btn btn--ghost btn--md">Iniciar sesión</Link>
              <Link to="/register" className="btn btn--primary btn--md">Crear cuenta</Link>
            </>
          )}
        </div>
      </header>

      <main className="public-main">
        <section className="hero">
          <Badge tone="primary"><Sparkles size={14} /> SKATENEA V1.0.0 · alfa</Badge>
          <h1>Rifas claras,<br /><span className="gradient-text">decisiones transparentes.</span></h1>
          <p className="hero__lead">
            Una plataforma multi-rifa para líderes, vendedores y compradores. Aprobaciones auditables,
            boletas con estados claros y verificación QR pensada para la confianza.
          </p>
          <div className="hero__actions">
            <Link to={isAuthenticated ? '/app' : '/register'} className="btn btn--primary btn--lg">
              {isAuthenticated ? `Hola, ${user?.full_name.split(' ')[0]}` : 'Participar ahora'}
              <ArrowRight size={18} />
            </Link>
            <a href="#rifas" className="btn btn--secondary btn--lg">Ver rifas activas</a>
          </div>
          <div className="hero__metrics">
            <div><strong>{raffles.length}</strong><span>rifas activas</span></div>
            <div><strong>{totalTickets}</strong><span>boletas pagadas</span></div>
            <div><strong>36</strong><span>combinaciones visuales</span></div>
          </div>
        </section>

        <section id="rifas" className="public-section">
          <div className="public-section__head">
            <div>
              <span className="eyebrow">Oportunidades en curso</span>
              <h2>Rifas activas</h2>
              <p>Explora las rifas aprobadas y conoce su avance en tiempo real.</p>
            </div>
            <Link to="/register" className="text-link">Quiero participar <ArrowRight size={16} /></Link>
          </div>

          {loading ? <PageLoader label="Cargando rifas…" /> : raffles.length === 0 ? (
            <EmptyState
              icon={<Ticket size={26} />}
              title="Muy pronto habrá nuevas rifas"
              description="Los líderes están preparando las próximas oportunidades. Vuelve pronto."
              action={<Link to="/register" className="btn btn--primary btn--md">Crear cuenta</Link>}
            />
          ) : (
            <div className="public-raffle-grid">
              {raffles.map((raffle) => (
                <Card key={raffle.id} className="public-raffle">
                  <div className="public-raffle__head">
                    <Badge tone="success">Activa</Badge>
                    <span>{raffle.code}</span>
                  </div>
                  <h3>{raffle.name}</h3>
                  <p className="muted-text">{raffle.description || 'Una oportunidad para participar.'}</p>
                  <div className="public-raffle__prize"><Trophy size={17} /> {raffle.prize}</div>
                  <div className="progress">
                    <span style={{ width: `${percent(raffle.stats.paid, raffle.total_tickets)}%` }} />
                  </div>
                  <div className="public-raffle__meta">
                    <span>{raffle.stats.paid}/{raffle.total_tickets} pagadas</span>
                    <span>{percent(raffle.stats.paid, raffle.total_tickets)}%</span>
                  </div>
                  <div className="public-raffle__footer">
                    <div><strong>{currency(raffle.ticket_price)}</strong><small>por boleta</small></div>
                    <div><CalendarDays size={15} /><small>Cierra {formatDate(raffle.end_at)}</small></div>
                  </div>
                </Card>
              ))}
            </div>
          )}
        </section>

        <section id="como-funciona" className="public-section">
          <div className="public-section__head">
            <div>
              <span className="eyebrow">Diseñado para operar</span>
              <h2>Un flujo claro para cada rol</h2>
            </div>
          </div>
          <div className="feature-grid">
            <Card><CardBody><ShieldCheck size={24} /><h3>Super administrador</h3><p className="muted-text">Aprueba rifas, gestiona usuarios y audita cada operación crítica.</p></CardBody></Card>
            <Card><CardBody><Users size={24} /><h3>Líder</h3><p className="muted-text">Crea rifas, asigna vendedores y controla el inventario de boletas.</p></CardBody></Card>
            <Card><CardBody><Ticket size={24} /><h3>Vendedor</h3><p className="muted-text">Gestiona sus números: disponible, separado y pagado.</p></CardBody></Card>
            <Card><CardBody><Trophy size={24} /><h3>Comprador</h3><p className="muted-text">Consulta rifas y revisa las boletas que ha comprado.</p></CardBody></Card>
          </div>
        </section>

        <section id="confianza" className="public-section public-section--split">
          <div>
            <span className="eyebrow">Confianza por diseño</span>
            <h2>La tecnología detrás de la transparencia</h2>
            <p className="muted-text">
              Autenticación JWT, refresh rotativo, API keys con scopes, auditoría append-only y un
              modelo de rifas con aprobación central. La versión 1.0.0 está construida para crecer.
            </p>
            <div className="check-list">
              <span><ShieldCheck size={17} /> Roles y permisos por recurso</span>
              <span><Ticket size={17} /> Boletas con token QR no predecible</span>
              <span><Users size={17} /> Multi-rifa con aprobación del super administrador</span>
            </div>
          </div>
          <Card className="trust-card">
            <CardBody>
              <Badge tone="violet">V1.0.0</Badge>
              <h3>Arquitectura cliente / servidor</h3>
              <p className="muted-text">React + TypeScript en el cliente, PHP 8.2 + PDO en el servidor, API REST versionada y base de datos portable.</p>
              <div className="trust-card__stats">
                <div><strong>12</strong><span>temas</span></div>
                <div><strong>3</strong><span>modos</span></div>
                <div><strong>100%</strong><span>semántico</span></div>
              </div>
            </CardBody>
          </Card>
        </section>
      </main>

      <footer className="public-footer">
        <span>© {new Date().getFullYear()} SKATENEA. Sistema de Gestión de Rifas.</span>
        <span><Link to="/login">Acceso administrativo</Link> · <Link to="/register">Registro</Link></span>
      </footer>
    </div>
  );
}
