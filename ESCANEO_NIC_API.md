# Módulo: ESCANEO DE SACOS (Recepción Nicaragua) - Documentación API

## Comandos de Setup

### 1. Ejecutar migraciones
```bash
php artisan migrate
```

### 2. Ejecutar seeder
```bash
php artisan db:seed --class=WarehouseSequenceSeeder
```

Esto creará:
- Campos `warehouse_code` y `received_nic_at` en `preregistrations`
- Tabla `warehouse_sequences` con registro inicial

### 3. Verificar rutas API
```bash
php artisan route:list --path=api/nic
```

## Cambios en el Sistema

### Generación Automática de Warehouse Code

**Para DROP_OFF:**
- Cuando se crea un preregistro con `intake_type='DROP_OFF'`, se genera automáticamente un `warehouse_code` de 6 dígitos (000001, 000002, etc.)
- El código se genera usando la tabla `warehouse_sequences` en una transacción para garantizar unicidad

**Para COURIER:**
- El `warehouse_code` queda en `null`
- Se identifica por `tracking_external`

## Endpoints API

Base URL: `http://localhost/api` (o tu dominio)

### 1. Escanear Paquete en Consolidación
**POST** `/api/nic/consolidations/{id}/scan`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "code": "000001"
}
```

**Notas:**
- `code`: **Requerido**. Puede ser:
  - **6 dígitos** (ej: "000001") → busca por `warehouse_code`
  - **Tracking externo** (ej: "1Z999AA10123456784") → busca por `tracking_external`

**Lógica de Escaneo:**
1. Valida que la consolidación esté en estado `SENT`
2. Determina el preregistro:
   - Si el código tiene 6 dígitos → busca por `warehouse_code`
   - Si no → busca por `tracking_external`
3. Valida que el preregistro pertenezca al saco
4. Valida que el preregistro esté en estado `IN_TRANSIT`
5. Si ya fue escaneado → responde "YA ESCANEADO"
6. Si no:
   - Actualiza `consolidation_item.scanned_at = now()`
   - Actualiza `preregistration.status = 'IN_WAREHOUSE_NIC'`
   - Actualiza `preregistration.received_nic_at = now()`

**Response (200) - Escaneo Exitoso:**
```json
{
    "message": "Paquete escaneado exitosamente.",
    "preregistration": {
        "id": 1,
        "code": "000001",
        "label_name": "Juan Perez",
        "status": "IN_WAREHOUSE_NIC"
    },
    "summary": {
        "total_items": 10,
        "scanned_count": 5,
        "missing_count": 5
    }
}
```

**Response (200) - Ya Escaneado:**
```json
{
    "message": "YA ESCANEADO",
    "preregistration": {
        "id": 1,
        "code": "000001",
        "label_name": "Juan Perez",
        "status": "IN_WAREHOUSE_NIC"
    }
}
```

**Errores:**
- **400**: Consolidación no está en estado `SENT`
- **404**: Preregistro no encontrado con el código proporcionado
- **400**: Preregistro no pertenece a esta consolidación
- **400**: Preregistro no está en estado `IN_TRANSIT`

---

### 2. Obtener Resumen de Consolidación
**GET** `/api/nic/consolidations/{id}/summary`

**Headers:**
```
Accept: application/json
```

**Ejemplo:**
```
GET /api/nic/consolidations/1/summary
```

**Response (200):**
```json
{
    "consolidation": {
        "id": 1,
        "code": "SAC-202602-0001",
        "service_type": "AIR",
        "status": "SENT"
    },
    "total_items": 10,
    "scanned_count": 7,
    "missing_count": 3,
    "list_missing": [
        {
            "code": "000008",
            "label_name": "Maria Rodriguez"
        },
        {
            "code": "1Z999AA10123456784",
            "label_name": "Carlos Lopez"
        },
        {
            "code": "000010",
            "label_name": "Ana Martinez"
        }
    ]
}
```

**Notas:**
- `list_missing`: Lista de paquetes que aún no han sido escaneados
- `code`: Muestra `warehouse_code` si existe, sino `tracking_external`

---

## Reglas de Negocio

### Estados de Preregistro
- **RECEIVED_MIAMI**: Preregistrado en Miami
- **IN_TRANSIT**: En tránsito hacia Nicaragua (automático al enviar saco)
- **IN_WAREHOUSE_NIC**: Recibido en almacén de Nicaragua (automático al escanear)
- **READY**: Listo para retiro
- **DELIVERED**: Entregado
- **CANCELLED**: Cancelado

### Validaciones de Escaneo
1. **Consolidación debe estar SENT**: Solo se pueden escanear paquetes de sacos enviados
2. **Preregistro debe estar IN_TRANSIT**: Solo se pueden escanear paquetes en tránsito
3. **Preregistro debe pertenecer al saco**: Validación de integridad
4. **No se puede escanear dos veces**: Si `scanned_at` ya tiene valor, responde "YA ESCANEADO"

### Códigos de Identificación
- **DROP_OFF**: Usa `warehouse_code` (6 dígitos, generado automáticamente)
- **COURIER**: Usa `tracking_external` (código del courier)

---

## Ejemplos Postman

### Collection JSON para importar:

```json
{
    "info": {
        "name": "Escaneo Nicaragua API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Escanear Paquete (Warehouse Code)",
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
                    "raw": "{\n    \"code\": \"000001\"\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/nic/consolidations/1/scan",
                    "host": ["{{base_url}}"],
                    "path": ["api", "nic", "consolidations", "1", "scan"]
                }
            }
        },
        {
            "name": "Escanear Paquete (Tracking)",
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
                    "raw": "{\n    \"code\": \"1Z999AA10123456784\"\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/nic/consolidations/1/scan",
                    "host": ["{{base_url}}"],
                    "path": ["api", "nic", "consolidations", "1", "scan"]
                }
            }
        },
        {
            "name": "Obtener Resumen",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/nic/consolidations/1/summary",
                    "host": ["{{base_url}}"],
                    "path": ["api", "nic", "consolidations", "1", "summary"]
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

1. **En Miami**: Se crean preregistros
   - DROP_OFF → se genera automáticamente `warehouse_code` (000001, 000002, etc.)
   - COURIER → se usa `tracking_external`

2. **Consolidación**: Se agrupan preregistros en sacos y se envían
   - Al enviar, todos los preregistros pasan a `IN_TRANSIT`

3. **En Nicaragua**: Se escanean los paquetes cuando llegan
   - Se escanea por `warehouse_code` (6 dígitos) o `tracking_external`
   - Al escanear, el preregistro pasa a `IN_WAREHOUSE_NIC`
   - Se registra `received_nic_at`

4. **Seguimiento**: Se puede consultar el resumen del saco
   - Ver cuántos paquetes faltan por escanear
   - Ver lista de paquetes faltantes

---

## Validaciones y Errores

### Errores Comunes

1. **"Solo se pueden escanear paquetes de consolidaciones con estado SENT"**
   - Solución: Asegúrate de que el saco haya sido enviado primero

2. **"Preregistro no encontrado con el código proporcionado"**
   - Solución: Verifica que el código sea correcto (6 dígitos para DROP_OFF, tracking para COURIER)

3. **"Este preregistro no pertenece a esta consolidación"**
   - Solución: El paquete no está en este saco, verifica la consolidación correcta

4. **"El preregistro no está en estado IN_TRANSIT"**
   - Solución: El paquete debe estar en tránsito para poder escanearlo

5. **"YA ESCANEADO"**
   - No es un error, es información. El paquete ya fue escaneado previamente

---

## Notas Importantes

1. **Generación automática de warehouse_code**: Solo para DROP_OFF, se genera en transacción para garantizar unicidad
2. **Sin autenticación**: Este módulo no tiene autenticación por ahora
3. **Transacciones**: Las operaciones de escaneo usan transacciones de base de datos
4. **Código preferido**: En el resumen, se muestra `warehouse_code` si existe, sino `tracking_external`

---

## Estructura de Datos

### Preregistration (actualizado)
- `warehouse_code`: CHAR(6) nullable UNIQUE (solo para DROP_OFF)
- `received_nic_at`: datetime nullable (se establece al escanear)

### ConsolidationItem (existente)
- `scanned_at`: datetime nullable (se establece al escanear)

### WarehouseSequence (nuevo)
- `id`: siempre 1
- `next_number`: int (incrementa automáticamente)

