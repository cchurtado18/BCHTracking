# Módulo: TRACKING PÚBLICO - Documentación API

## Endpoint Público

Este endpoint permite consultar el estado de un paquete sin necesidad de autenticación. Es un endpoint público diseñado para ser usado por clientes finales.

---

## Consultar Tracking

**GET** `/api/public/tracking/{code}`

**Parámetros:**
- `{code}`: Puede ser:
  - **Warehouse Code**: 6 dígitos numéricos (ej: `123456`)
  - **Tracking Externo**: Código del courier (ej: `1Z999AA10123456784`)

**Lógica de Búsqueda:**
1. Si el código tiene exactamente 6 dígitos → busca por `warehouse_code`
2. Si no → busca por `tracking_external`
3. Si no existe → retorna 404 con mensaje "No encontrado"

**Headers:**
```
Accept: application/json
```

**Ejemplos:**

### 1. Buscar por Warehouse Code (6 dígitos)
```
GET /api/public/tracking/123456
```

**Response (200):**
```json
{
    "code": "123456",
    "service_type": "AIR",
    "status": "READY",
    "received_at": "2026-02-03T04:00:00.000000Z",
    "updated_at": "2026-02-03T05:30:00.000000Z"
}
```

### 2. Buscar por Tracking Externo
```
GET /api/public/tracking/1Z999AA10123456784
```

**Response (200):**
```json
{
    "code": "1Z999AA10123456784",
    "service_type": "SEA",
    "status": "IN_TRANSIT",
    "received_at": "2026-02-01T10:00:00.000000Z",
    "updated_at": "2026-02-03T03:00:00.000000Z"
}
```

### 3. Código No Encontrado
```
GET /api/public/tracking/999999
```

**Response (404):**
```json
{
    "message": "No encontrado"
}
```

---

## Lógica de `received_at`

El campo `received_at` se determina según el estado del paquete:

1. **Si status es `RECEIVED_MIAMI` o `IN_TRANSIT`**:
   - Usa `created_at` como fecha de recepción en Miami

2. **Si `received_nic_at` existe**:
   - Usa `received_nic_at` como fecha de recepción en Nicaragua

3. **Fallback**:
   - Si ninguna de las anteriores aplica, usa `created_at`

---

## Campos Retornados

El endpoint retorna **SOLO** información mínima:

- `code`: El código que se consultó (warehouse_code o tracking_external)
- `service_type`: Tipo de servicio (`AIR` o `SEA`)
- `status`: Estado actual del paquete
  - `RECEIVED_MIAMI`
  - `IN_TRANSIT`
  - `IN_WAREHOUSE_NIC`
  - `READY`
  - `DELIVERED`
  - `CANCELLED`
- `received_at`: Fecha de recepción (ISO 8601)
- `updated_at`: Última actualización (ISO 8601)

**Campos NO retornados** (por seguridad):
- `agency_id`
- `agency_client_id`
- `intake_weight_lbs`
- `verified_weight_lbs`
- `label_name`
- `tracking_external` (si se consultó por warehouse_code)
- `warehouse_code` (si se consultó por tracking_external)
- `photos`
- `notes`
- `holding_reason`
- `assignment_status`
- Cualquier otra información sensible

---

## Estados del Paquete

| Estado | Descripción |
|--------|-------------|
| `RECEIVED_MIAMI` | Preregistrado en Miami, aún no ha salido |
| `IN_TRANSIT` | En tránsito hacia Nicaragua |
| `IN_WAREHOUSE_NIC` | Recibido en almacén de Nicaragua |
| `READY` | Listo para retiro |
| `DELIVERED` | Entregado |
| `CANCELLED` | Cancelado |

---

## Ejemplos de Respuestas por Estado

### Paquete en Miami (RECEIVED_MIAMI)
```json
{
    "code": "123456",
    "service_type": "AIR",
    "status": "RECEIVED_MIAMI",
    "received_at": "2026-02-01T10:00:00.000000Z",
    "updated_at": "2026-02-01T10:00:00.000000Z"
}
```

### Paquete en Tránsito (IN_TRANSIT)
```json
{
    "code": "123456",
    "service_type": "AIR",
    "status": "IN_TRANSIT",
    "received_at": "2026-02-01T10:00:00.000000Z",
    "updated_at": "2026-02-02T14:30:00.000000Z"
}
```

### Paquete en Almacén Nicaragua (IN_WAREHOUSE_NIC)
```json
{
    "code": "123456",
    "service_type": "AIR",
    "status": "IN_WAREHOUSE_NIC",
    "received_at": "2026-02-03T04:00:00.000000Z",
    "updated_at": "2026-02-03T04:00:00.000000Z"
}
```

### Paquete Listo para Retiro (READY)
```json
{
    "code": "123456",
    "service_type": "AIR",
    "status": "READY",
    "received_at": "2026-02-03T04:00:00.000000Z",
    "updated_at": "2026-02-03T05:30:00.000000Z"
}
```

### Paquete Entregado (DELIVERED)
```json
{
    "code": "123456",
    "service_type": "AIR",
    "status": "DELIVERED",
    "received_at": "2026-02-03T04:00:00.000000Z",
    "updated_at": "2026-02-03T06:00:00.000000Z"
}
```

---

## Ejemplos Postman

### Collection JSON para importar:

```json
{
    "info": {
        "name": "Tracking Público API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Tracking por Warehouse Code",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/public/tracking/123456",
                    "host": ["{{base_url}}"],
                    "path": ["api", "public", "tracking", "123456"]
                }
            }
        },
        {
            "name": "Tracking por Tracking Externo",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/public/tracking/1Z999AA10123456784",
                    "host": ["{{base_url}}"],
                    "path": ["api", "public", "tracking", "1Z999AA10123456784"]
                }
            }
        },
        {
            "name": "Tracking No Encontrado",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/public/tracking/999999",
                    "host": ["{{base_url}}"],
                    "path": ["api", "public", "tracking", "999999"]
                }
            }
        }
    ],
    "variable": [
        {
            "key": "base_url",
            "value": "http://localhost",
            "type": "string"
        }
    ]
}
```

---

## Notas Importantes

1. **Endpoint Público**: No requiere autenticación, puede ser usado por cualquier cliente
2. **Información Mínima**: Solo retorna campos esenciales para tracking
3. **Sin Información Sensible**: No expone datos de agencias, clientes, pesos, fotos, etc.
4. **Búsqueda Inteligente**: Detecta automáticamente si el código es warehouse_code (6 dígitos) o tracking_external
5. **Formato de Fechas**: Todas las fechas están en formato ISO 8601

---

## Integración

Este endpoint está diseñado para ser integrado en:
- Sitios web públicos
- Aplicaciones móviles
- Widgets de tracking
- Sistemas de notificaciones

**Ejemplo de uso en HTML/JavaScript:**
```javascript
fetch('https://api.ejemplo.com/api/public/tracking/123456')
    .then(response => response.json())
    .then(data => {
        console.log('Estado:', data.status);
        console.log('Recibido:', data.received_at);
    });
```

---

## Seguridad

- **Sin autenticación**: Endpoint público, accesible sin credenciales
- **Información limitada**: Solo expone datos necesarios para tracking
- **Sin datos sensibles**: No retorna información de agencias, clientes, pesos, etc.
- **Validación de código**: Verifica que el código exista antes de retornar información

