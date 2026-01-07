#!/bin/bash

# Cores para logs
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Iniciando instalação do ambiente do Bot Mines...${NC}"

# 1. Verifica/Instala Python e Venv
echo "📦 Verificando Python..."
if ! command -v python3 &> /dev/null; then
    echo -e "${RED}Python3 não encontrado. Instalando...${NC}"
    sudo apt-get update
    sudo apt-get install -y python3 python3-pip python3-venv
else
    echo -e "${GREEN}Python3 encontrado!${NC}"
    # Garante que o módulo venv está instalado
    sudo apt-get install -y python3-venv
fi

# 2. Vai para o diretório do bot
cd bots/mines || { echo -e "${RED}Diretório bots/mines não encontrado!${NC}"; exit 1; }

# 3. Cria o ambiente virtual
if [ ! -d "venv" ]; then
    echo "🛠️  Criando ambiente virtual (venv)..."
    python3 -m venv venv
else
    echo "✅ Ambiente virtual já existe."
fi

# 4. Ativa e instala dependências
echo "📥 Instalando dependências..."
source venv/bin/activate

# Atualiza pip
pip install --upgrade pip

# Instala requirements
if [ -f "requirements.txt" ]; then
    pip install -r requirements.txt
    echo -e "${GREEN}✅ Dependências instaladas com sucesso!${NC}"
else
    echo -e "${RED}Arquivo requirements.txt não encontrado!${NC}"
    # Cria um requirements básico se não existir
    echo "pyTelegramBotAPI==4.10.0" > requirements.txt
    echo "requests==2.28.2" >> requirements.txt
    echo "python-dotenv" >> requirements.txt
    pip install -r requirements.txt
fi

# 5. Teste rápido
echo "🧪 Testando importações..."
python3 -c "import telebot; import requests; print('✅ Ambiente configurado corretamente!')"

echo ""
echo -e "${GREEN}🎉 Instalação concluída!${NC}"
echo "Agora você pode iniciar o bot pelo painel admin."
