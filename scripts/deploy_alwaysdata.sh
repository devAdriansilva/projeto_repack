#!/usr/bin/env bash
# ==============================================================================
# SCRIPT DE DEPLOY MANUAL - PROJETO REPACK (ALWAYSDATA)
# ==============================================================================
# Uso:
#   chmod +x scripts/deploy_alwaysdata.sh
#   ./scripts/deploy_alwaysdata.sh usuario ssh-usuario.alwaysdata.net /home/usuario/projeto_repack
# ==============================================================================

set -euo pipefail

USER="${1:-}"
HOST="${2:-}"
DEST_PATH="${3:-}"

if [ -z "$USER" ] || [ -z "$HOST" ] || [ -z "$DEST_PATH" ]; then
    echo "Erro: Parâmetros insuficientes."
    echo "Uso: ./scripts/deploy_alwaysdata.sh <usuario> <host_ssh> <caminho_remoto>"
    echo "Exemplo: ./scripts/deploy_alwaysdata.sh minha_conta ssh-minha_conta.alwaysdata.net /home/minha_conta/projeto_repack"
    exit 1
fi

echo "=============================================="
echo "1. Executando testes automatizados locais..."
echo "=============================================="
php tests/test_fase5.php

echo "=============================================="
echo "2. Sincronizando arquivos com Alwaysdata via rsync..."
echo "=============================================="
rsync -avz --progress \
    --exclude-from="deploy-exclude.txt" \
    ./ "${USER}@${HOST}:${DEST_PATH}/"

echo "=============================================="
echo "3. Executando migrations e ajustando permissões remotas..."
echo "=============================================="
ssh "${USER}@${HOST}" bash -c "'
    cd \"${DEST_PATH}\"
    mkdir -p logs public/uploads/produtos
    chmod 755 .
    chmod -R 755 app public database
    chmod -R 775 logs public/uploads
    php database/migrator.php
'"

echo "=============================================="
echo "DEPLOY CONCLUÍDO COM SUCESSO NA ALWAYSDATA!"
echo "=============================================="
