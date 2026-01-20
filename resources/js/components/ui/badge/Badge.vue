<template>
  <div
    :class="[
      'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
      variantClasses[variant ?? 'default']
    ]"
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
interface Props {
  variant?: 'default' | 'secondary' | 'destructive' | 'success' | 'outline'
}

const props = defineProps<Props>()

const variantClasses = {
  default: 'bg-primary border-transparent text-primary-foreground hover:bg-primary/80',
  secondary: 'bg-secondary border-transparent text-secondary-foreground hover:bg-secondary/80',
  destructive: 'bg-destructive border-transparent text-white hover:bg-destructive/80',
  success: 'bg-green-600 border-transparent text-white hover:bg-green-600/80',
  outline: 'border-input bg-transparent hover:bg-accent hover:text-accent-foreground'
}
</script>
