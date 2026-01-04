<template>
    <BaseLayout>
        <div class="bonus-terms-container">
            <h1>Termos e Condições dos Bônus</h1>
            
            <h2>Uso do Bônus</h2>
            <p>O bônus pode ser utilizado para apostas em todos os jogos disponíveis no cassino, incluindo os jogos da categoria "Jogos de Habilidade" .</p>

            <h2>Rollover do Bônus</h2>
            <p>As apostas feitas em qualquer jogo, incluindo os "Jogos de Habilidade", não contribuem para o cumprimento do rollover do bônus. Ou seja, embora o bônus possa ser utilizado em todos os jogos, ele não ajudará a completar os requisitos de apostas necessários para a liberação do bônus.</p>

            <h2>Contribuição para o Rollover em Outros Jogos</h2>
            <p>O rollover do bônus será progressivamente acumulado apenas ao realizar apostas em jogos fora da categoria "Jogos de Habilidade" (como caça-níqueis, roleta, etc.), que têm sua contribuição para o rollover especificada nos termos gerais de bônus.</p>

            <h2>Limitação de Bônus</h2>
            <p>Todos os bônus são limitados a um por pessoa, por casa, por computador ou por IP, a menos que especificado de outra forma.</p>

            <h2>Cumprimento do Rollover</h2>
            <p>Todos os bônus estão sujeitos ao cumprimento do rollover. Os requisitos de apostas precisam ser atendidos antes que o bônus seja liberado para retirada.</p>

            <h2>Análise de Jogo</h2>
            <p>Antes de qualquer solicitação de retirada ser processada, o seu histórico de jogo será analisado para verificar a presença de padrões de jogo irregulares. Para garantir um jogo justo, apostas de margem igual, apostas de cobertura, apostas de baixo valor ou de alta variação serão consideradas como jogo irregular para fins de requisitos de bônus. Caso seja identificado que um jogo irregular ocorreu, o cassino se reserva o direito de reter retiradas e/ou confiscar os ganhos, além de cancelar bônus. O depósito poderá ser restabelecido na conta do jogador, sem necessidade de aviso prévio.</p>

            <h2>Fraude e Comportamento Irregular</h2>
            <p>A gestão do cassino se reserva o direito de anular quaisquer bônus e/ou ganhos obtidos por meio de comportamento fraudulento ou qualquer outra violação das regras de jogo justas, conforme descrito acima.</p>

            <h2>Alteração dos Termos</h2>
            <p>A administração do cassino se reserva o direito de atualizar ou alterar essas regras a qualquer momento, com ou sem aviso prévio.</p>

            <h2>Suporte</h2>
            <p>Para dúvidas sobre o uso do bônus ou os jogos elegíveis, entre em contato com nossa equipe de suporte.</p>
        </div>
    </BaseLayout>
</template>

<script>
import BaseLayout from "@/Layouts/BaseLayout.vue";

export default {
    props: [],
    components: { BaseLayout },
    data() {
        return {
            isLoading: false,
        };
    },
    setup(props) {
        return {};
    },
    computed: {},
    mounted() {},
    methods: {},
    watch: {},
};
</script>

<style scoped>
.bonus-terms-container {
    padding: 20px;
    font-family: Arial, sans-serif;
}

.bonus-terms-container h1 {
    font-size: 2rem;
    margin-bottom: 20px;
}

.bonus-terms-container h2 {
    font-size: 1.5rem;
    margin-top: 20px;
}

.bonus-terms-container p {
    font-size: 1rem;
    line-height: 1.5;
    margin-bottom: 10px;
}
</style>
