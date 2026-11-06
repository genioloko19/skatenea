# Despliegue con Nginx

## 1. API (PHP-FPM)

```nginx
server {
    listen 443 ssl http2;
    server_name api.skatenea.example;
    root /var/www/skatenea/servidor/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/api.skatenea.example/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.skatenea.example/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header Referrer-Policy "no-referrer" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 60;
    }

    location ~ /\.(env|git|htaccess) { deny all; }
    location ~* \.(sql|md|log|sqlite|bak)$ { deny all; }
}
```

## 2. Cliente estático

```nginx
server {
    listen 443 ssl http2;
    server_name app.skatenea.example;
    root /var/www/skatenea/cliente/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(js|css|svg|png|webp|woff2)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
    }

    add_header Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self' https://api.skatenea.example; frame-ancestors 'none'; base-uri 'self'; form-action 'self';" always;
}
```

## 3. Workers

Usar systemd/supervisor:

```ini
[Unit]
Description=SKATENEA Mail Worker
After=network.target

[Service]
User=skatenea
WorkingDirectory=/var/www/skatenea/servidor
ExecStart=/usr/bin/php bin/mail-worker.php
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Repetir para `webhook-worker.php`, `print-worker.php` y un timer diario para `maintenance.php`.

## 4. Checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`.
- [ ] `COOKIE_SECURE=true`, `COOKIE_SAMESITE=Strict`.
- [ ] `CORS_ALLOWED_ORIGINS` solo el dominio del cliente.
- [ ] `JWT_SECRET` de 64 bytes y rotación documentada.
- [ ] MySQL con TLS y usuario de mínimo privilegio.
- [ ] Backups cifrados y restauración probada.
- [ ] Logs fuera del webroot y retención definida.
- [ ] Monitoreo de 5xx, 401/403/429 y colas.
