/**
 * Turns an axios error into a message and a field => message map
 * (Laravel returns validation errors as { message, errors: { field: [..] } }).
 */
export function parseApiError(error, fallback = 'Something went wrong. Please try again.') {
  const response = error?.response

  if (!response) {
    return { message: 'Unable to reach the server. Is the Laravel API running?', fields: {} }
  }

  const fields = Object.fromEntries(
    Object.entries(response.data?.errors ?? {}).map(([field, messages]) => [field, messages[0]]),
  )

  return { message: response.data?.message || fallback, fields }
}
