#!/bin/bash
# Script de ayuda para base de datos y migraciones local (Nativo)

COMMAND=$1
ARG=$2

if [ "$COMMAND" = "create" ]; then
    if [ -z "$ARG" ]; then
        echo "Error: Debes especificar el nombre de la migracion."
        echo "Ejemplo: ./db.sh create create_logins_table"
        exit 1
    fi
    php backend/artisan make:migration "$ARG"
    exit 0
fi

if [ "$COMMAND" = "migrate" ]; then
    php backend/artisan migrate
    exit 0
fi

if [ "$COMMAND" = "fresh" ]; then
    php backend/artisan migrate:fresh --seed
    exit 0
fi

if [ "$COMMAND" = "rollback" ]; then
    php backend/artisan migrate:rollback
    exit 0
fi

if [ "$COMMAND" = "status" ]; then
    php backend/artisan migrate:status
    exit 0
fi

if [ "$COMMAND" = "seed" ]; then
    php backend/artisan db:seed
    exit 0
fi

# Mostrar ayuda por defecto
echo "Sistema de Ayuda de Base de Datos (Nativo Local)"
echo "--------------------------------------------------"
echo "Uso: ./db.sh [comando] [argumentos]"
echo ""
echo "Comandos disponibles:"
echo "  ./db.sh create [nombre]   Crea una nueva migracion con prefijo AAAA_MM_DD_HHMMSS."
echo "  ./db.sh migrate           Ejecuta las migraciones pendientes."
echo "  ./db.sh fresh             Limpia la base de datos y ejecuta todas las migraciones y seeders."
echo "  ./db.sh rollback          Revierte el ultimo lote (batch) de migraciones ejecutadas."
echo "  ./db.sh status            Muestra el estado de cada migracion (Si esta ejecutada o pendiente)."
echo "  ./db.sh seed              Pobla la base de datos ejecutando los seeders."
echo ""
exit 1
