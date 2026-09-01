# PrimeTrack Group — Paleta UI (contexto)

Fuente: `docs/assets/PrimeTrack_UI_Palette_Guide.pdf`  
Estado: **aplicado en UI** (preview visual). Colores por módulo del PDF: ignorados.

Hoy el panel usa emerald (`#10b981` / Tailwind green). El destino es **azul corporativo navy** alineado al logo.

## 1. Paleta corporativa

| Uso | HEX |
|---|---|
| Azul corporativo principal | `#0A2D6F` |
| Azul secundario | `#1E4FA8` |
| Gris oscuro (texto secundario) | `#5E6168` |
| Gris claro (bordes / fondos suaves) | `#E8EBEF` |
| Blanco | `#FFFFFF` |

## 2. Colores funcionales

| Función | HEX |
|---|---|
| Éxito | `#2BB673` |
| Advertencia | `#F6A623` |
| Error | `#D64545` |
| Información | `#3498DB` |

## 3. Asignación UI (del PDF)

| Elemento | Regla |
|---|---|
| Fondo general | Base clara (blanco / gris muy claro) |
| Header | Texto e iconos en blanco (sobre azul principal) |
| Menú lateral | Hover `#1E4FA8`, seleccionado `#0A2D6F` |
| Cards | Borde `#E8EBEF`, sombra ligera |
| Formularios | Borde `#D8DCE2`, foco `#1E4FA8` |
| Botón principal | Fondo azul principal, texto blanco, hover `#1E4FA8` |
| Botón secundario | Texto y borde `#0A2D6F` |
| Tablas | Filas blancas, hover `#F4F8FD` |
| Radio de esquinas | 10–12 px |
| Tipografía | Inter (alt: Segoe UI, Roboto, Open Sans); ~14 / 13 / 12 px |

## 4. Módulos listados en la guía

Dashboard, Clientes, Cotizaciones, Cargas, Rastreo, Facturación, Cobros, Contabilidad, Inventario, Reportes, Configuración.

> El extracto del PDF **no trae HEX distintos por módulo** (solo nombres). Si hay acentos por módulo en la versión visual, habrá que confirmarlos al implementar.

## 5. Variables CSS sugeridas (cuando se implemente)

```css
--brand-primary: #0A2D6F;
--brand-secondary: #1E4FA8;
--brand-muted: #5E6168;
--brand-surface: #E8EBEF;
--brand-white: #FFFFFF;
--brand-form-border: #D8DCE2;
--brand-row-hover: #F4F8FD;
--state-success: #2BB673;
--state-warning: #F6A623;
--state-error: #D64545;
--state-info: #3498DB;
```

## Relación con el resto del contexto

- Marca: **PrimeTrack Group** + logo en `docs/assets/primetrack-group-logo.png`
- Jerarquía: SLO raíz → CH subagencia → subagencias de CH
- Contabilidad: módulo paralelo (ver `CONTABILIDAD-PLAN-FASES.md`)
