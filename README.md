# moneylui

Template PHP T1 berbasis runtime Hoksiong.

## Isi

- `T1.php` — source template PHP.
- `data.example.json` — contoh kontrak data netral.
- `MANIFEST.json` — checksum SHA-256 artifact.

## Struktur deployment

```text
public_html/
├── index.php              # salinan T1.php
├── data/
│   └── data.json          # mengikuti data.example.json
└── img/
    ├── logo.webp
    ├── favicon.webp
    └── banner.webp
```

Template membaca data domain dari `data/data.json`. Domain, keyword, metadata, artikel, CTA, canonical, AMP, serta aset tidak disimpan di repository ini.

## Routing

Pretty URL membutuhkan rewrite menuju `index.php?id=<slug>` dengan pengecualian file dan direktori nyata.

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([a-zA-Z0-9\-]+)/?$ index.php?id=$1 [L,QSA]
```

## Keamanan

Repository ini tidak boleh berisi credential, token, data produksi, atau konfigurasi hosting.
