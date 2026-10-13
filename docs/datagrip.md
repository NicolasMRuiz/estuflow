# PostgreSQL desde DataGrip en la Mac

El servicio `postgres` publica exclusivamente `127.0.0.1:${DB_FORWARD_PORT:-5433}:5432`. El puerto efectivo comprobado en este checkout es **5433**, libre antes de aplicarlo. No se expone a la red local ni a Internet. Laravel continúa usando `DB_HOST=postgres` y `DB_PORT=5432` dentro de Docker. `test-postgres` y Redis no publican puertos.

## Datos de conexión

| Campo | Valor efectivo |
| --- | --- |
| Host | `127.0.0.1` |
| Puerto | `5433` |
| Base | `estuflow` (`DB_DATABASE` local) |
| Usuario | `estuflow` (`DB_USERNAME` local) |
| Contraseña | **DB_PASSWORD de .env local** |
| URL JDBC sin credenciales | `jdbc:postgresql://127.0.0.1:5433/estuflow` |

En DataGrip, crear una fuente desde File → New → Data Source → PostgreSQL. Usar el tipo de conexión predeterminado, completar los campos anteriores y elegir autenticación User & Password. Si faltan drivers, usar la descarga ofrecida por DataGrip. Mantener la contraseña en su campo específico; no incluirla en la URL, capturas, logs o Git. Ejecutar **Test Connection**, seleccionar el esquema `public` y guardar. [Procedimiento oficial de JetBrains](https://www.jetbrains.com/help/datagrip/postgresql.html).

Para comprobar identidad sin modificar datos, abrir una consola SQL y ejecutar:

```sql
SELECT current_database(), current_user, version();
SELECT count(*) FROM users;
```

La interfaz DataGrip y su Test Connection aún no se operaron en esta entrega. TCP desde la Mac y la consulta autenticada de Laravel son comprobaciones distintas; sólo la segunda valida una sesión PostgreSQL, por la red interna Docker. No se instaló un cliente psql en la Mac.

## Aplicar o cambiar el puerto conservando datos

`DB_FORWARD_PORT=5433` está en `.env.example` y se añadió al `.env` local sin alterar otros valores. Si está ocupado, elegir otro libre (por ejemplo 5434), actualizar **sólo** `DB_FORWARD_PORT` y recrear `postgres`:

```bash
docker compose config --quiet
docker compose up -d --wait postgres
docker compose port postgres 5432
./scripts/start.sh
```

Usar el puerto que devuelve el tercer comando en DataGrip y registrar su valor efectivo. No cambiar `DB_PORT`, `DB_HOST`, credenciales ni nombre del volumen para resolver conflictos. Reiniciar app/worker si una conexión larga quedó interrumpida. El volumen existente `estuflow_postgres_data` se conserva; recrear el contenedor no reinicializa la base. No ejecutar resets de desarrollo ni borrar volúmenes.

Para aplicar cambios de Google/Brevo: `docker compose run --rm --no-deps app php artisan config:clear` y `docker compose restart app worker`. [Pruebas manuales de correo y Google](mail.md#prueba-manual-desde-la-ui-con-brevo-configurado). `.env` y `.env.testing` siguen ignorados; no compartir sus valores.

[Resultados de esta ampliación](evidence/m02/datagrip-config.md).
