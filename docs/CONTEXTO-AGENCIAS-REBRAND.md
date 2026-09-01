# Contexto: jerarquía de agencias y rebranding

Notas de negocio para alinear el código con la operación real.  
Complementa `docs/CONTABILIDAD-PLAN-FASES.md` y `docs/LISTA-CAMBIOS-REVISION.md`.

## Jerarquía deseada

```text
SkyLink One                    ← única raíz / marca dueña
└── CH Logistics               ← agencia cliente (subagencia de SLO)
    ├── Subagencia A de CH
    └── Subagencia B de CH
└── (otras subagencias directas de SLO, si aplica)
```

- CH **deja de ser** “agencia principal” al mismo nivel que SkyLink One.
- CH **sigue** pudiendo tener subagencias a su favor (segundo nivel bajo SLO).

## Módulo Clientes (implementado)

Menú **Clientes** (rutas `agencies.*`). Tipos: `root` (SLO), `subagency`, `direct_client` (cliente propio de SLO con login, sin hijas). CH cuelga de SLO. Hoja de salida agrupa `deliveryNetworkIds()` (la subagencia + su red).

## Rebranding del sistema

| Elemento | Hoy / destino | Estado |
|---|---|---|
| Nombre UI | **PrimeTrack Group** | Aplicado en UI |
| Logo | `public/images/primetrack-group-logo.png` | Aplicado |
| Paleta | navy `#0A2D6F` / `#1E4FA8` | Aplicada en UI |
| Notas de entrega | prefijo **`BCH-` se mantiene** | Decidido |
| Etiquetas / Nota de cobro | Marca **SLO o CH según agencia** (no unificar a PrimeTrack) | Decidido |
| APP_NAME | PrimeTrack Group | Alineado en `.env` / example |

No confundir con logos **por agencia** (`agencies.logo_path`).

## Revisión cerrada (2026-08-10)

Ver `docs/LISTA-CAMBIOS-REVISION.md`.

**Siguiente bloque autorizado:** **B — Jerarquía de agencias** (antes de Contabilidad).

### Orden de implementación

1. ~~Rebrand UI~~ (hecho)
2. **Jerarquía:** CH bajo SLO; CH con hijos; ancestros; UI (autorizado)
3. Contabilidad Fase 1+ con rollup por árbol
