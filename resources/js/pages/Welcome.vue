<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

/**
 * Define the expected shape of the menu node.
 * This ensures type safety and autocompletion.
 */
type Node = {
    id: number | string
    label: string
    url?: string | null
    is_visible: boolean
    children?: Node[]
    linkable_type: 'custom' | 'App\\Models\\Page' | 'App\\Models\\Post' | 'none'
    linkable_id?: number | null
    slug?: string
}

// Access Inertia page props, ensuring type safety with a generic type.
const page = usePage<{
    menu?: { tree?: Node[] }
    auth: { user: any | null }
}>()

// --- Menu Helpers ---

/**
 * Accesses the raw menu tree from the shared props.
 * It provides a fallback to an empty array if the menu data isn't available.
 */
const rawTree = computed<Node[]>(
    () => page.props.menu?.tree ?? []
)

/**
 * Generates an appropriate href for a given menu node.
 * It intelligently resolves links based on their type (custom, page, or post).
 */
function resolveHref(node: Node): string {
    switch (node.linkable_type) {
        case 'custom':
            return node.url || '#'
        case 'App\\Models\\Page':
            return node.slug ? route('pages.show', node.slug) : '#'
        case 'App\\Models\\Post':
            return node.slug ? route('posts.show', node.slug) : '#'
        default:
            return '#'
    }
}

/**
 * Determines if a node has visible children to show in a dropdown.
 */
function hasVisibleChildren(node: Node): boolean {
    return Array.isArray(node.children) && node.children.some(c => c.is_visible)
}

/**
 * Processes the menu tree to prepare it for rendering.
 * It filters for visible items and ensures a "Home" link is always first.
 */
const topItems = computed<Node[]>(() => {
    const visibleItems = rawTree.value.filter(n => n.is_visible)

    // Find a node that looks like a homepage.
    const homeIndex = visibleItems.findIndex(n => {
        const label = (n.label || '').toLowerCase()
        const slug = (n.slug || '').toLowerCase()
        return label === 'home' || label === 'accueil' || slug === 'home' || slug === 'accueil'
    })

    // Create a mutable copy of the visible items.
    const orderedItems = [...visibleItems]

    // If a home page is found, move it to the beginning.
    if (homeIndex > 0) {
        const [homeItem] = orderedItems.splice(homeIndex, 1)
        orderedItems.unshift(homeItem)
    }
    // If no home page is found, inject a default one.
    else if (homeIndex === -1) {
        orderedItems.unshift({
            id: '__home__',
            label: 'Home',
            is_visible: true,
            linkable_type: 'custom',
            url: '/',
            children: [],
        } as Node)
    }

    return orderedItems
})
</script>

<template>

    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
        <header class="mb-6 w-full max-w-[335px] text-sm lg:max-w-4xl">
            <nav class="flex items-center justify-between gap-4">
                <ul class="flex items-center gap-2">
                    <li v-for="item in topItems" :key="item.id" class="group relative">
                        <Link :href="resolveHref(item)"
                            class="inline-block rounded-sm px-3 py-1.5 leading-normal text-[#1b1b18] hover:bg-black/5 dark:text-[#EDEDEC] dark:hover:bg-white/10">
                        {{ item.label }}
                        </Link>

                        <template v-if="hasVisibleChildren(item)">
                            <ul
                                class="invisible absolute left-0 z-20 mt-1 w-56 rounded-md border border-black/10 bg-white p-1 opacity-0 shadow-md transition-all group-hover:visible group-hover:opacity-100 dark:border-white/10 dark:bg-[#161615]">
                                <li v-for="child in item.children?.filter(c => c.is_visible) ?? []" :key="child.id">
                                    <Link :href="resolveHref(child)"
                                        class="block rounded-sm px-3 py-2 text-sm text-[#1b1b18] hover:bg-black/5 dark:text-[#EDEDEC] dark:hover:bg-white/10">
                                    {{ child.label }}
                                    </Link>

                                    <ul v-if="hasVisibleChildren(child)"
                                        class="ml-3 border-l border-black/10 pl-2 dark:border-white/10">
                                        <li v-for="gchild in child.children?.filter(gc => gc.is_visible) ?? []"
                                            :key="gchild.id">
                                            <Link :href="resolveHref(gchild)"
                                                class="block rounded-sm px-3 py-1.5 text-sm text-[#1b1b18] hover:bg-black/5 dark:text-[#EDEDEC] dark:hover:bg-white/10">
                                            {{ gchild.label }}
                                            </Link>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </template>
                    </li>
                </ul>

                <div class="flex items-center gap-2">
                    <Link v-if="page.props.auth?.user" :href="route('dashboard')"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]">
                    Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="route('login')"
                            class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]">
                        Log in
                        </Link>
                        <Link :href="route('register')"
                            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]">
                        Register
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <div
            class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
            <main
                class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row">
                <div
                    class="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
                    <h1 class="mb-1 font-medium">Let's get started</h1>
                    <p class="mb-2 text-[#706f6c] dark:text-[#A1A09A]">
                        Laravel has an incredibly rich ecosystem. <br />We suggest starting with the following.
                    </p>
                    <ul class="mb-4 flex flex-col lg:mb-6">
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:top-1/2 before:bottom-0 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]">
                            <span class="relative bg-white py-1 dark:bg-[#161615]">
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A]" />
                                </span>
                            </span>
                            <span>
                                Read the
                                <a href="https://laravel.com/docs" target="_blank"
                                    class="ml-1 inline-flex items-center space-x-1 font-medium text-[#f53003] underline underline-offset-4 dark:text-[#FF4433]">
                                    <span>Documentation</span>
                                    <svg width="10" height="11" viewBox="0 0 10 11" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5">
                                        <path d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001"
                                            stroke="currentColor" stroke-linecap="square" />
                                    </svg>
                                </a>
                            </span>
                        </li>
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:top-0 before:bottom-1/2 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]">
                            <span class="relative bg-white py-1 dark:bg-[#161615]">
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A]" />
                                </span>
                            </span>
                            <span>
                                Watch video tutorials at
                                <a href="https://laracasts.com" target="_blank"
                                    class="ml-1 inline-flex items-center space-x-1 font-medium text-[#f53003] underline underline-offset-4 dark:text-[#FF4433]">
                                    <span>Laracasts</span>
                                    <svg width="10" height="11" viewBox="0 0 10 11" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5">
                                        <path d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001"
                                            stroke="currentColor" stroke-linecap="square" />
                                    </svg>
                                </a>
                            </span>
                        </li>
                    </ul>
                    <ul class="flex gap-3 text-sm leading-normal">
                        <li>
                            <a href="https://cloud.laravel.com" target="_blank"
                                class="inline-block rounded-sm border border-black bg-[#1b1b18] px-5 py-1.5 text-sm leading-normal text-white hover:border-black hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white">
                                Deploy now
                            </a>
                        </li>
                    </ul>
                </div>
                <div
                    class="relative -mb-px aspect-335/376 w-full shrink-0 overflow-hidden rounded-t-lg bg-[#fff2f2] lg:mb-0 lg:-ml-px lg:aspect-auto lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg dark:bg-[#1D0002]">
                    <div
                        class="absolute inset-0 rounded-t-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:overflow-hidden lg:rounded-t-none lg:rounded-r-lg dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]" />
                </div>
            </main>
        </div>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
```
