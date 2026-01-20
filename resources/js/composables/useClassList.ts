// resources/js/composables/useClassList.ts

import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'

export function useClassList() {
  // State
  const showAddClass = ref(false)
  const showEditClass = ref(false)
  const showDeleteConfirm = ref(false)

  const selectedClass = ref<any | null>(null)
  const selectedClassId = ref<string | null>(null)

  // Sections (static sample, or fetch from API)
  const sections = ref([
    { id: 1, name: 'BSCS 1-A' },
    { id: 2, name: 'BSCS 2-B' },
  ])

  // Create
  const createClass = (form: any, reset: () => void) => {
    router.post('/classlist/add', form, {
      onSuccess: () => {
        toast('Class has been created', {
          description: new Intl.DateTimeFormat('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: '2-digit',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
          }).format(new Date()),
          action: {
            label: 'Undo',
            onClick: () => console.log('Undo'),
          },
        })
        reset()
        showAddClass.value = false
      },
      onError: () => toast.error('Failed to create class')
    })
  }

  // Edit
  const openEditClass = (classItem: any) => {
    selectedClass.value = classItem
    showEditClass.value = true
  }

  const updateClass = (form: any) => {
    router.put(`/classlist/${selectedClass.value.id}`, form, {
      onSuccess: () => {
        toast('Class has been updated', {
          description: new Intl.DateTimeFormat('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: '2-digit',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
          }).format(new Date()),
          action: {
            label: 'Undo',
            onClick: () => console.log('Undo'),
          },
        })
        showEditClass.value = false
        selectedClass.value = null
      },
      onError: () => toast.error('Failed to update class')
    })
  }

  // Delete
  const confirmDelete = (id: string) => {
    selectedClassId.value = id
    showDeleteConfirm.value = true
  }

  const deleteClass = () => {
    if (!selectedClassId.value) return

    router.delete(`/classlist/${selectedClassId.value}`, {
      onSuccess: () => {
        toast.success('Class deleted successfully.')
        showDeleteConfirm.value = false
        selectedClassId.value = null
      },
      onError: () => toast.error('Failed to delete class')
    })
  }

  return {
    // State
    showAddClass,
    showEditClass,
    showDeleteConfirm,
    selectedClass,
    selectedClassId,
    sections,

    // Actions
    createClass,
    openEditClass,
    updateClass,
    confirmDelete,
    deleteClass,
  }
}
