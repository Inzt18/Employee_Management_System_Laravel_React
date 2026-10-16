import { employeeApi } from '../../api/services'
import { TextField } from '../../components/form/FormFields'
import useForm from '../../hooks/useForm'
import RequestFormLayout from './RequestFormLayout'

export default function SubjectRequestPage() {
  const form = useForm({ subject_code: '', subject_name: '', units: '' })
  const { values, errors, handleChange } = form

  return (
    <RequestFormLayout
      form={form}
      onSubmit={employeeApi.submitSubject}
      hero={{
        icon: 'bi-journal-plus',
        tone: 'subject',
        title: 'Request a Subject',
        description: 'Ask the admin to add a subject to your load. It appears on your dashboard once approved.',
      }}
    >
      <div className="row">
        <TextField
          label="Subject Code"
          name="subject_code"
          className="col-md-4 mb-3"
          placeholder="e.g. IT3101"
          value={values.subject_code}
          onChange={handleChange}
          error={errors.subject_code}
          required
        />
        <TextField
          label="Subject Name"
          name="subject_name"
          className="col-md-8 mb-3"
          placeholder="e.g. Database Management Systems"
          value={values.subject_name}
          onChange={handleChange}
          error={errors.subject_name}
          required
        />
      </div>
      <TextField
        label="Units"
        name="units"
        type="number"
        min={1}
        max={10}
        className="mb-4"
        value={values.units}
        onChange={handleChange}
        error={errors.units}
        required
      />
    </RequestFormLayout>
  )
}
