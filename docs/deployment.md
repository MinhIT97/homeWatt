# Production deployment

## Runner contract

`deploy.yml` deploys pushes to `master` on a self-hosted Linux x64 runner. The
runner must provide `git`, Docker Engine, Docker Compose v2, and `curl`.

Repository variables:

- `PROJECT_DIR`: persistent production checkout, default
  `/home/minhnv/projects/homeWatt`.
- `PRODUCTION_URL`: public or runner-reachable URL, default
  `http://localhost:8087`.

The checkout must contain its production `.env`. The runner fetches and resets
tracked files to `origin/master`; `.env` and Docker volumes remain outside Git.

## Release images

The app and worker services use `homewatt-app:<short-sha>`. Nginx uses
`homewatt-nginx:<short-sha>`. Both images carry the full commit SHA in the
`org.opencontainers.image.revision` label, while Laravel exposes it through
`GET /version`.

`pull_policy: never` is intentional for these two locally built images. It
prevents Compose from trying to pull private or nonexistent release tags while
the self-hosted runner deploys its freshly built images.

## Health and smoke checks

Docker checks each long-running role independently:

- `app`: PHP-FPM port 9000.
- `queue`: default queue worker.
- `queue-ai`: AI queue worker.
- `scheduler`: Laravel schedule worker.

After all roles become healthy, `scripts/deploy/smoke-test.sh` verifies `/up`,
the expected release from `/version`, the no-store response header, `/login`,
and a referenced Vite asset.

## Rollback

Before building a release, the workflow tags the currently running app and
Nginx images with `rollback-<run-id>`. If migration, health checks, or smoke
tests fail, those images replace the failed release tags and all application
roles are recreated. The workflow then repeats health checks and the production
smoke test against the restored release.

Production migrations must remain backward-compatible because image rollback
does not reverse database migrations.

## Optional notifications

Set `TELEGRAM_BOT_TOKEN` and `TELEGRAM_DEPLOY_CHAT_ID` as repository secrets to
receive success or failure notifications. Missing Telegram credentials do not
fail deployment.
