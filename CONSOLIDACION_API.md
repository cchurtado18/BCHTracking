# Módulo 2: CONSOLIDACIÓN (SACOS) - Documentación API

## Comandos de Setup

### 1. Ejecutar migraciones
```bash
php artisan migrate
```

Esto creará:
- Tabla `consolidations` (sacos)
- Tabla `consolidation_items` (items en sacos)
- Actualizará `preregistrations.status` con los nuevos valores

### 2. Verificar rutas API
```bash
php artisan route:list --path=api/consolidations
```

## Endpoints API

Base URL: `http://localhost/api` (o tu dominio)

### 1. Crear Consolidación (Saco)
**POST** `/api/consolidations`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "service_type": "AIR",
    "notes": "Saco para envío aéreo - Febrero 2026"
}
```

**Notas:**
- `service_type`: **Requerido**. Valores: `AIR`, `SEA`
- `notes`: Opcional, máximo 1000 caracteres
- El código se genera automáticamente: `SAC-YYYYMM-0001` (correlativo por mes)
- El estado inicial es `OPEN`

**Response (201):**
```json
{
    "id": 1,
    "code": "SAC-202602-0001",
    "service_type": "AIR",
    "status": "OPEN",
    "sent_at": null,
    "received_at": null,
    "notes": "Saco para envío aéreo - Febrero 2026",
    "created_at": "2026-02-03T03:09:17.000000Z",
    "updated_at": "2026-02-03T03:09:17.000000Z"
}
```

---

### 2. Listar Consolidaciones (con filtros opcionales)
**GET** `/api/consolidations`

**Query Parameters (opcionales):**
- `status`: `OPEN`, `SENT`, `RECEIVED`, `CANCELLED`
- `service_type`: `AIR` o `SEA`
- `date_from`: `YYYY-MM-DD`
- `date_to`: `YYYY-MM-DD`

**Ejemplo:**
```
GET /api/consolidations?status=OPEN&service_type=AIR
```

**Response (200):**
```json
[
    {
        "id": 1,
        "code": "SAC-202602-0001",
        "service_type": "AIR",
        "status": "OPEN",
        "sent_at": null,
        "received_at": null,
        "notes": "Saco para envío aéreo",
        "created_at": "2026-02-03T03:09:17.000000Z",
        "updated_at": "2026-02-03T03:09:17.000000Z",
        "items_count": 5
    }
]
```

---

### 3. Ver Detalle de Consolidación
**GET** `/api/consolidations/{id}`

**Ejemplo:**
```
GET /api/consolidations/1
```

**Response (200):**
```json
{
    "id": 1,
    "code": "SAC-202602-0001",
    "service_type": "AIR",
    "status": "OPEN",
    "sent_at": null,
    "received_at": null,
    "notes": "Saco para envío aéreo",
    "created_at": "2026-02-03T03:09:17.000000Z",
    "updated_at": "2026-02-03T03:09:17.000000Z",
    "items": [
        {
            "id": 1,
            "tracking_external": "1Z999AA10123456784",
            "label_name": "Juan Perez",
            "intake_weight_lbs": "3.20",
            "status": "RECEIVED_MIAMI",
            "created_at": "2026-02-03T02:27:28.000000Z",
            "added_at": "2026-02-03T03:10:00.000000Z",
            "scanned_at": null
        }
    ],
    "report": {
        "total_items": 5,
        "total_lbs": 15.50,
        "scanned_count": 0,
        "missing_count": 5
    }
}
```

---

### 4. Agregar Items por Filtro (Fecha + Service Type)
**POST** `/api/consolidations/{id}/items/by-filter`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "from": "2026-02-01",
    "to": "2026-02-03",
    "service_type": "AIR"
}
```

**Notas:**
- Agrega todos los preregistros que:
  - Tienen `status = 'RECEIVED_MIAMI'`
  - Coinciden con `service_type`
  - Fueron creados entre `from` y `to` (inclusive)
  - **NO están ya en otro saco**

**Response (200):**
```json
{
    "message": "Se agregaron 5 preregistros al saco.",
    "added_count": 5
}
```

**Errores:**
- **400**: Si la consolidación no está `OPEN`
- **404**: Si no se encontraron preregistros que cumplan los criterios

---

### 5. Agregar Item por Escaneo (Tracking)
**POST** `/api/consolidations/{id}/items/by-scan`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "tracking_external": "1Z999AA10123456784"
}
```

**Notas:**
- Busca el preregistro por `tracking_external`
- Valida que esté en estado `RECEIVED_MIAMI`
- Valida que no esté ya en otro saco

**Response (200):**
```json
{
    "message": "Preregistro agregado exitosamente al saco.",
    "preregistration": {
        "id": 1,
        "tracking_external": "1Z999AA10123456784",
        "label_name": "Juan Perez"
    }
}
```

**Errores:**
- **400**: Si la consolidación no está `OPEN`
- **404**: Si el preregistro no existe
- **400**: Si el preregistro no está en estado `RECEIVED_MIAMI`
- **400**: Si el preregistro ya está en otro saco

---

### 6. Enviar Consolidación
**POST** `/api/consolidations/{id}/send`

**Headers:**
```
Accept: application/json
```

**Notas:**
- Solo funciona si la consolidación está en estado `OPEN`
- Cambia el estado de la consolidación a `SENT`
- Establece `sent_at` a la fecha/hora actual
- **Cambia el estado de TODOS los preregistros dentro del saco a `IN_TRANSIT`**

**Response (200):**
```json
{
    "message": "Consolidación enviada exitosamente.",
    "consolidation": {
        "id": 1,
        "code": "SAC-202602-0001",
        "status": "SENT",
        "sent_at": "2026-02-03T03:15:00.000000Z"
    }
}
```

**Errores:**
- **400**: Si la consolidación no está en estado `OPEN`

---

## Reglas de Negocio

### Estados de Consolidación
- **OPEN**: Saco abierto, se pueden agregar/quitar items
- **SENT**: Saco enviado a Nicaragua, no se pueden modificar items
- **RECEIVED**: Saco recibido en Nicaragua (se usará más adelante)
- **CANCELLED**: Saco cancelado

### Estados de Preregistro
- **RECEIVED_MIAMI**: Preregistrado en Miami, disponible para consolidación
- **IN_TRANSIT**: En tránsito hacia Nicaragua (automático cuando se envía el saco)
- **IN_WAREHOUSE_NIC**: Recibido en almacén de Nicaragua
- **READY**: Listo para retiro
- **DELIVERED**: Entregado
- **CANCELLED**: Cancelado

### Restricciones
1. **Un preregistro solo puede estar en un saco** (constraint UNIQUE en `preregistration_id`)
2. **Solo se pueden agregar items a sacos OPEN**
3. **Solo preregistros con status `RECEIVED_MIAMI` pueden agregarse a sacos**
4. **Al enviar un saco, todos sus preregistros pasan a `IN_TRANSIT`**

---

## Ejemplos Postman

### Collection JSON para importar:

```json
{
    "info": {
        "name": "Consolidación API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Crear Consolidación",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    },
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"service_type\": \"AIR\",\n    \"notes\": \"Saco para envío aéreo\"\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/consolidations",
                    "host": ["{{base_url}}"],
                    "path": ["api", "consolidations"]
                }
            }
        },
        {
            "name": "Listar Consolidaciones",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/consolidations?status=OPEN",
                    "host": ["{{base_url}}"],
                    "path": ["api", "consolidations"],
                    "query": [
                        {
                            "key": "status",
                            "value": "OPEN"
                        }
                    ]
                }
            }
        },
        {
            "name": "Ver Consolidación",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/consolidations/1",
                    "host": ["{{base_url}}"],
                    "path": ["api", "consolidations", "1"]
                }
            }
        },
        {
            "name": "Agregar Items por Filtro",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    },
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"from\": \"2026-02-01\",\n    \"to\": \"2026-02-03\",\n    \"service_type\": \"AIR\"\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/consolidations/1/items/by-filter",
                    "host": ["{{base_url}}"],
                    "path": ["api", "consolidations", "1", "items", "by-filter"]
                }
            }
        },
        {
            "name": "Agregar Item por Escaneo",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    },
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"tracking_external\": \"1Z999AA10123456784\"\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/consolidations/1/items/by-scan",
                    "host": ["{{base_url}}"],
                    "path": ["api", "consolidations", "1", "items", "by-scan"]
                }
            }
        },
        {
            "name": "Enviar Consolidación",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/consolidations/1/send",
                    "host": ["{{base_url}}"],
                    "path": ["api", "consolidations", "1", "send"]
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

## Flujo de Trabajo Típico

1. **Crear saco**: `POST /api/consolidations` con `service_type`
2. **Agregar preregistros**:
   - Opción A: Por filtro masivo: `POST /api/consolidations/{id}/items/by-filter`
   - Opción B: Por escaneo individual: `POST /api/consolidations/{id}/items/by-scan`
3. **Verificar contenido**: `GET /api/consolidations/{id}` (ver reporte)
4. **Enviar saco**: `POST /api/consolidations/{id}/send`
   - Esto cambia todos los preregistros a `IN_TRANSIT`

---

## Validaciones y Errores

### Errores Comunes

1. **"Solo se pueden agregar items a consolidaciones con estado OPEN"**
   - Solución: Solo se pueden agregar items a sacos en estado `OPEN`

2. **"Este preregistro ya está en otro saco"**
   - Solución: Un preregistro solo puede estar en un saco a la vez

3. **"El preregistro no está en estado RECEIVED_MIAMI"**
   - Solución: Solo preregistros con estado `RECEIVED_MIAMI` pueden agregarse

4. **"No se encontraron preregistros que cumplan los criterios"**
   - Solución: Verifica las fechas y `service_type` del filtro

---

## Notas Importantes

1. **Generación de código**: El código se genera automáticamente en formato `SAC-YYYYMM-0001`, correlativo por mes
2. **Sin autenticación**: Este módulo no tiene autenticación por ahora
3. **Transacciones**: Las operaciones críticas (enviar saco) usan transacciones de base de datos
4. **Constraints**: La base de datos previene que un preregistro esté en dos sacos simultáneamente

