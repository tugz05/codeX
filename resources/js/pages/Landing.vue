<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { GraduationCap, BookOpen, Award, Users, ClipboardCheck, Shield, TrendingUp, LogIn, Sparkles, ArrowRight } from 'lucide-vue-next'
import AppLogoIcon from '@/components/AppLogoIcon.vue'
import { ref, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps<{
  leaderboard: Array<{
    classlist: {
      id: string
      name: string
      section: string | null
    }
    top_students: Array<{
      user_id: number
      name: string
      email: string
      average_percentage: number
      total_attempts: number
    }>
  }>
}>()

const visibleSections = ref<Set<string>>(new Set())
const observer = ref<IntersectionObserver | null>(null)

onMounted(() => {
  // Create intersection observer for scroll animations
  observer.value = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting && entry.target.id) {
          visibleSections.value.add(entry.target.id)
        }
      })
    },
    { threshold: 0.1, rootMargin: '0px 0px -50px 0px' }
  )

  // Observe all animated sections after a small delay to ensure DOM is ready
  setTimeout(() => {
    document.querySelectorAll('[data-animate]').forEach((el) => {
      if (el.id) {
        observer.value?.observe(el)
      }
    })
  }, 100)
})

onBeforeUnmount(() => {
  observer.value?.disconnect()
})

function getRankBadge(index: number) {
  if (index === 0) return { text: '🥇', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' }
  if (index === 1) return { text: '🥈', class: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300' }
  if (index === 2) return { text: '🥉', class: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300' }
  return { text: `${index + 1}`, class: 'bg-muted text-muted-foreground' }
}

function isVisible(id: string): boolean {
  return visibleSections.value.has(id)
}
</script>

<template>
  <Head title="codeX - Learn, Code, Excel" />

  <div class="min-h-screen bg-background">
    <!-- Navigation -->
    <nav class="sticky top-0 z-50 border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 transition-all duration-300">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
          <div class="flex items-center gap-2 transition-transform duration-300 hover:scale-105">
            <AppLogoIcon class="h-5 w-7 text-foreground" />
            <span class="text-xl font-bold text-foreground">codeX</span>
          </div>
          <div class="flex items-center gap-4">
            <Link v-if="$page.props.auth?.user" :href="route($page.props.auth.user.account_type === 'student' ? 'student.classlist' : 'instructor.classlist')">
              <Button variant="ghost" class="transition-all duration-200 hover:scale-105">Dashboard</Button>
            </Link>
            <template v-else>
              <Link :href="route('login')">
                <Button variant="ghost" class="transition-all duration-200 hover:scale-105">Log in</Button>
              </Link>
              <Link :href="route('register')">
                <Button class="transition-all duration-200 hover:scale-105 hover:shadow-lg">Sign up</Button>
              </Link>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative overflow-hidden border-b border-border bg-gradient-to-b from-background via-background to-muted/20 py-20 sm:py-32">
      <!-- Animated background gradient -->
      <div class="absolute inset-0 -z-10">
        <div class="absolute left-1/4 top-1/4 h-96 w-96 rounded-full bg-primary/10 blur-3xl animate-pulse"></div>
        <div class="absolute right-1/4 bottom-1/4 h-96 w-96 rounded-full bg-primary/5 blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
      </div>
      
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl text-center">
          <div 
            id="hero-title"
            data-animate
            :class="[
              'transition-all duration-1000',
              isVisible('hero-title') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'
            ]"
          >
            <h1 class="text-4xl font-bold tracking-tight sm:text-6xl lg:text-7xl">
              Learn, Code,
              <span class="relative inline-block">
                <span class="bg-gradient-to-r from-primary via-primary/80 to-primary bg-clip-text text-transparent animate-gradient">
                  Excel
                </span>
                <Sparkles class="absolute -right-8 -top-2 h-6 w-6 text-primary animate-bounce sm:h-8 sm:w-8" style="animation-delay: 0.5s;" />
              </span>
            </h1>
          </div>
          
          <p 
            id="hero-description"
            data-animate
            :class="[
              'mt-6 text-lg leading-8 text-muted-foreground sm:text-xl transition-all duration-1000 delay-200',
              isVisible('hero-description') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'
            ]"
          >
            A comprehensive learning management system for coding education. Take quizzes, complete activities, and track your progress as you master programming.
          </p>
          
          <div 
            id="hero-cta"
            data-animate
            :class="[
              'mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 transition-all duration-1000 delay-300',
              isVisible('hero-cta') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'
            ]"
          >
            <Link :href="route('register')">
              <Button size="lg" class="text-base group relative overflow-hidden transition-all duration-300 hover:scale-105 hover:shadow-xl">
                <span class="relative z-10 flex items-center gap-2">
                  Get Started
                  <ArrowRight class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                </span>
                <span class="absolute inset-0 bg-gradient-to-r from-primary/20 to-primary/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
              </Button>
            </Link>
            <a :href="route('google.redirect')">
              <Button size="lg" variant="outline" class="text-base transition-all duration-300 hover:scale-105 hover:shadow-lg border-2">
                <svg class="mr-2 h-5 w-5 transition-transform duration-300 group-hover:scale-110" viewBox="0 0 24 24">
                  <path
                    fill="currentColor"
                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                  />
                  <path
                    fill="currentColor"
                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                  />
                  <path
                    fill="currentColor"
                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                  />
                  <path
                    fill="currentColor"
                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                  />
                </svg>
                Sign in with Google
              </Button>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 sm:py-32">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div 
          id="features-title"
          data-animate
          :class="[
            'mx-auto max-w-2xl text-center transition-all duration-1000',
            isVisible('features-title') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'
          ]"
        >
          <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Everything you need to excel</h2>
          <p class="mt-4 text-lg text-muted-foreground">
            Powerful features designed to enhance your learning experience
          </p>
        </div>
        <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-8 sm:grid-cols-2 lg:max-w-7xl lg:grid-cols-3">
          <Card 
            v-for="(feature, index) in [
              { icon: Code, title: 'Coding Activities', desc: 'Practice coding with real-time execution and AI-powered feedback' },
              { icon: ClipboardCheck, title: 'Quizzes & Exams', desc: 'Test your knowledge with timed quizzes and comprehensive examinations' },
              { icon: Award, title: 'AI-Powered Grading', desc: 'Get instant, detailed feedback on your essays and code submissions' },
              { icon: TrendingUp, title: 'Progress Tracking', desc: 'Monitor your performance and see how you rank among your peers' },
              { icon: Shield, title: 'Anti-Cheating System', desc: 'Advanced monitoring ensures fair and honest assessments' },
              { icon: Users, title: 'Class Management', desc: 'Organize your classes, manage students, and track engagement' }
            ]"
            :key="index"
            :id="`feature-${index}`"
            data-animate
            :class="[
              'border-2 transition-all duration-500 hover:scale-105 hover:shadow-xl cursor-pointer group',
              isVisible(`feature-${index}`) ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'
            ]"
            :style="{ transitionDelay: `${index * 100}ms` }"
          >
            <CardHeader>
              <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg border-2 transition-all duration-300 group-hover:border-primary group-hover:bg-primary/5">
                <component :is="feature.icon" class="h-6 w-6 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3" />
              </div>
              <CardTitle class="transition-colors duration-300 group-hover:text-primary">{{ feature.title }}</CardTitle>
              <CardDescription class="mt-2">
                {{ feature.desc }}
              </CardDescription>
            </CardHeader>
          </Card>
        </div>
      </div>
    </section>

    <!-- Leaderboard Section -->
    <section class="border-t border-border bg-muted/30 py-20 sm:py-32">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div 
          id="leaderboard-title"
          data-animate
          :class="[
            'mx-auto max-w-2xl text-center transition-all duration-1000',
            isVisible('leaderboard-title') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'
          ]"
        >
          <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Top Performers</h2>
          <p class="mt-4 text-lg text-muted-foreground">
            See who's excelling in different subjects
          </p>
        </div>

        <div v-if="props.leaderboard && props.leaderboard.length > 0" class="mx-auto mt-16 grid max-w-7xl gap-8 sm:grid-cols-2 lg:grid-cols-3">
          <Card 
            v-for="(item, cardIndex) in props.leaderboard" 
            :key="item.classlist.id" 
            :id="`leaderboard-${cardIndex}`"
            data-animate
            :class="[
              'border-2 transition-all duration-500 hover:scale-105 hover:shadow-xl group',
              isVisible(`leaderboard-${cardIndex}`) ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'
            ]"
            :style="{ transitionDelay: `${cardIndex * 150}ms` }"
          >
            <CardHeader>
              <div class="flex items-center gap-2">
                <BookOpen class="h-5 w-5 transition-transform duration-300 group-hover:rotate-12" />
                <CardTitle class="text-lg">{{ item.classlist.name }}</CardTitle>
              </div>
              <CardDescription v-if="item.classlist.section">
                {{ item.classlist.section }}
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div
                  v-for="(student, index) in item.top_students"
                  :key="student.user_id"
                  class="flex items-center justify-between rounded-lg border-2 p-3 transition-all duration-300 hover:border-primary hover:bg-primary/5 hover:shadow-md"
                  :style="{ transitionDelay: `${index * 50}ms` }"
                >
                  <div class="flex items-center gap-3">
                    <Badge :class="getRankBadge(index).class" class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition-transform duration-300 hover:scale-110">
                      {{ getRankBadge(index).text }}
                    </Badge>
                    <div>
                      <p class="font-semibold transition-colors duration-300 group-hover:text-primary">{{ student.name }}</p>
                      <p class="text-xs text-muted-foreground">{{ student.email }}</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="font-bold text-lg transition-colors duration-300 group-hover:text-primary">{{ student.average_percentage.toFixed(1) }}%</p>
                    <p class="text-xs text-muted-foreground">{{ student.total_attempts }} attempt{{ student.total_attempts !== 1 ? 's' : '' }}</p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <div 
          v-else 
          id="leaderboard-empty"
          data-animate
          :class="[
            'mx-auto mt-16 max-w-2xl text-center transition-all duration-1000',
            isVisible('leaderboard-empty') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'
          ]"
        >
          <Card class="border-2">
            <CardContent class="py-12">
              <GraduationCap class="mx-auto h-12 w-12 text-muted-foreground animate-bounce" />
              <p class="mt-4 text-muted-foreground">No rankings available yet. Be the first to excel!</p>
            </CardContent>
          </Card>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="relative border-t border-border py-20 sm:py-32 overflow-hidden">
      <!-- Animated background -->
      <div class="absolute inset-0 -z-10">
        <div class="absolute left-1/3 top-1/2 h-64 w-64 rounded-full bg-primary/10 blur-3xl animate-pulse"></div>
        <div class="absolute right-1/3 bottom-1/2 h-64 w-64 rounded-full bg-primary/5 blur-3xl animate-pulse" style="animation-delay: 1.5s;"></div>
      </div>
      
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div 
          id="cta-content"
          data-animate
          :class="[
            'mx-auto max-w-2xl text-center transition-all duration-1000',
            isVisible('cta-content') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'
          ]"
        >
          <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Ready to get started?</h2>
          <p class="mt-4 text-lg text-muted-foreground">
            Join thousands of students already learning and excelling on codeX
          </p>
          <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
            <Link :href="route('register')">
              <Button size="lg" class="text-base group relative overflow-hidden transition-all duration-300 hover:scale-105 hover:shadow-xl">
                <span class="relative z-10 flex items-center gap-2">
                  Create Account
                  <ArrowRight class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                </span>
                <span class="absolute inset-0 bg-gradient-to-r from-primary/20 to-primary/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
              </Button>
            </Link>
            <a :href="route('google.redirect')">
              <Button size="lg" variant="outline" class="text-base transition-all duration-300 hover:scale-105 hover:shadow-lg border-2 group">
                <svg class="mr-2 h-5 w-5 transition-transform duration-300 group-hover:scale-110" viewBox="0 0 24 24">
                  <path
                    fill="currentColor"
                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                  />
                  <path
                    fill="currentColor"
                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                  />
                  <path
                    fill="currentColor"
                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                  />
                  <path
                    fill="currentColor"
                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                  />
                </svg>
                Sign in with Google
              </Button>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-border bg-muted/30 py-12">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
          <div class="flex items-center gap-2 transition-transform duration-300 hover:scale-105">
            <Code class="h-5 w-5" />
            <span class="font-semibold">codeX</span>
          </div>
          <div class="flex flex-col items-end gap-1 text-right">
            <p class="text-sm text-muted-foreground">
              Developed by: <span class="font-semibold">Virgilio F. Tuga Jr.</span>
            </p>
            <p class="text-sm text-muted-foreground">
              © {{ new Date().getFullYear() }} codeX. All rights reserved.
            </p>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
@keyframes gradient {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

.animate-gradient {
  background-size: 200% 200%;
  animation: gradient 3s ease infinite;
}

/* Smooth scroll behavior */
html {
  scroll-behavior: smooth;
}
</style>
