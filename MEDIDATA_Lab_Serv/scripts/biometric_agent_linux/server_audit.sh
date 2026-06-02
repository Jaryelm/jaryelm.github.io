#!/bin/bash
# Auditoría rápida en servidor físico Ubuntu (sede Medicasa).
# Uso:
#   chmod +x server_audit.sh
#   ./server_audit.sh
#   ./server_audit.sh --run-agent
set -euo pipefail

ENV_FILE="${MEDICASA_AGENT_ENV:-/etc/medicasa-biometric-agent.env}"
REPO_DEFAULT="/opt/MedicasaDATAUpdate2"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${MEDIDATA_AGENT_REPO_ROOT:-$(dirname "$SCRIPT_DIR" 2>/dev/null || echo "$REPO_DEFAULT")}"

if [[ -f "$ENV_FILE" ]]; then
  set -a
  # shellcheck source=/dev/null
  source "$ENV_FILE"
  set +a
  echo "[OK] Cargado $ENV_FILE"
else
  echo "[WARN] No existe $ENV_FILE — exporte variables manualmente."
fi

export MEDIDATA_AGENT_REPO_ROOT="${MEDIDATA_AGENT_REPO_ROOT:-$REPO}"
PHP_BIN="${PHP_BIN:-/usr/bin/php}"

if [[ ! -x "$PHP_BIN" ]]; then
  echo "[FAIL] PHP CLI no encontrado en $PHP_BIN"
  exit 1
fi

AUDIT="$MEDIDATA_AGENT_REPO_ROOT/scripts/biometric_agent_linux/server_audit.php"
if [[ ! -f "$AUDIT" ]]; then
  echo "[FAIL] No está server_audit.php en $AUDIT"
  echo "       Sincronice el repo (rsync/git) hacia $MEDIDATA_AGENT_REPO_ROOT"
  exit 1
fi

exec "$PHP_BIN" "$AUDIT" --env-file="$ENV_FILE" "$@"
