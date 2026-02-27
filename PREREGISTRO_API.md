# Módulo 1: PREREGISTRO + FOTOS - Documentación API

## Comandos de Setup

### 1. Ejecutar migraciones
```bash
php artisan migrate
```

### 2. Crear enlace simbólico para storage público
```bash
php artisan storage:link
```
Este comando crea un enlace simbólico desde `storage/app/public` a `public/storage`, permitiendo que las imágenes sean accesibles públicamente.

### 3. Verificar rutas API
```bash
php artisan route:list --path=api
```

## Endpoints API

Base URL: `http://localhost/api` (o tu dominio)

### 1. Crear Preregistro
**POST** `/api/preregistrations`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
    "intake_type": "COURIER",
    "tracking_external": "1Z999AA10123456784",
    "label_name": "Juan Perez",
    "service_type": "AIR",
    "intake_weight_lbs": 3.2
}
```

**Notas:**
- `intake_type`: Opcional, default `COURIER`. Valores: `COURIER`, `DROP_OFF`
- `tracking_external`: **Requerido si `intake_type` es `COURIER`**, nullable si es `DROP_OFF`
- `label_name`: **Requerido**
- `service_type`: **Requerido**. Valores: `AIR`, `SEA`
- `intake_weight_lbs`: **Requerido**. Decimal (máx 999999.99)

**Response (201):**
```json
{
    "id": 1,
    "intake_type": "COURIER",
    "tracking_external": "1Z999AA10123456784",
    "label_name": "Juan Perez",
    "service_type": "AIR",
    "intake_weight_lbs": "3.20",
    "status": "RECEIVED_MIAMI",
    "created_at": "2026-02-03T02:21:43.000000Z",
    "updated_at": "2026-02-03T02:21:43.000000Z",
    "photos": []
}
```

---

### 2. Listar Preregistros (con filtros opcionales)
**GET** `/api/preregistrations`

**Query Parameters (opcionales):**
- `service_type`: `AIR` o `SEA`
- `intake_type`: `COURIER` o `DROP_OFF`
- `date_from`: `YYYY-MM-DD`
- `date_to`: `YYYY-MM-DD`

**Ejemplo:**
```
GET /api/preregistrations?service_type=AIR&date_from=2026-02-01&date_to=2026-02-03
```

**Response (200):**
```json
[
    {
        "id": 1,
        "intake_type": "COURIER",
        "tracking_external": "1Z999AA10123456784",
        "label_name": "Juan Perez",
        "service_type": "AIR",
        "intake_weight_lbs": "3.20",
        "status": "RECEIVED_MIAMI",
        "created_at": "2026-02-03T02:21:43.000000Z",
        "updated_at": "2026-02-03T02:21:43.000000Z",
        "photos": [
            {
                "id": 1,
                "preregistration_id": 1,
                "path": "preregistrations/2026/02/uuid.jpg",
                "mime": "image/jpeg",
                "size_bytes": 245678,
                "url": "http://localhost/storage/preregistrations/2026/02/uuid.jpg",
                "created_at": "2026-02-03T02:25:00.000000Z",
                "updated_at": "2026-02-03T02:25:00.000000Z"
            }
        ]
    }
]
```

---

### 3. Ver Detalle de Preregistro
**GET** `/api/preregistrations/{id}`

**Ejemplo:**
```
GET /api/preregistrations/1
```

**Response (200):**
```json
{
    "id": 1,
    "intake_type": "COURIER",
    "tracking_external": "1Z999AA10123456784",
    "label_name": "Juan Perez",
    "service_type": "AIR",
    "intake_weight_lbs": "3.20",
    "status": "RECEIVED_MIAMI",
    "created_at": "2026-02-03T02:21:43.000000Z",
    "updated_at": "2026-02-03T02:21:43.000000Z",
    "photos": [
        {
            "id": 1,
            "preregistration_id": 1,
            "path": "preregistrations/2026/02/uuid.jpg",
            "mime": "image/jpeg",
            "size_bytes": 245678,
            "url": "http://localhost/storage/preregistrations/2026/02/uuid.jpg",
            "created_at": "2026-02-03T02:25:00.000000Z",
            "updated_at": "2026-02-03T02:25:00.000000Z"
        }
    ]
}
```

---

### 4. Obtener Fotos de un Preregistro
**GET** `/api/preregistrations/{id}/photos`

**Ejemplo:**
```
GET /api/preregistrations/1/photos
```

**Response (200):**
```json
[
    {
        "id": 1,
        "path": "preregistrations/2026/02/uuid.jpg",
        "mime": "image/jpeg",
        "size_bytes": 245678,
        "url": "http://localhost/storage/preregistrations/2026/02/uuid.jpg",
        "created_at": "2026-02-03T02:25:00.000000Z",
        "updated_at": "2026-02-03T02:25:00.000000Z"
    },
    {
        "id": 2,
        "path": "preregistrations/2026/02/uuid2.png",
        "mime": "image/png",
        "size_bytes": 189234,
        "url": "http://localhost/storage/preregistrations/2026/02/uuid2.png",
        "created_at": "2026-02-03T02:26:00.000000Z",
        "updated_at": "2026-02-03T02:26:00.000000Z"
    }
]
```

---

### 5. Subir Foto a Preregistro
**POST** `/api/preregistrations/{id}/photos`

**Headers:**
```
Content-Type: multipart/form-data
Accept: application/json
```

**Body (form-data):**
- `photo`: Archivo de imagen (requerido)
  - Tipos permitidos: `jpg`, `jpeg`, `png`, `webp`
  - Tamaño máximo: 10MB (10240 KB)

**Ejemplo:**
```
POST /api/preregistrations/1/photos
```

**Response (201):**
```json
{
    "id": 1,
    "path": "preregistrations/2026/02/uuid.jpg",
    "url": "http://localhost/storage/preregistrations/2026/02/uuid.jpg"
}
```

**Errores posibles:**
- **400**: Si el preregistro ya tiene 10 fotos (máximo permitido)
- **422**: Si la validación falla (formato, tamaño, etc.)

---

## Ejemplos Postman

### Collection JSON para importar en Postman:

```json
{
    "info": {
        "name": "Preregistro API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Crear Preregistro",
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
                    "raw": "{\n    \"intake_type\": \"COURIER\",\n    \"tracking_external\": \"1Z999AA10123456784\",\n    \"label_name\": \"Juan Perez\",\n    \"service_type\": \"AIR\",\n    \"intake_weight_lbs\": 3.2\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/preregistrations",
                    "host": ["{{base_url}}"],
                    "path": ["api", "preregistrations"]
                }
            }
        },
        {
            "name": "Listar Preregistros",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/preregistrations?service_type=AIR",
                    "host": ["{{base_url}}"],
                    "path": ["api", "preregistrations"],
                    "query": [
                        {
                            "key": "service_type",
                            "value": "AIR"
                        }
                    ]
                }
            }
        },
        {
            "name": "Ver Preregistro",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/preregistrations/1",
                    "host": ["{{base_url}}"],
                    "path": ["api", "preregistrations", "1"]
                }
            }
        },
        {
            "name": "Obtener Fotos",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/api/preregistrations/1/photos",
                    "host": ["{{base_url}}"],
                    "path": ["api", "preregistrations", "1", "photos"]
                }
            }
        },
        {
            "name": "Subir Foto",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "form-data",
                    "form-data": [
                        {
                            "key": "photo",
                            "type": "file",
                            "src": []
                        }
                    ]
                },
                "url": {
                    "raw": "{{base_url}}/api/preregistrations/1/photos",
                    "host": ["{{base_url}}"],
                    "path": ["api", "preregistrations", "1", "photos"]
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

## Validaciones

### Preregistro:
- `label_name`: Requerido, string, máx 255 caracteres
- `service_type`: Requerido, debe ser `AIR` o `SEA`
- `intake_weight_lbs`: Requerido, numérico, min 0, máx 999999.99
- `tracking_external`: Requerido si `intake_type` es `COURIER`, nullable si es `DROP_OFF`
- `intake_type`: Opcional, default `COURIER`, valores: `COURIER`, `DROP_OFF`

### Foto:
- `photo`: Requerido, debe ser imagen
- Formatos permitidos: `jpg`, `jpeg`, `png`, `webp`
- Tamaño máximo: 10MB (10240 KB)
- Límite: Máximo 10 fotos por preregistro

## Estructura de Archivos

Las fotos se guardan en:
```
storage/app/public/preregistrations/YYYY/MM/uuid.extensión
```

Ejemplo:
```
storage/app/public/preregistrations/2026/02/550e8400-e29b-41d4-a716-446655440000.jpg
```

La URL pública será:
```
http://localhost/storage/preregistrations/2026/02/550e8400-e29b-41d4-a716-446655440000.jpg
```

## Notas Importantes

1. **Sin autenticación**: Este módulo no tiene autenticación por ahora, como se solicitó.
2. **Fotos obligatorias**: Aunque las fotos son obligatorias en el flujo, la API permite crear preregistros sin fotos inicialmente. Las fotos se suben después con el endpoint específico.
3. **Storage link**: Asegúrate de ejecutar `php artisan storage:link` para que las imágenes sean accesibles públicamente.
4. **Límite de fotos**: Cada preregistro puede tener máximo 10 fotos. Si intentas subir más, recibirás un error 400.

