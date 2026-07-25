<script setup lang="ts">
import { Head, router, usePoll } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    jobId: string | null;
    state: {
        status: string;
        progress: number;
        step: string;
        generated_text: string | null;
        error?: string;
    } | null;
}>();

const name = ref('');
const details = ref('');
const formError = ref('');

const isProcessing = computed(
    () =>
        props.state?.status === 'pending' ||
        props.state?.status === 'processing',
);

// Refresh only the `state` prop while the job is running; Inertia handles CSRF.
const { start, stop } = usePoll(
    1500,
    { only: ['state'] },
    { autoStart: false },
);
watch(isProcessing, (running) => (running ? start() : stop()), {
    immediate: true,
});

function generate() {
    formError.value = '';
    router.post(
        '/ai/generate-text',
        { name: name.value, details: details.value },
        {
            onError: (errors) =>
                (formError.value = Object.values(errors)[0] ?? ''),
        },
    );
}
</script>

<template>
    <Head title="AI Test" />

    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <main class="flex w-full max-w-2xl flex-col gap-6">
            <h1 class="text-xl font-medium">AI Description Generator</h1>

            <form
                class="flex flex-col gap-4 rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]"
                @submit.prevent="generate"
            >
                <input
                    v-model="name"
                    type="text"
                    placeholder="Product name"
                    class="rounded-md border border-[#e3e3e0] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A]"
                />
                <textarea
                    v-model="details"
                    rows="4"
                    placeholder="Details / keywords"
                    class="resize-y rounded-md border border-[#e3e3e0] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A]"
                />
                <button
                    type="submit"
                    :disabled="isProcessing"
                    class="self-start rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                >
                    {{ isProcessing ? 'Generating…' : 'Generate' }}
                </button>
            </form>

            <!-- Progress -->
            <div v-if="isProcessing" class="flex flex-col gap-2">
                <div class="flex justify-between text-sm">
                    <span>{{ props.state?.step }}</span>
                    <span>{{ props.state?.progress }}%</span>
                </div>
                <div
                    class="h-2 overflow-hidden rounded-full bg-[#f4f4f2] dark:bg-[#1f1f1e]"
                >
                    <div
                        class="h-full rounded-full bg-[#f53003] transition-all dark:bg-[#FF4433]"
                        :style="{ width: `${props.state?.progress}%` }"
                    />
                </div>
            </div>

            <!-- Error -->
            <p
                v-if="formError || props.state?.status === 'failed'"
                class="rounded-md bg-[#FF4433]/10 px-4 py-2 text-sm text-[#f53003] dark:text-[#FF4433]"
            >
                {{ formError || props.state?.error || 'Generation failed.' }}
            </p>

            <!-- Result -->
            <p
                v-if="props.state?.status === 'completed'"
                class="rounded-lg border border-[#e3e3e0] bg-white p-6 text-sm whitespace-pre-wrap dark:border-[#3E3E3A] dark:bg-[#161615]"
            >
                {{ props.state?.generated_text }}
            </p>
        </main>
    </div>
</template>
