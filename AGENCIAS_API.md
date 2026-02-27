# Módulo: AGENCIAS + CLIENTES INTERNOS + HOLDING - Documentación API

## Comandos de Setup

### 1. Ejecutar migraciones
```bash
php artisan migrate
```

Esto creará/actualizará:
- Tabla `agencies` con código de 4 dígitos
- Tabla `agency_clients` (clientes internos por agencia)
- Campos de asignación en `preregistrations`:
  - `agency_id` (FK a agencies)
  - `agency_client_id` (FK a agency_clients)
  - `assignment_status` (ENUM: 'ASSIGNED', 'HOLDING')
  - `holding_reason` (text nullable)

### 2. Verificar rutas API
```bash
php artisan route:list --path=api/agencies
php artisan route:list --path=api/agency-clients
php artisan route:list --path=api/packages
```

## Endpoints API

Base URL: `http://localhost/api` (o tu dominio)

---

## AGENCIAS CRUD

### 1. Crear Agencia
**POST** `/api/agencies`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "code": "0001",
    "name": "Agencia Test 1",
    "phone": "+505 1234-5678"
}
```

**Validaciones:**
- `code`: **Requerido**. String de exactamente 4 dígitos (regex: `/^\d{4}$/`)
- `name`: **Requerido**. String máximo 255 caracteres
- `phone`: Opcional. String máximo 255 caracteres

**Response (201):**
```json
{
    "id": 1,
    "code": "0001",
    "name": "Agencia Test 1",
    "phone": "+505 1234-5678",
    "is_active": true,
    "created_at": "2026-02-03T04:36:00.000000Z",
    "updated_at": "2026-02-03T04:36:00.000000Z"
}
```

---

### 2. Listar Agencias
**GET** `/api/agencies`

**Query Parameters (opcionales):**
- `is_active`: Boolean (true/false)
- `search`: Busca en `name` o `code`

**Ejemplo:**
```
GET /api/agencies?is_active=true&search=Test
```

**Response (200):**
```json
[
    {
        "id": 1,
        "code": "0001",
        "name": "Agencia Test 1",
        "phone": "+505 1234-5678",
        "is_active": true,
        "created_at": "2026-02-03T04:36:00.000000Z",
        "updated_at": "2026-02-03T04:36:00.000000Z"
    }
]
```

---

### 3. Ver Detalle de Agencia
**GET** `/api/agencies/{id}`

**Response (200):**
```json
{
    "id": 1,
    "code": "0001",
    "name": "Agencia Test 1",
    "phone": "+505 1234-5678",
    "is_active": true,
    "clients": [
        {
            "id": 1,
            "agency_id": 1,
            "full_name": "Juan Perez",
            "phone": "+505 8765-4321",
            "is_active": true
        }
    ],
    "created_at": "2026-02-03T04:36:00.000000Z",
    "updated_at": "2026-02-03T04:36:00.000000Z"
}
```

---

### 4. Actualizar Agencia
**PUT** `/api/agencies/{id}`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "code": "0001",
    "name": "Agencia Test 1 Actualizada",
    "phone": "+505 1234-5678",
    "is_active": true
}
```

**Notas:**
- Todos los campos son opcionales (usar `sometimes` en validación)
- `code` debe ser único (excepto el mismo registro)

**Response (200):**
```json
{
    "id": 1,
    "code": "0001",
    "name": "Agencia Test 1 Actualizada",
    "phone": "+505 1234-5678",
    "is_active": true,
    "created_at": "2026-02-03T04:36:00.000000Z",
    "updated_at": "2026-02-03T04:36:07.000000Z"
}
```

---

### 5. Toggle Estado Activo
**PATCH** `/api/agencies/{id}/toggle`

**Response (200):**
```json
{
    "message": "Estado actualizado exitosamente.",
    "agency": {
        "id": 1,
        "code": "0001",
        "name": "Agencia Test 1",
        "is_active": false,
        ...
    }
}
```

---

## CLIENTES INTERNOS

### 1. Crear Cliente Interno
**POST** `/api/agencies/{agency_id}/clients`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "full_name": "Juan Perez",
    "phone": "+505 8765-4321"
}
```

**Validaciones:**
- `full_name`: **Requerido**. String máximo 255 caracteres
- `phone`: Opcional. String máximo 255 caracteres

**Response (201):**
```json
{
    "id": 1,
    "agency_id": 1,
    "full_name": "Juan Perez",
    "phone": "+505 8765-4321",
    "is_active": true,
    "created_at": "2026-02-03T04:40:00.000000Z",
    "updated_at": "2026-02-03T04:40:00.000000Z"
}
```

---

### 2. Listar Clientes de una Agencia
**GET** `/api/agencies/{agency_id}/clients`

**Query Parameters (opcionales):**
- `is_active`: Boolean (true/false)
- `search`: Busca en `full_name`

**Ejemplo:**
```
GET /api/agencies/1/clients?is_active=true&search=Juan
```

**Response (200):**
```json
[
    {
        "id": 1,
        "agency_id": 1,
        "full_name": "Juan Perez",
        "phone": "+505 8765-4321",
        "is_active": true,
        "created_at": "2026-02-03T04:40:00.000000Z",
        "updated_at": "2026-02-03T04:40:00.000000Z"
    }
]
```

---

### 3. Ver Detalle de Cliente
**GET** `/api/agency-clients/{id}`

**Response (200):**
```json
{
    "id": 1,
    "agency_id": 1,
    "full_name": "Juan Perez",
    "phone": "+505 8765-4321",
    "is_active": true,
    "agency": {
        "id": 1,
        "code": "0001",
        "name": "Agencia Test 1"
    },
    "created_at": "2026-02-03T04:40:00.000000Z",
    "updated_at": "2026-02-03T04:40:00.000000Z"
}
```

---

### 4. Actualizar Cliente
**PUT** `/api/agency-clients/{id}`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "full_name": "Juan Perez Actualizado",
    "phone": "+505 8765-4321",
    "is_active": true
}
```

**Response (200):**
```json
{
    "id": 1,
    "agency_id": 1,
    "full_name": "Juan Perez Actualizado",
    "phone": "+505 8765-4321",
    "is_active": true,
    ...
}
```

---

### 5. Toggle Estado Activo
**PATCH** `/api/agency-clients/{id}/toggle`

**Response (200):**
```json
{
    "message": "Estado actualizado exitosamente.",
    "client": {
        "id": 1,
        "is_active": false,
        ...
    }
}
```

---

## HOLDING (ASIGNACIÓN)

### 1. Marcar Paquete en HOLDING
**POST** `/api/packages/{id}/mark-holding`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "holding_reason": "Cliente no encontrado, requiere verificación"
}
```

**Validaciones:**
- `holding_reason`: **Requerido**. String máximo 1000 caracteres

**Reglas:**
- Solo se puede marcar si `preregistration.status = 'IN_WAREHOUSE_NIC'`
- Cambia `assignment_status` a `'HOLDING'`
- Guarda `holding_reason`
- **NO cambia** el `status` (ubicación)

**Response (200):**
```json
{
    "message": "Paquete marcado en HOLDING exitosamente.",
    "package": {
        "id": 1,
        "status": "IN_WAREHOUSE_NIC",
        "assignment_status": "HOLDING",
        "holding_reason": "Cliente no encontrado, requiere verificación",
        ...
    }
}
```

**Errores:**
- **400**: "Solo se pueden marcar en HOLDING paquetes con estado IN_WAREHOUSE_NIC"

---

### 2. Resolver HOLDING y Asignar
**POST** `/api/packages/{id}/resolve-holding`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "agency_id": 1,
    "agency_client_id": 2
}
```

**Validaciones:**
- `agency_id`: **Requerido**. Debe existir en `agencies`
- `agency_client_id`: Opcional. Si se proporciona, debe pertenecer a la `agency_id` especificada

**Reglas:**
- Solo se puede resolver si `assignment_status = 'HOLDING'`
- Asigna `agency_id` y opcionalmente `agency_client_id`
- Cambia `assignment_status` a `'ASSIGNED'`
- Limpia `holding_reason` (null)
- **NO cambia** el `status` (ubicación)

**Response (200):**
```json
{
    "message": "HOLDING resuelto y paquete asignado exitosamente.",
    "package": {
        "id": 1,
        "status": "IN_WAREHOUSE_NIC",
        "assignment_status": "ASSIGNED",
        "holding_reason": null,
        "agency_id": 1,
        "agency_client_id": 2,
        ...
    }
}
```

**Errores:**
- **400**: "El paquete no está en estado HOLDING"
- **422**: "El cliente no pertenece a la agencia seleccionada"

---

## Reglas de Negocio

### Estados de Ubicación vs Asignación
- **Status (Ubicación)**: Controla la ubicación física del paquete
  - `RECEIVED_MIAMI`, `IN_TRANSIT`, `IN_WAREHOUSE_NIC`, `READY`, `DELIVERED`, `CANCELLED`
- **Assignment Status (Asignación)**: Controla la asignación a agencia
  - `ASSIGNED`: Asignado a una agencia
  - `HOLDING`: En espera, no asignado aún

### Integridad de Datos
- Si `agency_client_id` se proporciona, debe pertenecer a la misma `agency_id`
- Esta validación se hace en `ResolveHoldingRequest`

### HOLDING
- Solo se puede marcar en HOLDING si el paquete está `IN_WAREHOUSE_NIC`
- HOLDING no cambia el estado de ubicación, solo el de asignación
- Al resolver HOLDING, se asigna la agencia y opcionalmente el cliente interno

---

## Ejemplos Postman

### Collection JSON para importar:

```json
{
    "info": {
        "name": "Agencias y HOLDING API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Agencias",
            "item": [
                {
                    "name": "Crear Agencia",
                    "request": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Content-Type",
                                "value": "application/json"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": "{\n    \"code\": \"0001\",\n    \"name\": \"Agencia Test 1\",\n    \"phone\": \"+505 1234-5678\"\n}"
                        },
                        "url": {
                            "raw": "{{base_url}}/api/agencies",
                            "host": ["{{base_url}}"],
                            "path": ["api", "agencies"]
                        }
                    }
                },
                {
                    "name": "Listar Agencias",
                    "request": {
                        "method": "GET",
                        "url": {
                            "raw": "{{base_url}}/api/agencies?is_active=true",
                            "host": ["{{base_url}}"],
                            "path": ["api", "agencies"],
                            "query": [
                                {
                                    "key": "is_active",
                                    "value": "true"
                                }
                            ]
                        }
                    }
                },
                {
                    "name": "Actualizar Agencia",
                    "request": {
                        "method": "PUT",
                        "header": [
                            {
                                "key": "Content-Type",
                                "value": "application/json"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": "{\n    \"name\": \"Agencia Actualizada\"\n}"
                        },
                        "url": {
                            "raw": "{{base_url}}/api/agencies/1",
                            "host": ["{{base_url}}"],
                            "path": ["api", "agencies", "1"]
                        }
                    }
                },
                {
                    "name": "Toggle Agencia",
                    "request": {
                        "method": "PATCH",
                        "url": {
                            "raw": "{{base_url}}/api/agencies/1/toggle",
                            "host": ["{{base_url}}"],
                            "path": ["api", "agencies", "1", "toggle"]
                        }
                    }
                }
            ]
        },
        {
            "name": "Clientes Internos",
            "item": [
                {
                    "name": "Crear Cliente",
                    "request": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Content-Type",
                                "value": "application/json"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": "{\n    \"full_name\": \"Juan Perez\",\n    \"phone\": \"+505 8765-4321\"\n}"
                        },
                        "url": {
                            "raw": "{{base_url}}/api/agencies/1/clients",
                            "host": ["{{base_url}}"],
                            "path": ["api", "agencies", "1", "clients"]
                        }
                    }
                },
                {
                    "name": "Listar Clientes",
                    "request": {
                        "method": "GET",
                        "url": {
                            "raw": "{{base_url}}/api/agencies/1/clients",
                            "host": ["{{base_url}}"],
                            "path": ["api", "agencies", "1", "clients"]
                        }
                    }
                }
            ]
        },
        {
            "name": "HOLDING",
            "item": [
                {
                    "name": "Marcar en HOLDING",
                    "request": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Content-Type",
                                "value": "application/json"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": "{\n    \"holding_reason\": \"Cliente no encontrado, requiere verificación\"\n}"
                        },
                        "url": {
                            "raw": "{{base_url}}/api/packages/1/mark-holding",
                            "host": ["{{base_url}}"],
                            "path": ["api", "packages", "1", "mark-holding"]
                        }
                    }
                },
                {
                    "name": "Resolver HOLDING",
                    "request": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Content-Type",
                                "value": "application/json"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": "{\n    \"agency_id\": 1,\n    \"agency_client_id\": 2\n}"
                        },
                        "url": {
                            "raw": "{{base_url}}/api/packages/1/resolve-holding",
                            "host": ["{{base_url}}"],
                            "path": ["api", "packages", "1", "resolve-holding"]
                        }
                    }
                }
            ]
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

1. **Código de 4 dígitos**: El código de agencia debe ser exactamente 4 dígitos numéricos (0001, 0002, etc.)
2. **Sin DELETE**: No hay endpoints DELETE para agencies ni clients, solo toggle de `is_active`
3. **HOLDING separado**: HOLDING es un estado de asignación, no de ubicación
4. **Validación de integridad**: Si se proporciona `agency_client_id`, debe pertenecer a la `agency_id` especificada
5. **Sin autenticación**: Este módulo no tiene autenticación por ahora

---

## Estructura de Datos

### Agency
- `id`: PK
- `code`: CHAR(4) UNIQUE (4 dígitos)
- `name`: String
- `phone`: String nullable
- `is_active`: Boolean

### AgencyClient
- `id`: PK
- `agency_id`: FK a agencies
- `full_name`: String
- `phone`: String nullable
- `is_active`: Boolean

### Preregistration (actualizado)
- `agency_id`: FK a agencies (nullable)
- `agency_client_id`: FK a agency_clients (nullable)
- `assignment_status`: ENUM('ASSIGNED', 'HOLDING')
- `holding_reason`: Text nullable

