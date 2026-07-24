<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

// --- UI state only. Add your generation logic below. ---
const productName = ref('');
const details = ref('');

const result = ref('');
const error = ref('');
const loading = ref(false);

function generate() {
    loading.value = true;
    error.value = '';
    result.value = '';

    router.post(
        '/ai/generate-text',
        {
            name: productName.value, // must match the ProductRequest `name` rule
            details: details.value,
        },
        {
            preserveScroll: true,
            // router expects an Inertia response: controller should
            // return back()->with('result', $description) (flash shared via HandleInertiaRequests).
            onSuccess: (page) => {
                result.value =
                    (page.props.flash as { result?: string } | undefined)?.result ?? '';
            },
            onError: (errors) => {
                error.value =
                    (Object.values(errors)[0] as string) ?? 'An error occurred.';
            },
            onFinish: () => {
                loading.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="AI Test">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <main class="flex w-full max-w-2xl flex-col gap-6">
            <header class="flex flex-col gap-1">
                <h1 class="text-xl font-medium">AI Description Generator</h1>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Test harness for
                    <code
                        class="rounded bg-[#f4f4f2] px-1 py-0.5 text-[13px] dark:bg-[#1f1f1e]"
                        >POST /ai/generate-text</code
                    >
                </p>
            </header>

            <!-- Input form -->
            <form
                class="flex flex-col gap-4 rounded-lg border border-[#e3e3e0] bg-white p-6 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                @submit.prevent="generate"
            >
                <div class="flex flex-col gap-1.5">
                    <label
                        for="productName"
                        class="text-sm font-medium"
                        >Product name</label
                    >
                    <input
                        id="productName"
                        v-model="productName"
                        type="text"
                        placeholder="e.g. Wireless Headphones"
                        class="rounded-md border border-[#e3e3e0] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433]"
                    />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="details" class="text-sm font-medium"
                        >Details / keywords</label
                    >
                    <textarea
                        id="details"
                        v-model="details"
                        rows="4"
                        placeholder="Key features, materials, target audience…"
                        class="resize-y rounded-md border border-[#e3e3e0] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433]"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="loading"
                    class="self-start rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                >
                    {{ loading ? 'Generating…' : 'Generate' }}
                </button>
            </form>

            <!-- Error -->
            <p
                v-if="error"
                class="rounded-md border border-[#FF4433]/40 bg-[#FF4433]/10 px-4 py-2 text-sm text-[#f53003] dark:text-[#FF4433]"
            >
                {{ error }}
            </p>

            <!-- Result -->
            <section
                class="flex flex-col gap-2 rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]"
            >
                <h2 class="text-sm font-medium">Result</h2>
                <p
                    v-if="result"
                    class="text-sm whitespace-pre-wrap"
                >
                    {{ result }}
                </p>
                <p
                    v-else
                    class="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                >
                    The generated description will appear here.
                </p>
            </section>
        </main>
    </div>
</template>