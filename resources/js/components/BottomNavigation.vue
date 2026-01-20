<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { type NavItem } from '@/types';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    items: NavItem[];
    maxItems?: number;
}

const props = withDefaults(defineProps<Props>(), {
    maxItems: 5,
});

const page = usePage();

const isActive = (href: string) => {
    return page.url === href || page.url.startsWith(href + '/');
};

const displayItems = computed(() => {
    return props.items.slice(0, props.maxItems);
});
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 z-50 border-t border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 md:hidden">
        <div class="flex h-16 items-center justify-around">
            <Link
                v-for="item in displayItems"
                :key="item.href"
                :href="item.href"
                :class="cn(
                    'flex flex-col items-center justify-center gap-1 flex-1 h-full transition-colors',
                    isActive(item.href)
                        ? 'text-primary'
                        : 'text-muted-foreground hover:text-foreground'
                )"
            >
                <component :is="item.icon" class="h-5 w-5" />
                <span class="text-[10px] font-medium leading-tight">{{ item.title }}</span>
            </Link>
        </div>
    </nav>
</template>
