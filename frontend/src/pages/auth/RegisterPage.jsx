import { Link, useNavigate } from 'react-router-dom'
import FormHero from '../../components/FormHero'
import { FormAlert, SelectField, TextAreaField, TextField } from '../../components/form/FormFields'
import { useAuth } from '../../context/AuthContext'
import { useOptions } from '../../context/OptionsContext'
import { useToast } from '../../context/ToastContext'
import useForm from '../../hooks/useForm'
import { todayIso } from '../../utils/format'

const INITIAL_VALUES = {
  first_name: '',
  middle_name: '',
  last_name: '',
  birth_date: '',
  gender: '',
  email: '',
  department: '',
  address: '',
  username: '',
  password: '',
  password_confirmation: '',
}

export default function RegisterPage() {
  const { register } = useAuth()
  const { genders } = useOptions()
  const { notify } = useToast()
  const navigate = useNavigate()
  const { values, errors, message, submitting, handleChange, submit } = useForm(INITIAL_VALUES)

  const handleSubmit = async (event) => {
    event.preventDefault()
    const user = await submit(register)
    if (user) {
      notify(`Welcome, ${user.first_name}! Your account is ready.`)
      navigate('/dashboard', { replace: true })
    }
  }

  const field = (name) => ({ name, value: values[name], onChange: handleChange, error: errors[name] })

  return (
    <div className="container py-5">
      <div className="row justify-content-center">
        <div className="col-lg-8">
          <div className="form-card">
            <FormHero
              icon="bi-person-plus"
              tone="leave"
              title="Create an account"
              description="Register as an employee. Admin accounts can only be created by an administrator."
            />
            <div className="form-body">
              <FormAlert message={message} />
              <form onSubmit={handleSubmit} noValidate>
                <div className="row">
                  <TextField label="First Name" className="col-md-4 mb-3" required {...field('first_name')} />
                  <TextField label="Middle Name" className="col-md-4 mb-3" {...field('middle_name')} />
                  <TextField label="Last Name" className="col-md-4 mb-3" required {...field('last_name')} />
                  <TextField label="Date of Birth" type="date" className="col-md-6 mb-3" max={todayIso()} required {...field('birth_date')} />
                  <SelectField label="Gender" className="col-md-6 mb-3" options={genders} placeholder="Select gender" required {...field('gender')} />
                  <TextField label="Email Address" type="email" className="col-md-6 mb-3" required {...field('email')} />
                  <TextField label="Department" className="col-md-6 mb-3" placeholder="e.g. Information Technology" maxLength={100} required {...field('department')} />
                  <TextAreaField label="Address" className="col-12 mb-3" required {...field('address')} />
                  <TextField label="Username" className="col-md-12 mb-3" autoComplete="username" required {...field('username')} />
                  <TextField
                    label="Password"
                    type="password"
                    className="col-md-6 mb-4"
                    hint="At least 8 characters."
                    autoComplete="new-password"
                    required
                    {...field('password')}
                  />
                  <TextField
                    label="Confirm Password"
                    type="password"
                    className="col-md-6 mb-4"
                    autoComplete="new-password"
                    required
                    {...field('password_confirmation')}
                  />
                </div>
                <div className="form-actions">
                  <Link to="/login" className="btn btn-ghost">
                    Back to Login
                  </Link>
                  <button type="submit" className="btn btn-primary" disabled={submitting}>
                    {submitting ? 'Creating account...' : 'Create account'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
