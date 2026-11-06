import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { CheckCircle2, Search, ShieldAlert, Sparkles } from 'lucide-react';
import { api, ApiError } from '../api/client';
import type { VerifyResult } from '../api/types';
import { Background } from '../components/layout/Background';
import { Badge, TicketStatusBadge } from '../components/ui/Badge';
import { Card, CardBody } from '../components/ui/Card';
import { PageLoader } from '../components/ui/Feedback';

export function VerifyTicketPage() {
  const { token = '' } = useParams();
  const [result, setResult] = useState<VerifyResult | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api.verifyTicket(token)
      .then(setResult)
      .catch((caught) => setError(caught instanceof ApiError ? caught.message : 'No se pudo verificar la boleta.'));
  }, [token]);

  return (
    <div className="state-page">
      <Background />
      <div className="glass state-card verify-card">
        <div className="brand">
          <span className="brand__mark" aria-hidden="true"><Sparkles size={24} /></span>
          <span className="brand__text"><strong>SKATENEA</strong><small>Verificación de boleta</small></span>
        </div>
        {!result && !error && <PageLoader label="Verificando boleta…" />}
        {error && (
          <>
            <span className="state-card__icon"><ShieldAlert size={28} /></span>
            <h1>No pudimos verificar</h1>
            <p>{error}</p>
            <Link to="/" className="btn btn--secondary btn--md"><Search size={17} /> Ir al inicio</Link>
          </>
        )}
        {result && !result.valid && (
          <>
            <span className="state-card__icon"><ShieldAlert size={28} /></span>
            <h1>Boleta no válida</h1>
            <p>El token no corresponde a una boleta registrada. Solicita verificación al vendedor.</p>
            <Link to="/" className="btn btn--secondary btn--md"><Search size={17} /> Ir al inicio</Link>
          </>
        )}
        {result?.valid && (
          <>
            <span className="state-card__icon" style={{ color: 'var(--success)' }}><CheckCircle2 size={30} /></span>
            <Badge tone="success">Boleta verificada</Badge>
            <h1>Boleta #{result.number}</h1>
            <p>{result.raffle}</p>
            <Card className="verify-details">
              <CardBody>
                <div className="info-grid">
                  <div><span>Estado</span>{result.status ? <TicketStatusBadge status={result.status} /> : '—'}</div>
                  <div><span>Ganadora</span><strong>{result.winner ? 'Sí' : 'No'}</strong></div>
                </div>
              </CardBody>
            </Card>
            <p className="muted-text">Esta verificación no expone datos personales del comprador.</p>
          </>
        )}
      </div>
    </div>
  );
}
