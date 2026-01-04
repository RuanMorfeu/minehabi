<?php

namespace Database\Seeders;

use App\Models\AuthPopup;
use Illuminate\Database\Seeder;

class AuthPopupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pop-up para login
        AuthPopup::create([
            'title' => 'Bem-vindo de volta!',
            'message' => 'Olá, é ótimo ter você de volta ao DEI.bet! Aproveite todas as nossas funcionalidades e divirta-se.',
            'button_text' => 'Vamos jogar!',
            'show_after_login' => true,
            'show_after_register' => false,
            'show_only_once' => false,
            'active' => true,
            'target_user_type' => 'all',
        ]);

        // Pop-up para registro
        AuthPopup::create([
            'title' => 'Bem-vindo ao DEI.bet!',
            'message' => 'Obrigado por se registrar! Sua conta foi criada com sucesso. Aproveite todas as nossas funcionalidades e divirta-se.',
            'button_text' => 'Começar a jogar!',
            'show_after_login' => false,
            'show_after_register' => true,
            'show_only_once' => true,
            'active' => true,
            'target_user_type' => 'new',
        ]);

        // Pop-up para usuários com depósito
        AuthPopup::create([
            'title' => 'Obrigado pelo seu depósito!',
            'message' => 'Agradecemos pela confiança! Seu depósito foi recebido e você já pode aproveitar todos os nossos jogos com seu saldo.',
            'button_text' => 'Vamos jogar!',
            'show_after_login' => true,
            'show_after_register' => false,
            'show_only_once' => false,
            'active' => true,
            'target_user_type' => 'with_deposit',
        ]);

        // Pop-up para usuários sem depósito
        AuthPopup::create([
            'title' => 'Faça seu primeiro depósito!',
            'message' => 'Faça seu primeiro depósito agora e ganhe um bônus especial de boas-vindas! Não perca essa oportunidade.',
            'button_text' => 'Depositar agora',
            'show_after_login' => true,
            'show_after_register' => false,
            'show_only_once' => false,
            'active' => true,
            'target_user_type' => 'without_deposit',
        ]);
    }
}
