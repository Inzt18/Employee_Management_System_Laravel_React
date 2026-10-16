import { useEffect, useRef } from 'react'
import { useNavigate } from 'react-router-dom'
import Spinner from '../../components/Spinner'
import { homePathFor, useAuth } from '../../context/AuthContext'

/**
 * Landing page after Google or Facebook sign-in. The API puts either #token=... or #error=... in the URL fragment.
 */
export default function SocialCallbackPage() {
  const { acceptToken } = useAuth()
  const navigate = useNavigate()
  const handled = useRef(false)

  useEffect(() => {
    if (handled.current) return
    handled.current = true

    const params = new URLSearchParams(window.location.hash.slice(1))
    window.history.replaceState(null, '', window.location.pathname)

    const token = params.get('token')
    if (!token) {
      navigate('/login', { replace: true, state: { error: params.get('error') || 'Sign-in failed. Please try again.' } })
      return
    }

    acceptToken(token)
      .then((user) => navigate(homePathFor(user), { replace: true }))
      .catch(() => navigate('/login', { replace: true, state: { error: 'Sign-in failed. Please try again.' } }))
  }, [acceptToken, navigate])

  return <Spinner fullPage label="Signing you in..." />
}
