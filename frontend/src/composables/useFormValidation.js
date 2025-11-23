import { ref, computed } from 'vue'

export function useFormValidation(initialValues = {}, validationRules = {}) {
  const values = ref({ ...initialValues })
  const errors = ref({})
  const touched = ref({})

  const isValid = computed(() => {
    return Object.keys(errors.value).length === 0
  })

  const validate = (field) => {
    const rules = validationRules[field]
    if (!rules) return

    const value = values.value[field]
    let error = null

    for (const rule of rules) {
      error = rule(value)
      if (error) break
    }

    if (error) {
      errors.value[field] = error
    } else {
      delete errors.value[field]
    }
  }

  const validateAll = () => {
    Object.keys(validationRules).forEach(field => {
      validate(field)
    })
    return isValid.value
  }

  const handleBlur = (field) => {
    touched.value[field] = true
    validate(field)
  }

  const handleInput = (field, value) => {
    values.value[field] = value
    if (touched.value[field]) {
      validate(field)
    }
  }

  const resetForm = () => {
    values.value = { ...initialValues }
    errors.value = {}
    touched.value = {}
  }

  return {
    values,
    errors,
    touched,
    isValid,
    validate,
    validateAll,
    handleBlur,
    handleInput,
    resetForm
  }
}

// Validation rules helpers
export const validators = {
  required: (message = 'This field is required') => (value) => {
    return value ? null : message
  },
  email: (message = 'Invalid email address') => (value) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(value) ? null : message
  },
  minLength: (min, message) => (value) => {
    return value && value.length >= min ? null : message || `Minimum ${min} characters required`
  },
  maxLength: (max, message) => (value) => {
    return value && value.length <= max ? null : message || `Maximum ${max} characters allowed`
  },
  date: (message = 'Invalid date') => (value) => {
    return value && !isNaN(Date.parse(value)) ? null : message
  },
  afterDate: (compareDate, message) => (value) => {
    if (!value || !compareDate) return null
    return new Date(value) > new Date(compareDate) ? null : message || 'Date must be after the start date'
  }
}
