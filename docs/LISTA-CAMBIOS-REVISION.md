# Lista de cambios — revisión cerrada

Fecha de cierre de revisión: 2026-08-10.  
Fuente: plan “Lista cambios PrimeTrack” (revisión; sin editar el archivo del plan).

## Resumen por bloque

### A. Rebrand visual
| # | Cambio | Estado |
|---|---|---|
| A1–A6 | Nombre, logo, paleta, login, sin colores-por-módulo | **Hecho / acordado** |
| A7 | Prefijo notas `BCH-` | **Decidido: mantener `BCH-`** (histórico; no romper notas ya emitidas) |
| A8 | Marca etiquetas/comprobantes | **Decidido: seguir SLO vs CH por agencia**; UI del panel = PrimeTrack Group |

### B. Jerarquía de agencias
| # | Cambio | Estado |
|---|---|---|
| B1–B5 | CH bajo SLO + subagencias de CH + ancestros + UI | **Pendiente — siguiente bloque autorizado** |

### C. Contabilidad
| # | Cambio | Estado |
|---|---|---|
| C1 | Tarifas (rate cards mínimas al emitir) | Parcial (al generar factura) |
| C3 | Factura + voucher térmico + vínculo a hoja de salida | **Hecho (base)** |
| C2, C4–C6 | Rentabilidad, cobros, gastos, CRM | Pendiente |
| C7–C8 | Acceso admin + rollup | Acceso admin en facturas; rollup pendiente |

### D. No-regresión
Operación Miami/NIC/entregas/roles/fotos: sin cambios bloqueantes.

---

## Decisiones cerradas (antes abiertas)

1. **Prefijo `BCH-`:** se **mantiene**. Nuevas facturas contables usarán nombre “Factura PrimeTrack”, no el prefijo de notas de entrega.
2. **Etiquetas/comprobantes:** **no unificar** a PrimeTrack; siguen diseño SkyLink One / CH Logistics según red de la agencia (`isSkyLinkOne` / `isChLogistics` por ancestros tras B).
3. **Siguiente bloque a implementar:** **B — Jerarquía de agencias** (antes que Contabilidad C1).

---

## Autorización del siguiente bloque

**Autorizado: Bloque B (jerarquía de agencias).**

Alcance al implementar (cuando se pida código):

1. Migración/datos: solo SkyLink One `is_main`; CH `parent_agency_id = SLO`, `is_main = false`.
2. Permitir crear subagencia con padre = CH (o cualquier agencia que pueda ser padre de red).
3. Helpers de ancestro en `Agency` para marca/etiquetas.
4. Textos UI de alta/listado de agencias alineados al árbol.

**No autorizado aún en esta revisión:** Contabilidad C1+ (va después de B).

---

## Orden de trabajo vigente

1. ~~Revisión lista A/B/C~~ → **cerrada**
2. **B** — Jerarquía (siguiente)
3. A7/A8 ya cerrados (sin cambio de prefijo; etiquetas por agencia)
4. **C1 → C2** — Tarifas + rentabilidad
5. **C3 → C6** — Facturación, cobros, gastos, CRM contable
