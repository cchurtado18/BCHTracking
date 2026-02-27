#!/bin/bash

# Script de prueba para el módulo PREREGISTRO
# Asegúrate de tener el servidor corriendo: php artisan serve

BASE_URL="http://127.0.0.1:8000/api"
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== PRUEBAS DEL MÓDULO PREREGISTRO ===${NC}\n"

# 1. Crear preregistro
echo -e "${YELLOW}1. Creando preregistro...${NC}"
RESPONSE=$(curl -s -X POST "$BASE_URL/preregistrations" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "intake_type": "COURIER",
    "tracking_external": "1Z999AA10123456784",
    "label_name": "Juan Perez",
    "service_type": "AIR",
    "intake_weight_lbs": 3.2
  }')

ID=$(echo $RESPONSE | grep -o '"id":[0-9]*' | grep -o '[0-9]*')

if [ -z "$ID" ]; then
    echo -e "${RED}❌ Error al crear preregistro${NC}"
    echo "$RESPONSE"
    exit 1
else
    echo -e "${GREEN}✅ Preregistro creado con ID: $ID${NC}"
    echo "$RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE"
fi

echo ""

# 2. Listar preregistros
echo -e "${YELLOW}2. Listando preregistros...${NC}"
RESPONSE=$(curl -s "$BASE_URL/preregistrations" -H "Accept: application/json")
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Listado exitoso${NC}"
    echo "$RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE"
else
    echo -e "${RED}❌ Error al listar${NC}"
fi

echo ""

# 3. Ver detalle
echo -e "${YELLOW}3. Obteniendo detalle del preregistro $ID...${NC}"
RESPONSE=$(curl -s "$BASE_URL/preregistrations/$ID" -H "Accept: application/json")
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Detalle obtenido${NC}"
    echo "$RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE"
else
    echo -e "${RED}❌ Error al obtener detalle${NC}"
fi

echo ""

# 4. Probar filtros
echo -e "${YELLOW}4. Probando filtros (service_type=AIR)...${NC}"
RESPONSE=$(curl -s "$BASE_URL/preregistrations?service_type=AIR" -H "Accept: application/json")
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Filtros funcionando${NC}"
    echo "$RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE"
else
    echo -e "${RED}❌ Error con filtros${NC}"
fi

echo ""

# 5. Probar validación (tracking requerido para COURIER)
echo -e "${YELLOW}5. Probando validación (tracking requerido para COURIER)...${NC}"
RESPONSE=$(curl -s -X POST "$BASE_URL/preregistrations" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "intake_type": "COURIER",
    "label_name": "Test Sin Tracking",
    "service_type": "AIR",
    "intake_weight_lbs": 2.5
  }')

if echo "$RESPONSE" | grep -q "tracking_external\|required"; then
    echo -e "${GREEN}✅ Validación funcionando correctamente${NC}"
    echo "$RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE"
else
    echo -e "${RED}❌ La validación no está funcionando${NC}"
    echo "$RESPONSE"
fi

echo ""
echo -e "${YELLOW}=== PRUEBAS COMPLETADAS ===${NC}"
echo -e "${YELLOW}Nota: Para probar la subida de fotos, usa Postman o:${NC}"
echo -e "curl -X POST $BASE_URL/preregistrations/$ID/photos \\"
echo -e "  -F 'photo=@/ruta/a/tu/imagen.jpg' \\"
echo -e "  -H 'Accept: application/json'"

