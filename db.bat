@echo off
:: Script de ayuda para base de datos y migraciones local (Nativo)

if "%1"=="create" (
    if "%2"=="" (
        echo Error: Debes especificar el nombre de la migracion.
        echo Ejemplo: db create create_logins_table
        exit /b 1
    )
    php backend/artisan make:migration %2
    exit /b 0
)

if "%1"=="migrate" (
    php backend/artisan migrate
    exit /b 0
)

if "%1"=="fresh" (
    php backend/artisan migrate:fresh --seed
    exit /b 0
)

if "%1"=="rollback" (
    php backend/artisan migrate:rollback
    exit /b 0
)

if "%1"=="status" (
    php backend/artisan migrate:status
    exit /b 0
)

if "%1"=="seed" (
    php backend/artisan db:seed
    exit /b 0
)

:: Mostrar ayuda por defecto
echo Sistema de Ayuda de Base de Datos (Nativo Local)
echo --------------------------------------------------
echo Uso: db [comando] [argumentos]
echo.
echo Comandos disponibles:
echo   db create [nombre]   Crea una nueva migracion con prefijo AAAA_MM_DD_HHMMSS.
echo   db migrate           Ejecuta las migraciones pendientes.
echo   db fresh             Limpia la base de datos y ejecuta todas las migraciones y seeders.
echo   db rollback          Revierte el ultimo lote (batch) de migraciones ejecutadas.
echo   db status            Muestra el estado de cada migracion (Si esta ejecutada o pendiente).
echo   db seed              Pobla la base de datos ejecutando los seeders.
echo.
exit /b 1
