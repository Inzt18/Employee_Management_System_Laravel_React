import { useEffect } from 'react'
import { adminApi } from '../../../api/services'
import Modal from '../../../components/Modal'
import { FormAlert, SelectField, TextAreaField, TextField } from '../../../components/form/FormFields'
import { useOptions } from '../../../context/OptionsContext'
import useForm from '../../../hooks/useForm'

const EMPTY_EMPLOYEE = {
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
  role: 'Employee',
  employment_status: 'Active',
}

/**
 * Add (employee = {}) or edit (employee = existing record) form. Hidden when employee is null.
 */
export default function EmployeeFormModal({ employee, onClose, onSaved }) {
  const { departments, genders, roles, employment_statuses: statuses } = useOptions()
  const form = useForm(EMPTY_EMPLOYEE)
  const { values, errors, message, submitting, handleChange, submit, reset } = form
  const isEdit = Boolean(employee?.id)

  useEffect(() => {
    if (!employee) return
    const initial = { ...EMPTY_EMPLOYEE }
    Object.keys(initial).forEach((key) => {
      if (key !== 'password' && employee[key] != null) initial[key] = employee[key]
    })
    reset(initial)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [employee])

  const handleSubmit = async (event) => {
    event.preventDefault()
    const response = await submit((data) =>
      isEdit ? adminApi.updateEmployee(employee.id, data) : adminApi.createEmployee(data),
    )
    if (response) onSaved(response.message)
  }

  const field = (name) => ({ name, value: values[name], onChange: handleChange, error: errors[name] })
  const departmentOptions = departments.includes(values.department) || !values.department ? departments : [values.department, ...departments]

  return (
    <Modal
      show={Boolean(employee)}
      title={isEdit ? 'Edit Employee' : 'Add Employee'}
      onClose={onClose}
      size="modal-lg"
      footer={
        <>
          <button type="button" className="btn btn-ghost" onClick={onClose}>
            Cancel
          </button>
          <button type="submit" form="employee-form" className="btn btn-primary" disabled={submitting}>
            {submitting ? 'Saving...' : 'Save'}
          </button>
        </>
      }
    >
      <FormAlert message={message} />
      <form id="employee-form" onSubmit={handleSubmit} noValidate>
        <div className="row">
          <TextField label="First Name" className="col-md-4 mb-3" required {...field('first_name')} />
          <TextField label="Middle Name" className="col-md-4 mb-3" {...field('middle_name')} />
          <TextField label="Last Name" className="col-md-4 mb-3" required {...field('last_name')} />
          <TextField label="Birth Date" type="date" className="col-md-6 mb-3" required {...field('birth_date')} />
          <SelectField label="Gender" className="col-md-6 mb-3" options={genders} placeholder="Select gender" required {...field('gender')} />
          <TextField label="Email" type="email" className="col-md-6 mb-3" required {...field('email')} />
          <SelectField label="Department" className="col-md-6 mb-3" options={departmentOptions} placeholder="Select department" required {...field('department')} />
          <TextAreaField label="Address" className="col-12 mb-3" rows={2} required {...field('address')} />
          <TextField label="Username" className="col-md-6 mb-3" autoComplete="off" required {...field('username')} />
          <TextField
            label="Password"
            type="password"
            className="col-md-6 mb-3"
            autoComplete="new-password"
            hint={isEdit ? 'Leave blank to keep the current password.' : 'At least 8 characters.'}
            required={!isEdit}
            {...field('password')}
          />
          <SelectField label="Role" className="col-md-6 mb-3" options={roles} required {...field('role')} />
          <SelectField label="Status" className="col-md-6 mb-3" options={statuses} required {...field('employment_status')} />
        </div>
      </form>
    </Modal>
  )
}
