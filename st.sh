#!/bin/bash

echo " Iniciando configuração do projeto..."

# Atualizar o repositório
echo " Atualizando o repositório..."
git pull

# Composer dump-autoload
echo " Executando composer dump-autoload..."
composer dump-autoload

# Composer update
echo " Atualizando dependências do Composer..."
composer update

# NPM install
echo " Instalando dependências do Node.js..."
npm install

# NPM build
echo " Construindo os assets..."
npm run build

echo " Configuração concluída com sucesso!"
