#!/bin/bash

# Saia do script se algum comando falhar
set -e

echo "Instalando dependências com npm install..."
npm install

echo "Executando build com npm run build..."
npm run build

echo "Processo concluído com sucesso!"