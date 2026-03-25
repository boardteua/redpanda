# Публічні копії документів монорепо

Ці файли — **копії** канонічних джерел:

- `docs/chat-v2/openapi.yaml`
- `docs/chat-v2/AI-AGENT-FRIENDLY.md`
- `project-specs/chat-v2-setup.md`

**Навіщо:** на проді `docker/deploy.sh` видаляє каталоги `docs/` і `project-specs/` у корені репозиторію. Маршрути Laravel `GET /docs/*` читають спочатку монорепо, а якщо файлів немає — **цей бандл** у `backend/resources/public-monorepo-docs/`.

**Синхронізація:** перед кожним `rm -rf` деплой копіює актуальні файли сюди; локально при зміні OpenAPI або markdown оновлюйте копії командою з кореня репозиторію:

```bash
cp -f docs/chat-v2/openapi.yaml backend/resources/public-monorepo-docs/chat-v2/
cp -f docs/chat-v2/AI-AGENT-FRIENDLY.md backend/resources/public-monorepo-docs/chat-v2/
cp -f project-specs/chat-v2-setup.md backend/resources/public-monorepo-docs/project-specs/
```
