<?php

namespace App\Filament\Admin\Resources\SettingResource\Pages;

use App\Filament\Admin\Resources\SettingResource;
use App\Models\Setting;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DefaultSetting extends Page implements HasForms
{
    use HasPageSidebar;
    use InteractsWithForms;

    protected static string $resource = SettingResource::class;

    protected static string $view = 'filament.resources.setting-resource.pages.default-setting';

    public static function canView(Model $record): bool
    {
        return auth()->user()->hasRole('admin');
    }

    /*** @return string|Htmlable
     */
    public function getTitle(): string|Htmlable
    {
        return __('Padrão');
    }

    public Setting $record;

    public ?array $data = [];

    public function mount(): void
    {
        $envs = DotenvEditor::load(base_path('.env'));
        $setting = Setting::first();
        $this->record = $setting;
        $this->record->url_env = $envs->getValue('FILAMENT_BASE_URL');
        $this->form->fill($setting->toArray());
    }

    /*** @return void
     */
    public function save()
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

            // Obtém os dados validados e processados do formulário
            $data = $this->form->getState();
            $setting = Setting::find($this->record->id);

            // Processamento de Uploads
            $favicon = $data['software_favicon'] ?? null;
            $logoWhite = $data['software_logo_white'] ?? null;
            $logoBlack = $data['software_logo_black'] ?? null;
            $softwareBackground = $data['software_background'] ?? null;

            if (is_array($softwareBackground) || is_object($softwareBackground)) {
                if (! empty($softwareBackground)) {
                    $data['software_background'] = $this->uploadFile($softwareBackground);

                    if (is_array($data['software_background'])) {
                        unset($data['software_background']);
                    }
                }
            }

            if (is_array($favicon) || is_object($favicon)) {
                if (! empty($favicon)) {
                    $data['software_favicon'] = $this->uploadFile($favicon);

                    if (is_array($data['software_favicon'])) {
                        unset($data['software_favicon']);
                    }
                }
            }

            if (is_array($logoWhite) || is_object($logoWhite)) {
                if (! empty($logoWhite)) {
                    $data['software_logo_white'] = $this->uploadFile($logoWhite);

                    if (is_array($data['software_logo_white'])) {
                        unset($data['software_logo_white']);
                    }
                }
            }

            if (is_array($logoBlack) || is_object($logoBlack)) {
                if (! empty($logoBlack)) {
                    $data['software_logo_black'] = $this->uploadFile($logoBlack);

                    if (is_array($data['software_logo_black'])) {
                        unset($data['software_logo_black']);
                    }
                }
            }

            // Atualização do .env
            $envs = DotenvEditor::load(base_path('.env'));

            $envs->setKeys([
                'APP_NAME' => $data['software_name'],
                'FILAMENT_BASE_URL' => $data['url_env'],
            ]);

            $envs->save();

            // Trata o mines_win_chance para salvar null quando vazio
            if (array_key_exists('mines_win_chance', $data) && $data['mines_win_chance'] === '') {
                $data['mines_win_chance'] = null;
            }

            // Trata o chicken_win_chance para salvar null quando vazio
            if (array_key_exists('chicken_win_chance', $data) && $data['chicken_win_chance'] === '') {
                $data['chicken_win_chance'] = null;
            }

            // Atualiza o registro no banco
            if ($setting->update($data)) {
                Cache::put('setting', $setting);

                Notification::make()
                    ->title('Dados alterados')
                    ->body('Dados alterados com sucesso!')
                    ->success()
                    ->send();

                redirect(route('filament.admin.resources.settings.index'));
            }
        } catch (Halt $exception) {
            return;
        } catch (\Illuminate\Validation\ValidationException $exception) {
            // Se houver erro de validação, o Filament já exibe as mensagens no formulário
            return;
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Ajuste Visual')
                    ->description('Formulário ajustar o visual da plataforma')
                    ->schema([
                        Group::make()->schema([
                            TextInput::make('software_name')
                                ->label('Nome')
                                ->placeholder('Digite o nome do site')
                                ->required()
                                ->maxLength(191),
                            TextInput::make('software_description')
                                ->placeholder('Digite a descrição do site')
                                ->label('Descrição')
                                ->maxLength(191),
                            TextInput::make('url_env')
                                ->label('URL')
                                ->placeholder('Digite a url que o painel admin vai ter')
                                ->required()
                                ->maxLength(191),
                        ])->columns(2),
                        Group::make()->schema([
                            FileUpload::make('software_favicon')
                                ->label('Favicon')
                                ->placeholder('Carregue um favicon')
                                ->image(),
                            Group::make()->schema([
                                FileUpload::make('software_logo_white')
                                    ->label('Logo Branca')
                                    ->placeholder('Carregue uma logo branca')
                                    ->image()
                                    ->columnSpanFull(),
                                FileUpload::make('software_logo_black')
                                    ->label('Logo Escura')
                                    ->placeholder('Carregue uma logo escura')
                                    ->image()
                                    ->columnSpanFull(),
                                //                                FileUpload::make('software_background')
                                //                                    ->label('Background')
                                //                                    ->placeholder('Carregue um background')
                                //                                    ->image()
                                //                                    ->columnSpanFull(),
                            ]),
                        ])->columns(2),
                    ]),
                Section::make('Configurações de Segurança')
                    ->description('Configurações de verificação e segurança')
                    ->schema([
                        Toggle::make('kyc_required')
                            ->label('Verificação KYC Obrigatória')
                            ->helperText('Quando desabilitado, usuários podem sacar sem verificação de documentos')
                            ->inline(false),
                    ])->columns(1),
                Section::make('Configurações de Jogos')
                    ->description('Configurações globais para os jogos')
                    ->schema([
                        TextInput::make('mines_win_chance')
                            ->label('Chance Global de Vitória no Mines (%)')
                            ->helperText('Defina a porcentagem global de chance de vitória (0-100). Usado se o usuário não tiver configuração específica. Se ambos nulos = Aleatório.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->nullable()
                            ->placeholder('Aleatório (Padrão)'),
                        TextInput::make('chicken_win_chance')
                            ->label('Chance Global de Vitória no Chicken (%)')
                            ->helperText('Defina a porcentagem global de chance de vitória (0-100). Usado se o usuário não tiver configuração específica. Se ambos nulos = Aleatório.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->nullable()
                            ->placeholder('Aleatório (Padrão)'),
                    ])->columns(1),
            ])
            ->statePath('data');
    }

    /**
     * @return mixed|void
     */
    private function uploadFile($array)
    {
        if (! empty($array) && is_array($array) || ! empty($array) && is_object($array)) {
            foreach ($array as $k => $temporaryFile) {
                if ($temporaryFile instanceof TemporaryUploadedFile) {
                    $path = \Helper::upload($temporaryFile);
                    if ($path) {
                        return $path['path'];
                    }
                } else {
                    return $temporaryFile;
                }
            }
        }
    }
}
