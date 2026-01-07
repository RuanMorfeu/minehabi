import time
import random
import json
import telebot
from datetime import datetime
from telebot.types import InlineKeyboardButton, InlineKeyboardMarkup
import bd

api_key = '6749694076:AAFw3DpV7c-G1p-KTaSpQlw9GKicMFb0utg'  # Substitua pelo seu próprio token
chat_id = '-1002028372755'  # Substitua pelo ID do seu canal

bot = telebot.TeleBot(token=api_key)

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
🔍 Possivel Entrada Detectada''').message_id
    bd.message_ids1 = message_id
    time.sleep(15)
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
    markup.add(InlineKeyboardButton(text="CADASTRA-SE💎⬅️", url="https://go.aff.elisa.bet/szdd5pp0"))
    return markup


while True:
    h = datetime.now().hour
    m = datetime.now().minute + 3
    s = datetime.now().second
    if h <= 9:
        h = f'0{h}'
    if m <= 9:
        m = f'0{m}'
    if s <= 9:
        s = f'0{s}'
    print(f'{h}:{m}:{s}')

    minas_configuracoes = [2, 3, 4]
    for minas in minas_configuracoes:
        cores = ['💣', '💣', '💣', '💣', '💣', '💎', '💣', '💣', '💣', '💣', '💎', '💣', '💣', '💣', '💣', '💎', '💣', '💣', '💣', '💣', '💎', '💣', '💣', '💣', '💣']

        ALERT_GALE1()  # Chama a função de alerta

        DELETE_GALE1()  # Chama a função de exclusão do alerta

        sample = random.sample(cores, k=25)

        chance_acerto = calcular_chance(minas)
    
        message_text = f'''
    ✅ SINAL CONFIRMADO ✅

    JOAO MINES VIP 💎

    💣 Minas: {minas}
    📊 % acerto: {chance_acerto}%
    🕛 Válido até: 5 minutos
    🔁 Nº de tentativas: 1

    {''.join(sample[:5])}
    {''.join(sample[5:10])}
    {''.join(sample[10:15])}
    {''.join(sample[15:20])}
    {''.join(sample[20:])}
    '''

        dados = bot.send_message(chat_id=chat_id, text=message_text, reply_markup=button_link())

        time.sleep(240)

        bot.send_message(chat_id=dados.chat.id, text=f'''
    Lucro Garantido ✅, Espere o Próximo Sinal
Cadastre-se Na Plataforma 👇🏼
    ''', reply_markup=InlineKeyboardMarkup().add(
        InlineKeyboardButton("LINK COM VANTAGEM 💎", url="https://go.aff.elisa.bet/szdd5pp0")
    ))

        time.sleep(60)
        time.sleep(60)
        time.sleep(60)
