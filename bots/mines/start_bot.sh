#!/bin/bash

# Script para iniciar o Bot Mines
# Torne executável: chmod +x start_bot.sh

echo "Iniciando Bot Mines..."

# Verifica se o ambiente virtual existe
if [ ! -d "venv" ]; then
    echo "Criando ambiente virtual..."
    python3 -m venv venv
fi

# Ativa o ambiente virtual
source venv/bin/activate

# Instala dependências
echo "Instalando dependências..."
pip install -r requirements.txt

# Verifica se o arquivo .env existe
if [ ! -f ".env" ]; then
    echo "Criando arquivo .env a partir do exemplo..."
    cp .env.example .env
    echo "⚠️  Por favor, edite o arquivo .env com suas configurações!"
    exit 1
fi

# Inicia o bot
echo "Iniciando o bot..."
python Mines_com_api.py
