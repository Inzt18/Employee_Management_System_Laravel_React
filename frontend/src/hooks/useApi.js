import { useCallback, useEffect, useRef, useState } from 'react'
import { parseApiError } from '../utils/errors'

/**
 * Runs an API call whenever its dependencies change and exposes
 * { data, loading, error, reload }. Out-of-order responses are ignored.
 */
export default function useApi(request, deps = [], initialData = null) {
  const [data, setData] = useState(initialData)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const latestCall = useRef(0)

  // eslint-disable-next-line react-hooks/exhaustive-deps
  const run = useCallback(request, deps)

  const reload = useCallback(async () => {
    const callId = ++latestCall.current
    setLoading(true)
    setError(null)
    try {
      const result = await run()
      if (callId === latestCall.current) setData(result)
    } catch (err) {
      if (callId === latestCall.current) setError(parseApiError(err).message)
    } finally {
      if (callId === latestCall.current) setLoading(false)
    }
  }, [run])

  useEffect(() => {
    reload()
  }, [reload])

  return { data, loading, error, reload, setData }
}
