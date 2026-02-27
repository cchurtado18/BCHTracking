# Módulo: ENTREGAS (Retiro por Escaneo de Warehouse) - Documentación API

## Comandos de Setup

### 1. Ejecutar migraciones
```bash
php artisan migrate
```

Esto creará:
- Tabla `deliveries` con campos:
  - `preregistration_id` (FK cascade)
  - `delivered_at` (datetime)
  - `delivered_to` (string)
  - `delivery_type` (ENUM: 'PICKUP', 'DELIVERY')
  - `notes` (text nullable)
  - Índices: `delivered_at`, `delivery_type`

### 2. Verificar rutas API
```bash
php artisan route:list --path=api/deliveries
```

## Endpoints API

Base URL: `http://localhost/api` (o tu dominio)

---

## ENTREGAS

### 1. Escanear y Entregar Paquete
**POST** `/api/deliveries/scan`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "warehouse_code": "123456",
    "delivered_to": "Juan Perez",
    "notes": "Entregado en recepción",
    "delivery_type": "PICKUP"
}
```

**Validaciones:**
- `warehouse_code`: **Requerido**. String de exactamente 6 dígitos (regex: `/^\d{6}$/`)
- `delivered_to`: **Requerido**. String máximo 255 caracteres
- `notes`: Opcional. String máximo 1000 caracteres
- `delivery_type`: Opcional. Valores: `PICKUP` (default) o `DELIVERY`

**Lógica:**
1. Busca preregistration por `warehouse_code`
2. Valida que `status = 'READY'`
3. Verifica que no haya sido entregado previamente
4. Crea registro en `deliveries`
5. Cambia `preregistration.status` a `'DELIVERED'`
6. Retorna datos del paquete y entrega

**Response (201):**
```json
{
    "message": "Paquete entregado exitosamente.",
    "delivery": {
        "id": 1,
        "delivered_at": "2026-02-03T04:45:00.000000Z",
        "delivered_to": "Juan Perez",
        "delivery_type": "PICKUP",
        "notes": "Entregado en recepción"
    },
    "package": {
        "id": 1,
        "warehouse_code": "123456",
        "label_name": "Maria Rodriguez",
        "status": "DELIVERED",
        "agency": {
            "id": 1,
            "code": "0001",
            "name": "Agencia Test 1"
        },
        "agency_client": {
            "id": 2,
            "full_name": "Juan Perez"
        }
    }
}
```

**Errores:**
- **404**: "Paquete no encontrado con el código de warehouse proporcionado"
- **400**: "El paquete no está listo para entrega. Estado actual: {status}"
- **400**: "Este paquete ya fue entregado anteriormente"

---

### 2. Listar Entregas
**GET** `/api/deliveries`

**Query Parameters (opcionales):**
- `date_from`: `YYYY-MM-DD` (filtra por `delivered_at`)
- `date_to`: `YYYY-MM-DD` (filtra por `delivered_at`)
- `agency_id`: Integer (filtra por agencia del paquete)
- `delivery_type`: `PICKUP` o `DELIVERY`
- `search`: Busca en `warehouse_code`, `label_name` o `delivered_to`
- `page`: Número de página (paginación)

**Ejemplo:**
```
GET /api/deliveries?date_from=2026-02-01&date_to=2026-02-28&agency_id=1&delivery_type=PICKUP
```

**Response (200):**
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "delivered_at": "2026-02-03T04:45:00.000000Z",
            "delivered_to": "Juan Perez",
            "delivery_type": "PICKUP",
            "notes": "Entregado en recepción",
            "package": {
                "id": 1,
                "warehouse_code": "123456",
                "label_name": "Maria Rodriguez",
                "status": "DELIVERED"
            },
            "agency": {
                "id": 1,
                "code": "0001",
                "name": "Agencia Test 1"
            },
            "agency_client": {
                "id": 2,
                "full_name": "Juan Perez"
            }
        }
    ],
    "per_page": 15,
    "total": 1
}
```

---

### 3. Ver Detalle de Entrega
**GET** `/api/deliveries/{id}`

**Ejemplo:**
```
GET /api/deliveries/1
```

**Response (200):**
```json
{
    "id": 1,
    "delivered_at": "2026-02-03T04:45:00.000000Z",
    "delivered_to": "Juan Perez",
    "delivery_type": "PICKUP",
    "notes": "Entregado en recepción",
    "package": {
        "id": 1,
        "warehouse_code": "123456",
        "tracking_external": null,
        "label_name": "Maria Rodriguez",
        "service_type": "AIR",
        "intake_weight_lbs": "3.20",
        "verified_weight_lbs": "3.25",
        "status": "DELIVERED"
    },
    "agency": {
        "id": 1,
        "code": "0001",
        "name": "Agencia Test 1",
        "phone": "+505 1234-5678"
    },
    "agency_client": {
        "id": 2,
        "full_name": "Juan Perez",
        "phone": "+505 8765-4321"
    }
}
```

---

## Reglas de Negocio

### Estados de Ubicación
- Solo paquetes con `status = 'READY'` se pueden entregar
- Al entregar, el `status` cambia automáticamente a `'DELIVERED'`
- No se puede entregar un paquete dos veces

### Warehouse Code
- Debe ser exactamente 6 dígitos numéricos
- Identifica de forma única el paquete para escanear

### Tipos de Entrega
- **PICKUP**: Retiro en almacén (default)
- **DELIVERY**: Entrega a domicilio

### Transacciones
- La creación de la entrega y el cambio de estado se hacen en una transacción
- Garantiza consistencia de datos

---

## Ejemplos Postman

### Collection JSON para importar:

```json
{
    "info": {
        "name": "Entregas API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Escanear y Entregar (PICKUP)",
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
                    "raw": "{\n    \"warehouse_code\": \"123456\",\n    \"delivered_to\": \"Juan Perez\",\n    \"notes\": \"Entregado en recepción\",\n    \"delivery_type\": \"PICKUP\"\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/deliveries/scan",
                    "host": ["{{base_url}}"],
                    "path": ["api", "deliveries", "scan"]
                }
            }
        },
        {
            "name": "Escanear y Entregar (DELIVERY)",
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
                    "raw": "{\n    \"warehouse_code\": \"123457\",\n    \"delivered_to\": \"Maria Rodriguez\",\n    \"notes\": \"Entregado en domicilio\",\n    \"delivery_type\": \"DELIVERY\"\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/deliveries/scan",
                    "host": ["{{base_url}}"],
                    "path": ["api", "deliveries", "scan"]
                }
            }
        },
        {
            "name": "Listar Entregas",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/deliveries?date_from=2026-02-01&date_to=2026-02-28",
                    "host": ["{{base_url}}"],
                    "path": ["api", "deliveries"],
                    "query": [
                        {
                            "key": "date_from",
                            "value": "2026-02-01"
                        },
                        {
                            "key": "date_to",
                            "value": "2026-02-28"
                        }
                    ]
                }
            }
        },
        {
            "name": "Listar Entregas por Agencia",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/deliveries?agency_id=1&delivery_type=PICKUP",
                    "host": ["{{base_url}}"],
                    "path": ["api", "deliveries"],
                    "query": [
                        {
                            "key": "agency_id",
                            "value": "1"
                        },
                        {
                            "key": "delivery_type",
                            "value": "PICKUP"
                        }
                    ]
                }
            }
        },
        {
            "name": "Buscar Entregas",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/deliveries?search=123456",
                    "host": ["{{base_url}}"],
                    "path": ["api", "deliveries"],
                    "query": [
                        {
                            "key": "search",
                            "value": "123456"
                        }
                    ]
                }
            }
        },
        {
            "name": "Ver Detalle de Entrega",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/deliveries/1",
                    "host": ["{{base_url}}"],
                    "path": ["api", "deliveries", "1"]
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

1. **Paquete en Almacén**: El paquete debe estar en estado `READY` (procesado y listo para retiro)

2. **Escaneo de Warehouse Code**: Se escanea el código de 6 dígitos del paquete

3. **Validación**: 
   - Verifica que el paquete existe
   - Verifica que está en estado `READY`
   - Verifica que no ha sido entregado previamente

4. **Registro de Entrega**: 
   - Se crea registro en `deliveries`
   - Se registra quién retiró (`delivered_to`)
   - Se registra tipo de entrega (`PICKUP` o `DELIVERY`)
   - Se pueden agregar notas

5. **Actualización de Estado**: 
   - El `status` del paquete cambia a `DELIVERED`
   - El paquete queda marcado como entregado

---

## Validaciones y Errores

### Errores Comunes

1. **"Paquete no encontrado con el código de warehouse proporcionado"**
   - Solución: Verifica que el `warehouse_code` sea correcto (6 dígitos)

2. **"El paquete no está listo para entrega. Estado actual: {status}"**
   - Solución: El paquete debe estar en estado `READY` antes de entregar

3. **"Este paquete ya fue entregado anteriormente"**
   - Solución: El paquete ya tiene un registro de entrega, no se puede entregar dos veces

4. **"The warehouse code field is required"**
   - Solución: Debes proporcionar un `warehouse_code` válido

5. **"The warehouse code must be 6 characters"**
   - Solución: El código debe ser exactamente 6 dígitos numéricos

---

## Notas Importantes

1. **Solo READY**: Solo se pueden entregar paquetes con estado `READY`
2. **Warehouse Code**: Debe ser exactamente 6 dígitos (validado con regex)
3. **Sin autenticación**: Este módulo no tiene autenticación por ahora
4. **Transacciones**: Las operaciones de entrega usan transacciones de base de datos
5. **Paginación**: El listado está paginado (15 items por página por defecto)
6. **No se puede entregar dos veces**: Validación previene entregas duplicadas

---

## Estructura de Datos

### Delivery
- `id`: PK
- `preregistration_id`: FK a preregistrations (cascade)
- `delivered_at`: datetime (se establece automáticamente)
- `delivered_to`: string (nombre de quien retira)
- `delivery_type`: ENUM('PICKUP', 'DELIVERY')
- `notes`: text nullable

### Preregistration (actualizado)
- Relación `hasOne` a `Delivery`
- Al entregar, `status` cambia a `'DELIVERED'`

---

## Integración con Otros Módulos

- **Agencias**: Las entregas muestran información de la agencia asignada
- **Clientes Internos**: Las entregas muestran información del cliente interno si existe
- **Paquetes**: El módulo de entregas completa el ciclo de vida del paquete

