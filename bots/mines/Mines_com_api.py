import time
import random
import json
import telebot
import requests
import os
import logging
import sys
from datetime import datetime
from telebot.types import InlineKeyboardButton, InlineKeyboardMarkup
import bd

# Configuração de Logs
log_file = os.path.join(os.path.dirname(__file__), 'bot_debug.log')
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(log_file),
        logging.StreamHandler(sys.stdout)
    ]
)

logging.info("Iniciando bot Mines...")

# Carrega configurações do arquivo JSON
def carregar_config():
    config_path = os.path.join(os.path.dirname(__file__), 'config.json')
    try:
        with open(config_path, 'r', encoding='utf-8') as f:
            config = json.load(f)
            logging.info("Configurações carregadas com sucesso.")
            return config
    except Exception as e:
        logging.error(f"Erro ao carregar config.json: {e}")
        return None

config = carregar_config()
if not config:
    logging.critical("Não foi possível carregar as configurações. Verifique o arquivo config.json")
    exit(1)

api_key = config['telegram']['bot_token']
chat_id = config['telegram']['chat_id']
api_url = config['api']['laravel_url']

logging.info(f"Bot Token: {api_key[:5]}...{api_key[-5:]}")
logging.info(f"Chat ID: {chat_id}")
logging.info(f"API URL: {api_url}")

try:
    bot = telebot.TeleBot(token=api_key)
    logging.info("Instância do TeleBot criada com sucesso.")
except Exception as e:
    logging.critical(f"Erro ao criar instância do TeleBot: {e}")
    exit(1)

def verificar_status_bot():
    """Verifica se o bot está habilitado no painel admin"""
    try:
        logging.debug(f"Verificando status na API: {api_url}")
        response = requests.get(api_url, timeout=10)
        if response.status_code == 200:
            data = response.json()
            status = data.get('enabled', False)
            logging.debug(f"Status recebido da API: {status}")
            return status
        else:
            logging.warning(f"API retornou status code: {response.status_code}")
            return False
    except Exception as e:
        logging.error(f"Erro ao verificar status na API: {e}")
        return False
    return False

def calcular_chance(minas):
    if minas == 2:
        return random.randint(93, 100)
    elif minas == 3:
        return random.randint(86, 100)
    elif minas == 4:
        return random.randint(78, 100)

# Defina as funções ALERT_GALE1 e DELETE_GALE1 como antes
def ALERT_GALE1():
    try:
        h = datetime.now().hour
        m = datetime.now().minute + 1
        s = datetime.now().second
        if h <= 9:
            h = f'0{h}'
        if m <= 9:
            m = f'0{m}'
        if s <= 9:
            s = f'0{s}'
        
        logging.info("Enviando mensagem de alerta...")
        msg = bot.send_message(chat_id=chat_id, text=f'''
🔍 ANALISANDO TABULEIRO...

📊 Identificando posições seguras...
⏳ Aguarde...''')
        message_id = msg.message_id
        logging.info(f"Mensagem de alerta enviada. ID: {message_id}")
        
        bd.message_ids1 = message_id
        time.sleep(60)
        bd.message_delete1 = True
    except Exception as e:
        logging.error(f"Erro em ALERT_GALE1: {e}")

def DELETE_GALE1():
    try:
        if bd.message_delete1 == True:
            logging.info(f"Deletando mensagem de alerta ID: {bd.message_ids1}")
            bot.delete_message(chat_id=chat_id, message_id=bd.message_ids1)
            bd.message_delete1 = False
    except Exception as e:
        logging.error(f"Erro em DELETE_GALE1: {e}")

def gerar_minas(quantidade):
    minas = ['💣'] * quantidade + ['💎'] * (25 - quantidade)
    random.shuffle(minas)
    return minas

# Resto do código
def button_link():
    markup = InlineKeyboardMarkup()
    markup.row_width = 2
    markup.add(InlineKeyboardButton(text="CADASTRA-SE💎⬅️", url="https://dei.bet/register"))
    return markup


while True:
    try:
        # VERIFICA SE O BOT ESTÁ ATIVO NO PAINEL ADMIN
        if not verificar_status_bot():
            logging.info("Bot desativado no painel admin. Aguardando 30 segundos...")
            time.sleep(30)
            continue
        
        h = datetime.now().hour
        m = datetime.now().minute + 3
        s = datetime.now().second
        if h <= 9:
            h = f'0{h}'
        if m <= 9:
            m = f'0{m}'
        if s <= 9:
            s = f'0{s}'
        logging.info(f'{h}:{m}:{s} - Bot ATIVO e iniciando ciclo de envio.')

        # Sempre usa 24 minas
        minas = 24
        
        # Verifica novamente antes de enviar o sinal
        if not verificar_status_bot():
            logging.info("Bot desativado durante o envio. Parando ciclo.")
            continue
                
        # Array com 24 bombas e 1 diamante
        cores = ['💣'] * 24 + ['💎']

        ALERT_GALE1()  # Chama a função de alerta

        DELETE_GALE1()  # Chama a função de exclusão do alerta

        sample = random.sample(cores, k=25)

        chance_acerto = calcular_chance(minas)
        
        message_text = f'''
✅ MINES - ENTRADA CONFIRMADA ✅

🎮 Jogo: MINES
💣 Minas: {minas}
📊 % acerto: 100%
🕛 Válido até: 3 minutos

📍 TABULEIRO:
{''.join(sample[:5])}
{''.join(sample[5:10])}
{''.join(sample[10:15])}
{''.join(sample[15:20])}
{''.join(sample[20:])}

⏰ Entre agora e boa sorte!
'''

        logging.info("Enviando sinal de entrada confirmada...")
        dados = bot.send_message(chat_id=chat_id, text=message_text, reply_markup=button_link())
        logging.info(f"Sinal enviado com sucesso. ID: {dados.message_id}")

        logging.info("Aguardando 180 segundos (validade do sinal)...")
        time.sleep(180)

        logging.info("Enviando mensagem de lucro garantido...")
        bot.send_message(chat_id=dados.chat.id, text=f'''
Lucro Garantido ✅, Espere o Próximo Sinal
Cadastre-se Na Plataforma 👇🏼
    ''', reply_markup=InlineKeyboardMarkup().add(
            InlineKeyboardButton("LINK COM VANTAGEM 💎", url="https://dei.bet/register")
        ))

        logging.info("Aguardando 30 segundos para próximo ciclo...")
        time.sleep(30)
    
    except Exception as e:
        logging.error(f"Erro no loop principal: {e}")
        time.sleep(10)
