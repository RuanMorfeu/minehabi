<?php

namespace App\Filament\Admin\Pages;

use App\Models\Gateway;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\HtmlString;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class GatewayPage extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.gateway-page';

    public ?array $data = [];

    public Gateway $setting;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    /*** @return void
     */
    public function mount(): void
    {
        $gateway = Gateway::first();
        if (! empty($gateway)) {
            $this->setting = $gateway;
            $this->form->fill($this->setting->toArray());
        } else {
            $this->form->fill();
        }
    }

    /*** @param Form $form
     * @return Form
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Sibs')
                    ->description(new HtmlString('URL: https://www.docs.pay.sibs.com/portugal/sibs-gateway/integrations/api'))
                    ->schema([
                        TextInput::make('sibs_terminalId')
                            ->label('Terminal ID')
                            ->placeholder('')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('sibs_entidade')
                            ->label('Entidade')
                            ->placeholder('')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('sibs_clientId')
                            ->label('Client ID')
                            ->placeholder('')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        Textarea::make('sibs_bearerToken')
                            ->label('Bearer Token')
                            ->placeholder('')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('EuPago')

                    ->description(new HtmlString('URL: https://eupago.pt'))
                    ->schema([
                        TextInput::make('eupago_uri')
                            ->label('URI')
                            ->placeholder('https://clientes.eupago.pt/')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('eupago_id')
                            ->label('Client ID')
                            ->placeholder('4a830d1439ea40d890c007b5364exxxx')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('eupago_secret')
                            ->label('Client Secret')
                            ->placeholder('1bc65bfab03c40379a081bbffe6xxxx')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('eupago_api_key')
                            ->label('API Key')
                            ->placeholder('068b-8b22-2bf7-86df-xxxx')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ]),

                Section::make('EzzePay')

                    ->description(new HtmlString('Ajustes de credenciais para a EzzePay, Webhook: <b>'.url('/ezzepay/webhook', [], true).'</b>'))
                    ->schema([
                        TextInput::make('ezze_uri')
                            ->label('URI')
                            ->placeholder('Digite a url da api')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('ezze_client')
                            ->label('Client ID')
                            ->placeholder('Digite o client ID')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('ezze_secret')
                            ->label('Client Secret')
                            ->placeholder('Digite o client secret')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('ezze_user')
                            ->label('Usúario do webhook')
                            ->placeholder('Digite o usuário de autenticação do webhook')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('ezze_senha')
                            ->label('Senha do webhook')
                            ->placeholder('Digite a senha de autenticação do webhook')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ]),
                Section::make('Suitpay')
                    ->description('Ajustes de credenciais para a Suitpay')
                    ->schema([
                        TextInput::make('suitpay_cliente_id')
                            ->label('Client ID')
                            ->placeholder('Digite o client ID')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('suitpay_cliente_secret')
                            ->label('Client Secret')
                            ->placeholder('Digite o client secret')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('DigitoPay')
                    ->description(new HtmlString('<div style="display:flex; align-items:center">Abra sua conta agora mesmo na DigitoPay! Aproveite as melhores taxas do mercado e tenha sua conta aprovada em até 24 horas. <a class=" dark:text-white" style="width: 137px; display: flex; background-color: #A000EC; padding: 10px; border-radius: 20px; justify-content:center;" href="https://app.wa.link/link/digitopay" target="_blank">Abra agora</a></div>'))

                    ->schema([
                        TextInput::make('digitopay_uri')
                            ->label('Client URI')
                            ->placeholder('Digite a url da api')
                            ->maxLength(191),
                        TextInput::make('digitopay_cliente_id')
                            ->label('Client ID')
                            ->placeholder('Digite o client ID')
                            ->maxLength(191),
                        TextInput::make('digitopay_cliente_secret')
                            ->label('Client Secret')
                            ->placeholder('Digite o client secret')
                            ->maxLength(191),
                    ])->columns(3),

                Section::make('Mollie')
                    ->description('Gateway de pagamento Mollie')
                    ->schema([
                        \Filament\Forms\Components\Toggle::make('mollie_active')
                            ->label('Ativar Mollie')
                            ->helperText('Ative para habilitar pagamentos via Mollie')
                            ->columnSpanFull(),
                        TextInput::make('mollie_api_key')
                            ->label('API Key')
                            ->placeholder('test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx ou live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                            ->maxLength(191)
                            ->helperText('Chave da API do Mollie (test_ para testes, live_ para produção)')
                            ->columnSpanFull(),
                        TextInput::make('mollie_profile_id')
                            ->label('Profile ID')
                            ->placeholder('pfl_xxxxxxxxxxxxxxxx')
                            ->maxLength(191)
                            ->helperText('Profile ID do Mollie para usar com Components API (checkout embebido)')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    /*** @return void
     */
    public function submit(): void
    {
        try {
            if (env('APP_DEMO')) {
                Notification::make()
                    ->title('Atenção')
                    ->body('Você não pode realizar está alteração na versão demo')
                    ->danger()
                    ->send();

                return;
            }

            $setting = Gateway::first();
            if (! empty($setting)) {
                if ($setting->update($this->data)) {
                    if (! empty($this->data['stripe_public_key'])) {
                        $envs = DotenvEditor::load(base_path('.env'));

                        $envs->setKeys([
                            'STRIPE_KEY' => $this->data['stripe_public_key'],
                            'STRIPE_SECRET' => $this->data['stripe_secret_key'],
                            'STRIPE_WEBHOOK_SECRET' => $this->data['stripe_webhook_key'],
                        ]);

                        $envs->save();
                    }

                    Notification::make()
                        ->title('Chaves Alteradas')
                        ->body('Suas chaves foram alteradas com sucesso!')
                        ->success()
                        ->send();
                }
            } else {
                if (Gateway::create($this->data)) {
                    Notification::make()
                        ->title('Chaves Criadas')
                        ->body('Suas chaves foram criadas com sucesso!')
                        ->success()
                        ->send();
                }
            }
        } catch (Halt $exception) {
            Notification::make()
                ->title('Erro ao alterar dados!')
                ->body('Erro ao alterar dados!')
                ->danger()
                ->send();
        }
    }
}
