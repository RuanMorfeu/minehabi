import time
import random
import json
import telebot
import requests
import os
from datetime import datetime
from telebot.types import InlineKeyboardButton, InlineKeyboardMarkup
import bd

# Carrega configurações do arquivo JSON
def carregar_config():
    config_path = os.path.join(os.path.dirname(__file__), 'config.json')
    try:
        with open(config_path, 'r', encoding='utf-8') as f:
            return json.load(f)
    except Exception as e:
        print(f"Erro ao carregar config.json: {e}")
        return None

config = carregar_config()
if not config:
    print("Não foi possível carregar as configurações. Verifique o arquivo config.json")
    exit(1)

api_key = config['telegram']['bot_token']
chat_id = config['telegram']['chat_id']
api_url = config['api']['laravel_url']

bot = telebot.TeleBot(token=api_key)

def verificar_status_bot():
    """Verifica se o bot está habilitado no painel admin"""
    try:
        response = requests.get(api_url)
        if response.status_code == 200:
            data = response.json()
            return data.get('enabled', False)
    except:
        # Se falhar a conexão, assume que está desativado
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
    h = datetime.now().hour
    m = datetime.now().minute + 1
    s = datetime.now().second
    if h <= 9:
        h = f'0{h}'
    if m <= 9:
        m = f'0{m}'
    if s <= 9:
        s = f'0{s}'
    message_id = bot.send_message(chat_id=chat_id, text=f'''
🔍 ANALISANDO TABULEIRO...

📊 Identificando posições seguras...
⏳ Aguarde...''').message_id
    bd.message_ids1 = message_id
    time.sleep(60)
    bd.message_delete1 = True

def DELETE_GALE1():
    if bd.message_delete1 == True:
        bot.delete_message(chat_id=chat_id, message_id=bd.message_ids1)
        bd.message_delete1 = False

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
    # VERIFICA SE O BOT ESTÁ ATIVO NO PAINEL ADMIN
    if not verificar_status_bot():
        print("Bot desativado no painel admin. Aguardando 30 segundos...")
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
    print(f'{h}:{m}:{s} - Bot ATIVO')

    # Sempre usa 24 minas
    minas = 24
    
    # Verifica novamente antes de enviar o sinal
    if not verificar_status_bot():
        print("Bot desativado durante o envio. Parando...")
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

    dados = bot.send_message(chat_id=chat_id, text=message_text, reply_markup=button_link())

    time.sleep(180)

    bot.send_message(chat_id=dados.chat.id, text=f'''
Lucro Garantido ✅, Espere o Próximo Sinal
Cadastre-se Na Plataforma 👇🏼
    ''', reply_markup=InlineKeyboardMarkup().add(
        InlineKeyboardButton("LINK COM VANTAGEM 💎", url="https://dei.bet/register")
    ))

    time.sleep(30)
