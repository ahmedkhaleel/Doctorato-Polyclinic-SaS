<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: [File, String, null],
        default: null,
    },
    currentImage: {
        type: String,
        default: null,
    },
    label: {
        type: String,
        default: 'Image',
    },
    accept: {
        type: String,
        default: 'image/*',
    },
    error: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);

const preview = ref(null);
const fileInput = ref(null);

const displayImage = computed(() => {
    if (preview.value) return preview.value;
    if (props.currentImage) return `/storage/${props.currentImage}`;
    return null;
});

function handleFileChange(event) {
    const file = event.target.files[0];
    if (file) {
        emit('update:modelValue', file);
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    preview.value = null;
    emit('update:modelValue', null);
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}
</script>

<template>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ label }}</label>
        <div class="mt-1">
            <div v-if="displayImage" class="relative inline-block mb-2">
                <img :src="displayImage" :alt="label" class="h-32 w-32 object-cover rounded-lg border border-gray-200" />
                <button
                    type="button"
                    @click="removeImage"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600"
                >
                    &times;
                </button>
            </div>
            <input
                ref="fileInput"
                type="file"
                :accept="accept"
                @change="handleFileChange"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gold-primary file:text-white hover:file:bg-gold-dark cursor-pointer"
            />
        </div>
        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
