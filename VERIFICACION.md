# Guía de Verificación del Módulo PREREGISTRO

## ✅ Verificación Rápida

### 1. Verificar que las tablas existen
```bash
mysql -u root -e "USE skychcargo; SHOW TABLES;"
```
Deberías ver: `preregistrations` y `preregistration_photos`

### 2. Verificar estructura de tablas
```bash
mysql -u root -e "USE skychcargo; DESCRIBE preregistrations;"
mysql -u root -e "USE skychcargo; DESCRIBE preregistration_photos;"
```

### 3. Verificar rutas API
```bash
php artisan route:list --path=api
```
Deberías ver 4 rutas relacionadas con preregistrations.

### 4. Verificar storage link
```bash
ls -la public/storage
```
Debería ser un enlace simbólico a `storage/app/public`.

---

## 🚀 Iniciar Servidor y Probar

### Opción 1: Script Automático
```bash
# Asegúrate de que el servidor esté corriendo
php artisan serve

# En otra terminal, ejecuta:
./test_preregistro.sh
```

### Opción 2: Pruebas Manuales con curl

#### 1. Iniciar servidor
```bash
php artisan serve
```

#### 2. Crear un preregistro
```bash
curl -X POST http://127.0.0.1:8000/api/preregistrations \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "intake_type": "COURIER",
    "tracking_external": "1Z999AA10123456784",
    "label_name": "Juan Perez",
    "service_type": "AIR",
    "intake_weight_lbs": 3.2
  }'
```

#### 3. Listar preregistros
```bash
curl http://127.0.0.1:8000/api/preregistrations \
  -H "Accept: application/json"
```

#### 4. Ver detalle (reemplaza {id} con el ID del preregistro creado)
```bash
curl http://127.0.0.1:8000/api/preregistrations/1 \
  -H "Accept: application/json"
```

#### 5. Probar filtros
```bash
curl "http://127.0.0.1:8000/api/preregistrations?service_type=AIR" \
  -H "Accept: application/json"
```

#### 6. Probar validación (debe fallar)
```bash
curl -X POST http://127.0.0.1:8000/api/preregistrations \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "intake_type": "COURIER",
    "label_name": "Test Sin Tracking",
    "service_type": "AIR",
    "intake_weight_lbs": 2.5
  }'
```
**Resultado esperado:** Error de validación indicando que `tracking_external` es requerido.

#### 7. Subir foto (reemplaza {id} y la ruta de la imagen)
```bash
curl -X POST http://127.0.0.1:8000/api/preregistrations/1/photos \
  -F "photo=@/ruta/a/tu/imagen.jpg" \
  -H "Accept: application/json"
```

---

## 📱 Usar Postman

1. Importa la collection desde `PREREGISTRO_API.md`
2. O crea manualmente las requests:
   - **POST** `/api/preregistrations` - Crear preregistro
   - **GET** `/api/preregistrations` - Listar
   - **GET** `/api/preregistrations/{id}` - Ver detalle
   - **POST** `/api/preregistrations/{id}/photos` - Subir foto

---

## ✅ Checklist de Verificación

- [ ] Tablas creadas en base de datos
- [ ] Rutas API registradas (4 rutas)
- [ ] Storage link creado (`public/storage` → `storage/app/public`)
- [ ] Crear preregistro funciona
- [ ] Listar preregistros funciona
- [ ] Ver detalle funciona
- [ ] Filtros funcionan (service_type, intake_type, date_from, date_to)
- [ ] Validación funciona (tracking requerido para COURIER)
- [ ] Subir foto funciona (máximo 10 fotos)

---

## 🔍 Verificar en Base de Datos

### Ver preregistros creados
```bash
mysql -u root -e "USE skychcargo; SELECT * FROM preregistrations;"
```

### Ver fotos subidas
```bash
mysql -u root -e "USE skychcargo; SELECT * FROM preregistration_photos;"
```

### Verificar que las fotos se guardaron en storage
```bash
ls -la storage/app/public/preregistrations/
```

---

## 🐛 Solución de Problemas

### Error: "Route not found"
- Verifica que `routes/api.php` existe
- Verifica que `bootstrap/app.php` tiene `api: __DIR__.'/../routes/api.php'`
- Ejecuta: `php artisan route:clear`

### Error: "Storage link not found"
- Ejecuta: `php artisan storage:link`

### Error: "Class not found"
- Ejecuta: `composer dump-autoload`

### Las fotos no se ven
- Verifica que `public/storage` es un enlace simbólico
- Verifica permisos: `chmod -R 775 storage/app/public`
- Verifica que el servidor web puede leer `public/storage`

---

## 📊 Estado Actual

✅ **Migraciones:** Ejecutadas correctamente  
✅ **Modelos:** Creados con relaciones  
✅ **Validaciones:** Funcionando  
✅ **API Endpoints:** Funcionando  
✅ **Storage Link:** Creado  
✅ **Rutas:** Registradas  

El módulo está **listo para usar** 🎉

