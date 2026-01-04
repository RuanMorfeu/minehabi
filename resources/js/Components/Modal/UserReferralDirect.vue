<script setup>
import Modal from "@/Components/Modal.vue";
import { onMounted, ref, watch } from "vue";
const closeModalRef = ref(false);
const props = defineProps({
    open: {
        required: false,
        default: false,
    },
});

const emit = defineEmits(["close"]);

watch(
    () => props.open,
    () => {
        closeModalRef.value = !props.open;
    },
);

const close = () => {
    if (props.closeable) {
        emit("close");
    }
};

const closeModalRefEvent = () => {
    closeModalRef.value = false;
};
</script>

<template>
    <Modal :show="open" modal-title="teste" @close="closeModalRefEvent">
        <div class="bg-zinc-900/90 backdrop-blur p-4 rounded">
            <div>
                <h4>Ganhe até 20,00 BRL por convidado.</h4>
                <h3>Convide Amigos, Ganhe Dinheiro</h3>
                <p>
                    Receba saldo em dinheiro para todos os amigos que você
                    indicar à Blaze.
                </p>
            </div>
            <div>Compartilhe o seu código de convite</div>
            <div>Seu Código de Referência</div>
        </div>
    </Modal>
</template>

<style scoped></style>
