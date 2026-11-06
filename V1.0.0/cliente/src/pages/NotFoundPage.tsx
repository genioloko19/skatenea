import { Link } from 'react-router-dom';
import { Compass, Home } from 'lucide-react';
import { Background } from '../components/layout/Background';

export function NotFoundPage() {
  return (
    <div className="state-page">
      <Background />
      <div className="glass state-card">
        <span className="state-card__icon"><Compass size={34} /></span>
        <span className="eyebrow">404</span>
        <h1>Esta página no existe.</h1>
        <p>El enlace puede estar roto o la página se movió. Vuelve al inicio para continuar.</p>
        <Link to="/" className="btn btn--primary btn--lg"><Home size={18} /> Ir al inicio</Link>
      </div>
    </div>
  );
}
