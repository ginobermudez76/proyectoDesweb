CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TABLE rol(
    id SERIAL NOT NULL PRIMARY KEY,
    uuid UUID NOT NULL UNIQUE DEFAULT uuid_generate_v4(),
    codigo character varying(50) UNIQUE NOT NULL,
    nombre_rol character varying(50) UNIQUE NOT NULL,
    descripcion character varying(255),
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    updated_at timestamp with time zone,
    deleted boolean NOT NULL DEFAULT false,
    deleted_at timestamp with time zone,
);

CREATE TABLE usuario(
    id SERIAL NOT NULL PRIMARY KEY,
    uuid UUID NOT NULL UNIQUE DEFAULT uuid_generate_v4(),
    nombre_usuario varchar(50) NOT NULL,
    correo_electronico varchar(100) NOT NULL,
    password_hash varchar(255) NOT NULL,
    nombres varchar(50) NOT NULL,
    apellidos varchar(50) NOT NULL,
    activo boolean NOT NULL DEFAULT false,
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    updated_at timestamp with time zone,
    deleted boolean NOT NULL DEFAULT false,
    deleted_at timestamp with time zone,
);

CREATE TABLE opcion(
    id SERIAL NOT NULL PRIMARY KEY,
    uuid UUID NOT NULL UNIQUE DEFAULT uuid_generate_v4(),
    nombre_opcion varchar(50) NOT NULL UNIQUE,
    descripcion varchar(255),
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    updated_at timestamp with time zone,
    deleted boolean NOT NULL DEFAULT false,
    deleted_at timestamp with time zone,
);

CREATE TABLE endpoint(
    id SERIAL NOT NULL PRIMARY KEY,
    uuid UUID NOT NULL UNIQUE DEFAULT uuid_generate_v4(),
    nombre_endpoint varchar(50) NOT NULL UNIQUE,
    url varchar(255) NOT NULL UNIQUE,
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    updated_at timestamp with time zone,
    deleted boolean NOT NULL DEFAULT false,
    deleted_at timestamp with time zone,
    rbac_enabled boolean NOT NULL DEFAULT false
);

CREATE TABLE rol_opcion(
    id SERIAL NOT NULL PRIMARY KEY,
    uuid UUID NOT NULL UNIQUE DEFAULT uuid_generate_v4(),
    id_rol integer NOT NULL,
    id_opcion integer NOT NULL,
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    updated_at timestamp with time zone,
    deleted boolean NOT NULL DEFAULT false,
    deleted_at timestamp with time zone,
    FOREIGN KEY (id_rol) REFERENCES rol(id),
    FOREIGN KEY (id_opcion) REFERENCES opcion(id),

    CONSTRAINT uq_rol_opcion UNIQUE(id_rol, id_opcion)
);

CREATE TABLE opcion_endpoint(
    id SERIAL NOT NULL PRIMARY KEY,
    uuid UUID NOT NULL UNIQUE DEFAULT uuid_generate_v4(),
    id_opcion integer NOT NULL,
    id_endpoint integer NOT NULL,
    FOREIGN KEY (id_opcion) REFERENCES opcion(id),
    FOREIGN KEY (id_endpoint) REFERENCES endpoint(id),
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    updated_at timestamp with time zone,
    deleted boolean NOT NULL DEFAULT false,
    deleted_at timestamp with time zone,

    CONSTRAINT uq_opcion_endpoint UNIQUE(id_opcion, id_endpoint)
);

CREATE TABLE rol_usuario(
    id SERIAL NOT NULL PRIMARY KEY,
    uuid UUID NOT NULL UNIQUE DEFAULT uuid_generate_v4(),
    id_rol integer NOT NULL,
    id_usuario integer NOT NULL,
    FOREIGN KEY (id_rol) REFERENCES rol(id),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id),
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    updated_at timestamp with time zone,
    deleted boolean NOT NULL DEFAULT false,
    deleted_at timestamp with time zone,

    CONSTRAINT uq_rol_usuario UNIQUE(id_rol, id_usuario)
);

CREATE TABLE auditoria (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT uuid_generate_v4(),
    entidad VARCHAR(50) NOT NULL,
    id_entidad INTEGER,
    accion VARCHAR(50) NOT NULL,
    datos_anteriores JSONB,
    datos_nuevos JSONB,
    usuario VARCHAR(100),
    fecha TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_rol_usuario_rol
ON rol_usuario(id_rol);

CREATE INDEX idx_rol_usuario_usuario
ON rol_usuario(id_usuario);

CREATE INDEX idx_opcion_endpoint_opcion
ON opcion_endpoint(id_opcion);

CREATE INDEX idx_opcion_endpoint_endpoint
ON opcion_endpoint(id_endpoint);

CREATE INDEX idx_rol_opcion_rol
ON rol_opcion(id_rol);

CREATE INDEX idx_rol_opcion_opcion
ON rol_opcion(id_opcion);

CREATE INDEX idx_auditoria_entidad
ON auditoria(entidad);

CREATE INDEX idx_auditoria_fecha
ON auditoria(fecha);

CREATE INDEX idx_auditoria_accion
ON auditoria(accion);

CREATE INDEX idx_auditoria_id_entidad
ON auditoria(id_entidad);

CREATE OR REPLACE FUNCTION fn_auditoria()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN

    -- INSERT
    IF TG_OP = 'INSERT' THEN

        INSERT INTO auditoria(
            entidad,
            id_entidad,
            accion,
            datos_nuevos,
            usuario
        )
        VALUES(
            TG_TABLE_NAME,
            NEW.id,
            'INSERT',
            to_jsonb(NEW),
            CURRENT_USER
        );

        RETURN NEW;

    END IF;

    -- UPDATE
    IF TG_OP = 'UPDATE' THEN

        -- Detectar borrado lógico
        IF OLD.deleted = false
           AND NEW.deleted = true THEN

            INSERT INTO auditoria(
                entidad,
                id_entidad,
                accion,
                datos_anteriores,
                datos_nuevos,
                usuario
            )
            VALUES(
                TG_TABLE_NAME,
                NEW.id,
                'DELETE_LOGICO',
                to_jsonb(OLD),
                to_jsonb(NEW),
                CURRENT_USER
            );

        ELSE

            INSERT INTO auditoria(
                entidad,
                id_entidad,
                accion,
                datos_anteriores,
                datos_nuevos,
                usuario
            )
            VALUES(
                TG_TABLE_NAME,
                NEW.id,
                'UPDATE',
                to_jsonb(OLD),
                to_jsonb(NEW),
                CURRENT_USER
            );

        END IF;

        RETURN NEW;

    END IF;

    -- DELETE físico
    IF TG_OP = 'DELETE' THEN

        INSERT INTO auditoria(
            entidad,
            id_entidad,
            accion,
            datos_anteriores,
            usuario
        )
        VALUES(
            TG_TABLE_NAME,
            OLD.id,
            'DELETE_FISICO',
            to_jsonb(OLD),
            CURRENT_USER
        );

        RETURN OLD;

    END IF;

    RETURN NULL;

END;
$$;

CREATE UNIQUE INDEX uq_usuario_nombre_activo
ON usuario(nombre_usuario)
WHERE activo = true;

CREATE UNIQUE INDEX uq_usuario_correo_activo
ON usuario(correo_electronico)
WHERE activo = true;

CREATE TRIGGER trg_auditoria_rol
AFTER INSERT OR UPDATE OR DELETE
ON rol
FOR EACH ROW
EXECUTE FUNCTION fn_auditoria();

CREATE TRIGGER trg_auditoria_usuario
AFTER INSERT OR UPDATE OR DELETE
ON usuario
FOR EACH ROW
EXECUTE FUNCTION fn_auditoria();

CREATE TRIGGER trg_auditoria_opcion
AFTER INSERT OR UPDATE OR DELETE
ON opcion
FOR EACH ROW
EXECUTE FUNCTION fn_auditoria();

CREATE TRIGGER trg_auditoria_endpoint
AFTER INSERT OR UPDATE OR DELETE
ON endpoint
FOR EACH ROW
EXECUTE FUNCTION fn_auditoria();

CREATE TRIGGER trg_auditoria_rol_opcion
AFTER INSERT OR UPDATE OR DELETE
ON rol_opcion
FOR EACH ROW
EXECUTE FUNCTION fn_auditoria();

CREATE TRIGGER trg_auditoria_opcion_endpoint
AFTER INSERT OR UPDATE OR DELETE
ON opcion_endpoint
FOR EACH ROW
EXECUTE FUNCTION fn_auditoria();

CREATE TRIGGER trg_auditoria_rol_usuario
AFTER INSERT OR UPDATE OR DELETE
ON rol_usuario
FOR EACH ROW
EXECUTE FUNCTION fn_auditoria();