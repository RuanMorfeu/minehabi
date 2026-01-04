<?php

namespace App\Http\Controllers\Api\Profile;

use App\Helpers\R2Helper;
use App\Http\Controllers\Controller;
use App\Models\UserAccount;
use App\Models\UserDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    /**
     * Exibe o status da verificação do usuário
     */
    public function index()
    {
        try {
            $user = auth('api')->user();

            $userDocument = UserDocument::where('user_id', $user->id)->first();
            $userAccount = UserAccount::where('user_id', $user->id)->first();

            // Status unificado - prioriza UserAccount como status principal
            $verificationStatus = null;
            $rejectionReason = null;
            $verifiedAt = null;

            if ($userAccount) {
                $verificationStatus = $userAccount->status;
                $rejectionReason = $userAccount->rejection_reason;
                $verifiedAt = $userAccount->verified_at;
            } elseif ($userDocument) {
                // Fallback para documento se não houver conta
                $verificationStatus = $userDocument->verification_status;
                $rejectionReason = $userDocument->rejection_reason;
                $verifiedAt = $userDocument->verified_at;
            }

            // Lógica de can_resubmit baseada no status unificado
            $canResubmitValue = false;
            if ($verificationStatus === 'rejected') {
                // Se foi rejeitado, verifica se pode reenviar
                if ($userAccount && $userAccount->can_resubmit) {
                    // Verifica limite de tentativas no documento
                    $maxAttempts = config('kyc.max_submission_attempts', 3);
                    $submissionAttempts = $userDocument ? $userDocument->submission_attempts : 0;

                    if ($maxAttempts <= 0 || $submissionAttempts < $maxAttempts) {
                        // Verifica cooldown no documento
                        $cooldownHours = config('kyc.resubmission_cooldown_hours', 24);
                        $cooldownPassed = true;

                        if ($cooldownHours > 0 && $userDocument && $userDocument->last_submission_at) {
                            $cooldownEnds = $userDocument->last_submission_at->addHours($cooldownHours);
                            $cooldownPassed = now()->gte($cooldownEnds);
                        }

                        $canResubmitValue = $cooldownPassed;
                    }
                }
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'document' => $userDocument,
                    'account' => $userAccount,
                    'verification_status' => $verificationStatus,
                    'rejection_reason' => $rejectionReason,
                    'verified_at' => $verifiedAt,
                    // Informações adicionais para compatibilidade
                    'has_documents' => $userDocument ? true : false,
                    'document_type' => $userDocument ? $userDocument->document_type : null,
                    // Controle de reenvio
                    'can_resubmit' => $canResubmitValue,
                    'submission_attempts' => $userDocument ? $userDocument->submission_attempts : 0,
                    'max_attempts' => config('kyc.max_submission_attempts', 3),
                    'cooldown_hours' => $userDocument ? $userDocument->getResubmissionCooldownHours() : null,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar status de verificação: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor',
            ], 500);
        }
    }

    /**
     * Salva as informações pessoais do usuário
     */
    public function storePersonalInfo(Request $request)
    {
        try {
            $rules = [
                'full_name' => 'required|string|max:255',
                'document_number' => 'required|string|max:50',
                'birth_date' => 'required|date|before:today',
                'phone' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:5',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = auth('api')->user();

            // Verifica se já existe um registro
            $userAccount = UserAccount::where('user_id', $user->id)->first();

            $data = $request->only([
                'full_name', 'document_number', 'birth_date', 'phone', 'country',
            ]);

            $data['user_id'] = $user->id;
            $data['country'] = $data['country'] ?? 'PT';

            if ($userAccount) {
                // Atualiza o registro existente
                $userAccount->update($data);
            } else {
                // Cria um novo registro
                $userAccount = UserAccount::create($data);
            }

            return response()->json([
                'status' => true,
                'message' => 'Informações pessoais salvas com sucesso',
                'data' => $userAccount,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao salvar informações pessoais: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor',
            ], 500);
        }
    }

    /**
     * Faz upload dos documentos de verificação
     */
    public function uploadDocuments(Request $request)
    {
        try {
            $rules = [
                // Dados pessoais (opcionais se já foram salvos)
                'full_name' => 'nullable|string|max:255',
                'birth_date' => 'nullable|date|before:today',
                'document_number' => 'nullable|string|max:50',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',

                // Documentos (obrigatórios) - aceita todos os formatos de imagem
                'document_type' => 'required|in:cc,passport,carta_conducao',
                'document_front' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,webp,svg,tiff,tif,ico,heic,heif,raw,cr2,nef,arw,dng|max:10240', // 10MB
                'document_back' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,webp,svg,tiff,tif,ico,heic,heif,raw,cr2,nef,arw,dng|max:10240',
                'selfie' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,webp,svg,tiff,tif,ico,heic,heif,raw,cr2,nef,arw,dng|max:10240',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                Log::warning('KYC Upload: Validação falhou', [
                    'user_id' => auth('api')->id(),
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => [
                        'document_type' => $request->input('document_type'),
                        'has_document_front' => $request->hasFile('document_front'),
                        'has_document_back' => $request->hasFile('document_back'),
                        'has_selfie' => $request->hasFile('selfie'),
                        'document_front_size' => $request->hasFile('document_front') ? $request->file('document_front')->getSize() : null,
                        'document_back_size' => $request->hasFile('document_back') ? $request->file('document_back')->getSize() : null,
                        'selfie_size' => $request->hasFile('selfie') ? $request->file('selfie')->getSize() : null,
                        'document_front_mime' => $request->hasFile('document_front') ? $request->file('document_front')->getMimeType() : null,
                        'document_back_mime' => $request->hasFile('document_back') ? $request->file('document_back')->getMimeType() : null,
                        'selfie_mime' => $request->hasFile('selfie') ? $request->file('selfie')->getMimeType() : null,
                        'document_front_extension' => $request->hasFile('document_front') ? $request->file('document_front')->getClientOriginalExtension() : null,
                        'document_back_extension' => $request->hasFile('document_back') ? $request->file('document_back')->getClientOriginalExtension() : null,
                        'selfie_extension' => $request->hasFile('selfie') ? $request->file('selfie')->getClientOriginalExtension() : null,
                    ],
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Arquivos inválidos',
                    'errors' => $validator->errors(),
                    'debug' => [
                        'document_type' => $request->input('document_type'),
                        'files_received' => [
                            'document_front' => $request->hasFile('document_front'),
                            'document_back' => $request->hasFile('document_back'),
                            'selfie' => $request->hasFile('selfie'),
                        ],
                    ],
                ], 400);
            }

            $user = auth('api')->user();

            // Verifica se já existe um registro de documento e conta
            $userDocument = UserDocument::where('user_id', $user->id)->first();
            $userAccount = UserAccount::where('user_id', $user->id)->first();

            // Usar a mesma lógica unificada do frontend
            $canResubmit = false;
            $message = 'Não é possível reenviar documentos.';

            // Verifica se o status unificado permite reenvio
            if ($userAccount && $userAccount->status === 'rejected') {
                // Verifica se não foi bloqueado pelo admin
                if ($userAccount->can_resubmit) {
                    // Verifica limite de tentativas
                    $maxAttempts = config('kyc.max_submission_attempts', 3);
                    $submissionAttempts = $userDocument ? $userDocument->submission_attempts : 0;

                    if ($maxAttempts <= 0 || $submissionAttempts < $maxAttempts) {
                        // Verifica cooldown
                        $cooldownHours = config('kyc.resubmission_cooldown_hours', 24);
                        $cooldownPassed = true;

                        if ($cooldownHours > 0 && $userDocument && $userDocument->last_submission_at) {
                            $cooldownEnds = $userDocument->last_submission_at->addHours($cooldownHours);
                            $cooldownPassed = now()->gte($cooldownEnds);
                        }

                        $canResubmit = $cooldownPassed;

                        if (! $cooldownPassed) {
                            $remainingHours = $userDocument->getResubmissionCooldownHours();
                            $message = "Aguarde {$remainingHours} horas antes de reenviar documentos.";
                        }
                    } else {
                        $message = 'Limite de tentativas de envio excedido.';
                    }
                } else {
                    $message = 'Reenvio de documentos não permitido pelo administrador.';
                }
            } elseif ($userAccount && $userAccount->status === 'pending') {
                $message = 'Documentos já foram enviados e estão em análise.';
            } elseif ($userAccount && $userAccount->status === 'approved') {
                $message = 'Documentos já foram aprovados.';
            } elseif (! $userAccount && $userDocument && $userDocument->verification_status !== 'rejected') {
                $message = 'Documentos já foram enviados e estão em análise ou aprovados.';
            } else {
                // Primeira vez enviando ou sem restrições
                $canResubmit = true;
            }

            // Se não pode reenviar, retorna erro 403
            // IMPORTANTE: Só bloqueia se já existe UserDocument (documentos já foram enviados)
            if (! $canResubmit && $userDocument) {
                $cooldownHours = $userDocument->getResubmissionCooldownHours();

                return response()->json([
                    'status' => false,
                    'message' => $message,
                    'can_resubmit' => false,
                    'cooldown_hours' => $cooldownHours,
                ], 403);
            }

            // TRANSAÇÃO ATÔMICA - Todo o processo dentro de uma transação
            return \DB::transaction(function () use ($request, $user, $userDocument, $userAccount) {
                $uploadedFiles = [];
                $oldFilesToDelete = []; // Arquivos antigos para deletar apenas se tudo der certo
                $newFilesToCleanup = []; // Novos arquivos para limpar em caso de erro

                try {
                    // 1. SALVAR DADOS PESSOAIS (se fornecidos)
                    if ($request->filled('full_name') || $request->filled('birth_date') || $request->filled('document_number')) {
                        if (! $userAccount) {
                            $userAccount = new UserAccount;
                            $userAccount->user_id = $user->id;
                        }

                        // Atualizar apenas campos fornecidos (apenas campos que existem na tabela)
                        if ($request->filled('full_name')) {
                            $userAccount->full_name = $request->input('full_name');
                        }
                        if ($request->filled('birth_date')) {
                            $userAccount->birth_date = $request->input('birth_date');
                        }
                        if ($request->filled('document_number')) {
                            $userAccount->document_number = $request->input('document_number');
                        }
                        if ($request->filled('phone')) {
                            $userAccount->phone = $request->input('phone');
                        }
                        if ($request->filled('country')) {
                            $userAccount->country = $request->input('country');
                        }
                        // Campos address, city, postal_code não existem na tabela - removidos

                        $userAccount->status = 'pending';
                        $userAccount->save();

                        Log::info("KYC Upload: Dados pessoais salvos para usuário {$user->id}");
                    }

                    // 2. UPLOAD DOS ARQUIVOS
                    // Upload dos arquivos para o Cloudflare R2
                    $fileFields = ['document_front', 'document_back', 'selfie'];
                    $userId = $user->id;
                    $baseDirectory = "kyc/{$userId}";

                    Log::info("KYC Upload: Iniciando upload para usuário {$userId}");

                    foreach ($fileFields as $field) {
                        if ($request->hasFile($field)) {
                            // Usa o R2Helper para fazer upload do arquivo
                            $file = $request->file($field);
                            $extension = $file->getClientOriginalExtension();
                            $filename = $field.'_'.time().'_'.uniqid().'.'.$extension;

                            Log::info("KYC Upload: Fazendo upload do arquivo {$field} como {$filename}");

                            $path = R2Helper::uploadFile($file, $baseDirectory, $filename);

                            if (! $path) {
                                throw new \Exception("Falha ao fazer upload do arquivo {$field}");
                            }

                            // Verifica se o arquivo foi realmente salvo no R2
                            if (! R2Helper::fileExists($path)) {
                                throw new \Exception("Arquivo {$field} não foi salvo corretamente no R2");
                            }

                            $uploadedFiles[$field] = $path;
                            $newFilesToCleanup[] = $path;

                            Log::info("KYC Upload: Arquivo {$field} enviado com sucesso para {$path}");

                            // Marca arquivo antigo para exclusão (só deleta se tudo der certo)
                            if ($userDocument && ! empty($userDocument->$field)) {
                                $oldFilesToDelete[] = $userDocument->$field;
                            }
                        } elseif ($userDocument && ! empty($userDocument->$field)) {
                            // Mantém o arquivo existente se não for enviado um novo
                            $uploadedFiles[$field] = $userDocument->$field;
                            Log::info("KYC Upload: Mantendo arquivo existente para {$field}: {$userDocument->$field}");
                        }
                    }

                    // Verifica se todos os arquivos obrigatórios estão presentes
                    foreach ($fileFields as $field) {
                        if (empty($uploadedFiles[$field])) {
                            throw new \Exception("Arquivo obrigatório {$field} não foi fornecido");
                        }
                    }

                    $data = [
                        'user_id' => $user->id,
                        'document_type' => $request->document_type,
                        'document_front' => $uploadedFiles['document_front'],
                        'document_back' => $uploadedFiles['document_back'],
                        'selfie' => $uploadedFiles['selfie'],
                        'verification_status' => 'pending',
                        'rejection_reason' => null,
                        'verified_at' => null,
                    ];

                    if ($userDocument) {
                        // Verifica se é um reenvio após rejeição (usando status unificado)
                        $isResubmission = ($userAccount && $userAccount->status === 'rejected') ||
                                         ($userDocument->verification_status === 'rejected');

                        Log::info("KYC Upload: Atualizando UserDocument existente (ID: {$userDocument->id})");

                        // Atualiza o registro existente
                        $userDocument->update($data);

                        // Incrementa contador apenas se for reenvio após rejeição
                        if ($isResubmission) {
                            $userDocument->incrementSubmissionAttempts();
                            Log::info('KYC Upload: Incrementando tentativas (reenvio após rejeição)');
                        } else {
                            // Se não é reenvio, mantém o contador atual ou define como 1 se for 0
                            if ($userDocument->submission_attempts == 0) {
                                $userDocument->update([
                                    'submission_attempts' => 1,
                                    'last_submission_at' => now(),
                                ]);
                                Log::info('KYC Upload: Definindo primeira tentativa');
                            }
                        }
                    } else {
                        Log::info('KYC Upload: Criando novo UserDocument');

                        // Cria um novo registro
                        $userDocument = UserDocument::create($data);
                        // Define primeira tentativa
                        $userDocument->update([
                            'submission_attempts' => 1,
                            'last_submission_at' => now(),
                        ]);
                    }

                    // Atualiza o UserAccount para manter sincronização
                    if ($userAccount) {
                        Log::info("KYC Upload: Atualizando UserAccount (ID: {$userAccount->id})");
                        $userAccount->update([
                            'status' => 'pending',
                            'rejection_reason' => null,
                            'verified_at' => null,
                        ]);
                    }

                    // Se chegou até aqui, tudo deu certo - deleta arquivos antigos
                    foreach ($oldFilesToDelete as $oldFile) {
                        R2Helper::deleteFile($oldFile);
                        Log::info("KYC Upload: Arquivo antigo deletado: {$oldFile}");
                    }

                    Log::info("KYC Upload: Processo concluído com sucesso para usuário {$userId}");

                    return response()->json([
                        'status' => true,
                        'message' => 'Documentos enviados com sucesso! Aguarde a análise.',
                        'data' => $userDocument->fresh(),
                    ]);

                } catch (\Exception $e) {
                    Log::error("KYC Upload: Erro durante o processo: {$e->getMessage()}");

                    // Limpa arquivos recém-enviados em caso de erro
                    foreach ($newFilesToCleanup as $fileToCleanup) {
                        try {
                            R2Helper::deleteFile($fileToCleanup);
                            Log::info("KYC Upload: Arquivo limpo após erro: {$fileToCleanup}");
                        } catch (\Exception $cleanupError) {
                            Log::error("KYC Upload: Erro ao limpar arquivo {$fileToCleanup}: {$cleanupError->getMessage()}");
                        }
                    }

                    throw $e; // Re-throw para que a transação seja revertida
                }
            });

        } catch (\Exception $e) {
            Log::error('KYC Upload: Erro geral ao fazer upload dos documentos: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor',
            ], 500);
        }
    }
}
