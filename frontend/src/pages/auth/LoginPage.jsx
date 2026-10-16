import { useEffect, useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { API_URL } from '../../api/client'
import { authApi } from '../../api/services'
import { FormAlert, TextField } from '../../components/form/FormFields'
import { homePathFor, useAuth } from '../../context/AuthContext'
import useForm from '../../hooks/useForm'

export default function LoginPage() {
  const { login, notice } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [googleEnabled, setGoogleEnabled] = useState(false)
  const { values, errors, message, submitting, handleChange, submit, setMessage } = useForm({ username: '', password: '' })

  useEffect(() => {
    authApi
      .providers()
      .then((providers) => setGoogleEnabled(providers.google))
      .catch(() => setGoogleEnabled(false))
  }, [])

  useEffect(() => {
    const error = location.state?.error ?? notice
    if (error) setMessage({ type: 'warning', text: error })
  }, [location.state, notice, setMessage])

  const handleSubmit = async (event) => {
    event.preventDefault()
    const user = await submit(login)
    if (user) {
      const requested = location.state?.from?.pathname
      navigate(requested && requested !== '/' ? requested : homePathFor(user), { replace: true })
    }
  }

  return (
    <div className="auth-wrap">
      <div className="auth-split">
        <div className="auth-aside">
          <div className="brand-mark brand-mark-lg mb-3">EM</div>
          <h2>Welcome back</h2>
          <p>Sign in to manage your schedule, tasks, leave, and requests in one place.</p>
          <ul className="auth-points">
            <li><i className="bi bi-calendar-check" /> View your subjects and class schedule</li>
            <li><i className="bi bi-file-earmark-arrow-up" /> File leave with supporting documents</li>
            <li><i className="bi bi-bell" /> Track approvals from the admin</li>
          </ul>
        </div>

        <div className="auth-main">
          <div className="auth-brand mb-1">Employee Management</div>
          <p className="text-muted mb-4">Sign in to continue</p>

          <FormAlert message={message} />

          <form onSubmit={handleSubmit} noValidate>
            <TextField
              label="Username"
              name="username"
              value={values.username}
              onChange={handleChange}
              error={errors.username}
              placeholder="Enter username"
              autoComplete="username"
              autoFocus
              required
            />
            <TextField
              label="Password"
              name="password"
              type="password"
              value={values.password}
              onChange={handleChange}
              error={errors.password}
              placeholder="Enter password"
              autoComplete="current-password"
              className="mb-4"
              required
            />
            <button type="submit" className="btn btn-primary w-100" disabled={submitting}>
              {submitting ? 'Signing in...' : 'Login'}
            </button>
          </form>

          {googleEnabled && (
            <>
              <div className="auth-divider">or</div>
              <a href={`${API_URL}/auth/google/redirect`} className="btn btn-google w-100">
                <i className="bi bi-google" /> Sign in with Google
              </a>
            </>
          )}

          <div className="text-center mt-4">
            <span>Do not have an account yet? </span>
            <Link to="/register">Register</Link>
          </div>
        </div>
      </div>
    </div>
  )
}
