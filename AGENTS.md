# EstuFlow

- Trabajar en este checkout aislado; no crear worktrees salvo pedido explícito.
- Stack: Laravel 13 / PHP 8.4 / Livewire 4 / Blade / Tailwind 4, PostgreSQL 18 y Redis. Alpine proviene únicamente de Livewire.
- Preservar archivos, `.env`, secretos y trabajo ajeno. Sin commits, push, despliegues ni borrado de volúmenes automáticos.
- Esta entrega implementa autenticación y panel vacío. El modelo académico requiere una tarea aparte; no simular datos ni enlaces operativos.
- Autorización, validación, CSRF y límites de intentos siempre en servidor. No guardar contraseñas ni tokens en logs/evidencias.
- Preparar con `./scripts/setup.sh`; iniciar y comprobar readiness con `./scripts/start.sh`.
- Checks: `./scripts/check.sh`, `./scripts/test.sh`, `./scripts/e2e.sh`. Tests únicamente en `testing`, PostgreSQL `estuflow_test` del servicio `test-postgres`; nunca SQLite ni reset de desarrollo.
- Consultar `docs/architecture.md` para backend/stack, `docs/ui.md` para interfaz, `docs/testing.md` para aislamiento y `docs/progress.md` para alcance.
- Al cambiar dependencias actualizar y verificar sus lockfiles. No desactivar TLS, firmas ni checksums.
