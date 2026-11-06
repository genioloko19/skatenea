import { Link } from 'react-router-dom';
import { Home, ShieldAlert } from 'lucide-react';

export function ForbiddenPage() {
  return (
    <div className="state-card state-card--inline glass">
      <span className="state-card__icon"><ShieldAlert size={30} /></span>
      <span className="eyebrow">403</span>
      <h1>No tienes acceso a esta sección.</h1>
      <p>Tu rol no incluye este permiso. Si crees que es un error, contacta al super administrador.</p>
      <Link to="/app" className="btn btn--secondary btn--md"><Home size={17} /> Volver al panel</Link>
    </div>
  );
}
