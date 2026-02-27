# Módulo: PAQUETES (Procesamiento Nicaragua + Etiqueta Warehouse) - Documentación API

## Comandos de Setup

### 1. Ejecutar migraciones
```bash
php artisan migrate
```

Esto creará:
- Tabla `agencies` (agencias B2B)
- Campos adicionales en `preregistrations`:
  - `agency_id` (FK a agencies)
  - `verified_weight_lbs` (peso verificado)
  - `ready_at` (fecha de procesamiento)
  - `label_print_count` (contador de impresiones)
  - `label_last_printed_at` (última impresión)

### 2. Crear agencias de prueba (opcional)
```sql
INSERT INTO agencies (name, code, active, created_at, updated_at) VALUES
('Agencia Test 1', 'AGT001', 1, NOW(), NOW()),
('Agencia Test 2', 'AGT002', 1, NOW(), NOW());
```

### 3. Verificar rutas API
```bash
php artisan route:list --path=api/packages
```

## Endpoints API

Base URL: `http://localhost/api` (o tu dominio)

### 1. Listar Paquetes (Unificado)
**GET** `/api/packages`

**Query Parameters (opcionales):**
- `status`: String o array. Valores: `RECEIVED_MIAMI`, `IN_TRANSIT`, `IN_WAREHOUSE_NIC`, `READY`, `DELIVERED`, `CANCELLED`
  - Ejemplo: `status=IN_WAREHOUSE_NIC` o `status[]=IN_WAREHOUSE_NIC&status[]=READY`
- `service_type`: `AIR` o `SEA`
- `intake_type`: `COURIER` o `DROP_OFF`
- `date_from`: `YYYY-MM-DD` (filtra por `created_at`)
- `date_to`: `YYYY-MM-DD` (filtra por `created_at`)
- `search`: Busca en `tracking_external`, `warehouse_code` o `label_name`
- `page`: Número de página (paginación)

**Ejemplo:**
```
GET /api/packages?status=IN_WAREHOUSE_NIC&service_type=AIR&search=Juan
```

**Response (200):**
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "code_to_scan": "000001",
            "tracking_external": null,
            "warehouse_code": "000001",
            "label_name": "Juan Perez",
            "service_type": "AIR",
            "intake_weight_lbs": "3.20",
            "verified_weight_lbs": null,
            "status": "IN_WAREHOUSE_NIC",
            "received_nic_at": "2026-02-03T04:00:00.000000Z",
            "ready_at": null,
            "agency_id": null,
            "agency": null,
            "is_preregistration": false,
            "display_label": ""
        },
        {
            "id": 2,
            "code_to_scan": "1Z999AA10123456784",
            "tracking_external": "1Z999AA10123456784",
            "warehouse_code": null,
            "label_name": "Maria Rodriguez",
            "service_type": "AIR",
            "intake_weight_lbs": "5.50",
            "verified_weight_lbs": null,
            "status": "RECEIVED_MIAMI",
            "received_nic_at": null,
            "ready_at": null,
            "agency_id": null,
            "agency": null,
            "is_preregistration": true,
            "display_label": "PREREGISTRO"
        }
    ],
    "per_page": 15,
    "total": 2
}
```

**Campos importantes:**
- `code_to_scan`: Código para escanear (warehouse_code si existe, sino tracking_external)
- `is_preregistration`: `true` si status es `RECEIVED_MIAMI` o `IN_TRANSIT`
- `display_label`: "PREREGISTRO" si es preregistro, "" si no

---

### 2. Ver Detalle de Paquete
**GET** `/api/packages/{id}`

**Ejemplo:**
```
GET /api/packages/1
```

**Response (200):**
```json
{
    "id": 1,
    "intake_type": "DROP_OFF",
    "tracking_external": null,
    "warehouse_code": "000001",
    "label_name": "Juan Perez",
    "service_type": "AIR",
    "intake_weight_lbs": "3.20",
    "verified_weight_lbs": "3.25",
    "status": "READY",
    "received_nic_at": "2026-02-03T04:00:00.000000Z",
    "ready_at": "2026-02-03T05:00:00.000000Z",
    "agency_id": 1,
    "agency": {
        "id": 1,
        "name": "Agencia Test 1"
    },
    "photos": [
        {
            "id": 1,
            "path": "preregistrations/2026/02/uuid.jpg",
            "url": "http://localhost/storage/preregistrations/2026/02/uuid.jpg",
            "created_at": "2026-02-03T02:25:00.000000Z"
        }
    ],
    "consolidation": {
        "code": "SAC-202602-0001",
        "status": "SENT"
    },
    "is_preregistration": false,
    "display_label": ""
}
```

---

### 3. Procesar Paquete
**POST** `/api/packages/{id}/process`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "agency_id": 1,
    "verified_weight_lbs": 5.25
}
```

**Notas:**
- `agency_id`: **Requerido**. Debe existir en la tabla `agencies`
- `verified_weight_lbs`: **Requerido**. Debe ser > 0

**Lógica de Procesamiento:**
1. Valida que `status = 'IN_WAREHOUSE_NIC'`
2. Si `warehouse_code` es `null` → genera uno nuevo (6 dígitos) usando `warehouse_sequences`
3. Asigna `agency_id`
4. Guarda `verified_weight_lbs`
5. Establece `ready_at = now()`
6. Incrementa `label_print_count` y establece `label_last_printed_at = now()`
7. Cambia `status` a `READY`

**Response (200):**
```json
{
    "message": "Paquete procesado exitosamente.",
    "package": {
        "id": 1,
        "code_to_scan": "000001",
        "tracking_external": null,
        "warehouse_code": "000001",
        "label_name": "Juan Perez",
        "service_type": "AIR",
        "intake_weight_lbs": "3.20",
        "verified_weight_lbs": "5.25",
        "status": "READY",
        "received_nic_at": "2026-02-03T04:00:00.000000Z",
        "ready_at": "2026-02-03T05:00:00.000000Z",
        "agency_id": 1,
        "agency": {
            "id": 1,
            "name": "Agencia Test 1"
        },
        "is_preregistration": false,
        "display_label": ""
    }
}
```

**Errores:**
- **400**: "Solo se pueden procesar paquetes con estado IN_WAREHOUSE_NIC"
- **422**: Errores de validación (agency_id no existe, verified_weight_lbs inválido)

---

### 4. Reimprimir Etiqueta
**POST** `/api/packages/{id}/reprint-label`

**Headers:**
```
Accept: application/json
```

**Notas:**
- Solo funciona si el paquete tiene `warehouse_code`
- No cambia el estado del paquete
- Solo incrementa `label_print_count` y actualiza `label_last_printed_at`

**Response (200):**
```json
{
    "message": "Etiqueta reimpresa exitosamente.",
    "package": {
        "id": 1,
        "code_to_scan": "000001",
        "warehouse_code": "000001",
        "label_name": "Juan Perez",
        "status": "READY",
        "label_print_count": 2,
        "label_last_printed_at": "2026-02-03T06:00:00.000000Z",
        ...
    }
}
```

**Errores:**
- **400**: "No se puede reimprimir la etiqueta: el paquete no tiene warehouse_code"

---

## Reglas de Negocio

### Estados del Paquete
- **RECEIVED_MIAMI**: Preregistrado en Miami → `is_preregistration = true`, `display_label = "PREREGISTRO"`
- **IN_TRANSIT**: En tránsito hacia Nicaragua → `is_preregistration = true`, `display_label = "PREREGISTRO"`
- **IN_WAREHOUSE_NIC**: Recibido en almacén Nicaragua (después de escanear) → `is_preregistration = false`
- **READY**: Procesado y listo para retiro → `is_preregistration = false`
- **DELIVERED**: Entregado → `is_preregistration = false`
- **CANCELLED**: Cancelado → `is_preregistration = false`

### Procesamiento
- **Solo se puede procesar** si `status = 'IN_WAREHOUSE_NIC'`
- **No se puede procesar** si está en `RECEIVED_MIAMI` o `IN_TRANSIT` (preregistro)
- **No se puede procesar** si ya está `READY` o `DELIVERED`
- Si no tiene `warehouse_code`, se genera automáticamente durante el procesamiento

### Código para Escanear
- `code_to_scan`: Muestra `warehouse_code` si existe, sino `tracking_external`
- Si no tiene ninguno, el paquete solo se puede mostrar (no escanear ni procesar)

---

## Ejemplos Postman

### Collection JSON para importar:

```json
{
    "info": {
        "name": "Paquetes API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Listar Paquetes",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/packages?status=IN_WAREHOUSE_NIC",
                    "host": ["{{base_url}}"],
                    "path": ["api", "packages"],
                    "query": [
                        {
                            "key": "status",
                            "value": "IN_WAREHOUSE_NIC"
                        }
                    ]
                }
            }
        },
        {
            "name": "Listar Paquetes con Búsqueda",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/packages?search=Juan&status[]=IN_WAREHOUSE_NIC&status[]=READY",
                    "host": ["{{base_url}}"],
                    "path": ["api", "packages"],
                    "query": [
                        {
                            "key": "search",
                            "value": "Juan"
                        },
                        {
                            "key": "status[]",
                            "value": "IN_WAREHOUSE_NIC"
                        },
                        {
                            "key": "status[]",
                            "value": "READY"
                        }
                    ]
                }
            }
        },
        {
            "name": "Ver Detalle de Paquete",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/packages/1",
                    "host": ["{{base_url}}"],
                    "path": ["api", "packages", "1"]
                }
            }
        },
        {
            "name": "Procesar Paquete",
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
                    "raw": "{\n    \"agency_id\": 1,\n    \"verified_weight_lbs\": 5.25\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/packages/1/process",
                    "host": ["{{base_url}}"],
                    "path": ["api", "packages", "1", "process"]
                }
            }
        },
        {
            "name": "Reimprimir Etiqueta",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/packages/1/reprint-label",
                    "host": ["{{base_url}}"],
                    "path": ["api", "packages", "1", "reprint-label"]
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

## Flujo de Trabajo

1. **Preregistro en Miami**: Se crea preregistro
   - DROP_OFF → se genera `warehouse_code` automáticamente
   - COURIER → se usa `tracking_external`
   - Estado: `RECEIVED_MIAMI` → `is_preregistration = true`, `display_label = "PREREGISTRO"`

2. **Consolidación y Envío**: Se agrupa en saco y se envía
   - Estado: `IN_TRANSIT` → `is_preregistration = true`, `display_label = "PREREGISTRO"`

3. **Recepción en Nicaragua**: Se escanea cuando llega el saco
   - Estado: `IN_WAREHOUSE_NIC` → `is_preregistration = false`, `display_label = ""`
   - Se registra `received_nic_at`

4. **Procesamiento**: Se asigna agencia y se verifica peso
   - Se genera `warehouse_code` si no existe
   - Se asigna `agency_id`
   - Se guarda `verified_weight_lbs`
   - Estado: `READY` → `is_preregistration = false`
   - Se registra `ready_at` y se imprime etiqueta

5. **Reimpresión**: Se puede reimprimir la etiqueta sin cambiar estado
   - Solo incrementa contador de impresiones

---

## Validaciones y Errores

### Errores Comunes

1. **"Solo se pueden procesar paquetes con estado IN_WAREHOUSE_NIC"**
   - Solución: El paquete debe estar en almacén de Nicaragua (ya escaneado)

2. **"No se puede reimprimir la etiqueta: el paquete no tiene warehouse_code"**
   - Solución: El paquete debe tener `warehouse_code` (se genera automáticamente al procesar)

3. **"The agency id field is required"**
   - Solución: Debes proporcionar un `agency_id` válido

4. **"The verified weight lbs field is required"**
   - Solución: Debes proporcionar un peso verificado > 0

---

## Notas Importantes

1. **Listado Unificado**: El endpoint `/api/packages` muestra tanto preregistros como paquetes físicos
2. **Indicadores**: `is_preregistration` y `display_label` ayudan a distinguir visualmente
3. **Generación automática**: `warehouse_code` se genera automáticamente si no existe al procesar
4. **Sin autenticación**: Este módulo no tiene autenticación por ahora
5. **Transacciones**: Las operaciones de procesamiento usan transacciones de base de datos
6. **Paginación**: El listado está paginado (15 items por página por defecto)

---

## Estructura de Datos

### Agency (nuevo)
- `id`: PK
- `name`: Nombre de la agencia
- `code`: Código único (opcional)
- `contact_name`, `contact_phone`, `contact_email`: Datos de contacto
- `active`: Boolean

### Preregistration (actualizado)
- `agency_id`: FK a agencies (nullable)
- `verified_weight_lbs`: Peso verificado en Nicaragua
- `ready_at`: Fecha de procesamiento
- `label_print_count`: Contador de impresiones
- `label_last_printed_at`: Última fecha de impresión

