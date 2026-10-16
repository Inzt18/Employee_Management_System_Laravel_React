import { useState } from 'react'
import { parseApiError } from '../utils/errors'

/**
 * Small form state helper: values, per-field errors from Laravel, and a submit wrapper.
 */
export default function useForm(initialValues) {
  const [values, setValues] = useState(initialValues)
  const [errors, setErrors] = useState({})
  const [message, setMessage] = useState(null)
  const [submitting, setSubmitting] = useState(false)

  const setField = (name, value) => {
    setValues((current) => ({ ...current, [name]: value }))
    setErrors((current) => ({ ...current, [name]: undefined }))
  }

  const handleChange = (event) => {
    const { name, value, type, files } = event.target
    setField(name, type === 'file' ? files[0] ?? null : value)
  }

  const submit = async (action) => {
    setSubmitting(true)
    setMessage(null)
    setErrors({})
    try {
      return await action(values)
    } catch (error) {
      const parsed = parseApiError(error)
      const fieldCount = Object.keys(parsed.fields).length
      setErrors(parsed.fields)
      setMessage({
        type: 'danger',
        text: fieldCount > 1 ? 'Please correct the highlighted fields.' : parsed.message,
      })
      return undefined
    } finally {
      setSubmitting(false)
    }
  }

  const reset = (nextValues = initialValues) => {
    setValues(nextValues)
    setErrors({})
    setMessage(null)
  }

  return { values, errors, message, submitting, setField, setValues, setMessage, handleChange, submit, reset }
}
