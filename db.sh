#!/bin/bash
# Script de ayuda para base de datos y migraciones local (Nativo)

COMMAND=$1
ARG=$2

case "$COMMAND" in
    create)
        if [[ -z "$ARG" ]]; then
            echo "Error: Debes especificar el nombre de la migracion." >&2
            echo "Ejemplo: ./db.sh create create_logins_table" >&2
            exit 1
        fi
        php backend/artisan make:migration "$ARG"
        ;;
    migrate)
        php backend/artisan migrate
        ;;
    fresh)
        php backend/artisan migrate:fresh --seed
        ;;
    rollback)
        php backend/artisan migrate:rollback
        ;;
    status)
        php backend/artisan migrate:status
        ;;
    seed)
        php backend/artisan db:seed
        ;;
    *)
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
        ;;
esac

exit 0
