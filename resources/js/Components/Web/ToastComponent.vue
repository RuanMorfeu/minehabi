<template>
    <div class="fixed right-0 z-50">
        <fwb-toast closable> You've unlocked achievement. </fwb-toast>
        <fwb-toast closable type="warning">
            Improve password difficulty.
        </fwb-toast>
        <fwb-toast closable type="success">
            Item moved successfully.
        </fwb-toast>
        <fwb-toast closable type="danger"> Item has been deleted. </fwb-toast>
    </div>
</template>

<script setup>
import { FwbToast } from "flowbite-vue";
</script>
