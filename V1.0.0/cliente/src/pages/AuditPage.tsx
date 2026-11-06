import { useEffect, useState } from 'react';
import { Download, RefreshCw, ScrollText } from 'lucide-react';
import { api, ApiError } from '../api/client';
import type { AuditEntry } from '../api/types';
import { useToast } from '../components/ui/Toast';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Card, CardBody, CardHeader } from '../components/ui/Card';
import { EmptyState, PageHeader, PageLoader } from '../components/ui/Feedback';
import { formatDateTime } from '../lib/format';

export function AuditPage() {
  const { toast } = useToast();
  const [entries, setEntries] = useState<AuditEntry[]>([]);
  const [loading, setLoading] = useState(true);

  const load = () => {
    setLoading(true);
    api.audit({ limit: 200 })
      .then((payload) => setEntries(payload.audit))
      .catch((error) => toast('error', 'No se pudo cargar la auditoría', error instanceof ApiError ? error.message : undefined))
      .finally(() => setLoading(false));
  };

  useEffect(load, []);

  const exportCsv = async () => {
    try {
      const blob = await api.download('/audit/export.csv');
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = 'auditoria-skatenea.csv';
      anchor.click();
      URL.revokeObjectURL(url);
    } catch (error) {
      toast('error', 'No se pudo exportar', error instanceof ApiError ? error.message : undefined);
    }
  };

  return (
    <div className="page-stack">
      <PageHeader
        eyebrow="Gobierno"
        title="Auditoría"
        subtitle="Registro append-only de las operaciones críticas del sistema."
        actions={
          <>
            <Button variant="secondary" icon={<Download size={16} />} onClick={exportCsv}>Exportar CSV</Button>
            <Button variant="secondary" icon={<RefreshCw size={16} />} onClick={load}>Actualizar</Button>
          </>
        }
      />

      {loading ? <PageLoader label="Cargando auditoría…" /> : entries.length === 0 ? (
        <EmptyState icon={<ScrollText size={26} />} title="No hay registros todavía" description="Las operaciones críticas aparecerán aquí." />
      ) : (
        <Card>
          <CardHeader icon={<ScrollText size={20} />} title="Eventos recientes" subtitle={`${entries.length} registros`} actions={<Badge tone="violet">{entries.length}</Badge>} />
          <CardBody>
            <div className="table-wrap">
              <table className="data-table">
                <thead><tr><th>Fecha</th><th>Actor</th><th>Acción</th><th>Entidad</th><th>IP</th></tr></thead>
                <tbody>
                  {entries.map((entry) => (
                    <tr key={entry.id}>
                      <td>{formatDateTime(entry.created_at)}</td>
                      <td><code>{entry.actor_id ? entry.actor_id.slice(0, 8) : entry.actor_type}</code></td>
                      <td><code>{entry.action}</code></td>
                      <td>{entry.entity_type}{entry.entity_id ? ` · ${entry.entity_id.slice(0, 8)}` : ''}</td>
                      <td>{entry.ip ?? '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardBody>
        </Card>
      )}
    </div>
  );
}
