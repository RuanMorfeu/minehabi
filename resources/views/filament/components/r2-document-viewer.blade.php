<div class="flex flex-col items-center">
    <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
        {{ $label ?? 'Documento' }}
    </div>
    
    @php
        // Obtém o documento do usuário
        $userDocument = $getRecord()->user->userDocument ?? null;
        $path = $userDocument ? $userDocument->{$urlAccessor} : null;
        $url = $path ? \App\Helpers\R2Helper::getTemporaryUrl($path) : null;
    @endphp
    
    @if ($url)
        <div class="relative w-full h-64 overflow-hidden rounded-lg border border-gray-300 dark:border-gray-700">
            <img 
                src="{{ $url }}" 
                alt="{{ $label ?? 'Documento' }}" 
                class="w-full h-full object-contain"
                loading="lazy"
            />
            
            <div class="absolute bottom-0 right-0 p-2">
                <a 
                    href="{{ $url }}" 
                    target="_blank" 
                    class="inline-flex items-center justify-center rounded-full bg-primary-500 px-3 py-1 text-sm font-medium text-white hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                >
                    <span class="mr-1">Ver</span>
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            </div>
        </div>
    @else
        <div class="w-full h-32 flex items-center justify-center rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-800">
            <span class="text-sm text-gray-500 dark:text-gray-400">Documento não enviado</span>
        </div>
    @endif
</div>
