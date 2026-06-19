#!/bin/bash
# Script de ayuda para base de datos y migraciones en Linux / macOS

COMMAND=$1
ARG=$2

if [ "$COMMAND" = "create" ]; then
    if [ -z "$ARG" ]; then
        echo "Error: Debes especificar el nombre de la migracion."
        echo "Ejemplo: ./db.sh create create_logins_table"
        exit 1
    fi
    docker compose exec app php artisan make:migration "$ARG"
    exit 0
fi

if [ "$COMMAND" = "migrate" ]; then
    docker compose exec app php artisan migrate
    exit 0
fi

if [ "$COMMAND" = "fresh" ]; then
    docker compose exec app php artisan migrate:fresh --seed
    exit 0
fi

if [ "$COMMAND" = "rollback" ]; then
    docker compose exec app php artisan migrate:rollback
    exit 0
fi

if [ "$COMMAND" = "status" ]; then
    docker compose exec app php artisan migrate:status
    exit 0
fi

if [ "$COMMAND" = "seed" ]; then
    docker compose exec app php artisan db:seed
    exit 0
fi

# Mostrar ayuda por defecto
echo "Sistema de Ayuda de Base de Datos (Docker Wrapper)"
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
