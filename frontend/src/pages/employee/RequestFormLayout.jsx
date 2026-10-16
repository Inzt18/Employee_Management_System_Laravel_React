import { Link, useNavigate } from 'react-router-dom'
import FormHero from '../../components/FormHero'
import { FormAlert } from '../../components/form/FormFields'
import { useToast } from '../../context/ToastContext'

/**
 * Shared page frame for the employee request forms. `onSubmit` must resolve to the API response.
 */
export default function RequestFormLayout({ hero, form, onSubmit, submitLabel = 'Submit Request', children }) {
  const navigate = useNavigate()
  const { notify } = useToast()

  const handleSubmit = async (event) => {
    event.preventDefault()
    const response = await form.submit(onSubmit)
    if (response) {
      notify(response.message)
      navigate('/dashboard')
    }
  }

  return (
    <div className="form-shell">
      <Link className="back-link" to="/dashboard">
        <i className="bi bi-arrow-left" /> Back to dashboard
      </Link>
      <div className="form-card">
        <FormHero {...hero} />
        <div className="form-body">
          <FormAlert message={form.message} />
          <form onSubmit={handleSubmit} noValidate>
            {children}
            <div className="form-actions">
              <Link to="/dashboard" className="btn btn-ghost">
                Cancel
              </Link>
              <button type="submit" className="btn btn-primary" disabled={form.submitting}>
                <i className="bi bi-check-circle" /> {form.submitting ? 'Submitting...' : submitLabel}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
