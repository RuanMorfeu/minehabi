<script>
    // Função para verificar se há um arquivo ZIP para download na sessão
    document.addEventListener('DOMContentLoaded', function() {
        // Ouvir eventos de notificação do Filament
        document.addEventListener('notify', function(event) {
            // Verificar se é uma notificação de sucesso relacionada ao download
            if (event.detail.type === 'success' && 
                event.detail.message.includes('Listas geradas com sucesso')) {
                // Aguardar um momento para garantir que o arquivo esteja pronto
                setTimeout(function() {
                    // Redirecionar para a rota de download
                    window.location.href = '{{ route("download.zip") }}';
                }, 1500);
            }
        });
    });
</script>
