import { employeeApi } from '../../api/services'
import { TextAreaField, TextField } from '../../components/form/FormFields'
import { useOptions } from '../../context/OptionsContext'
import useForm from '../../hooks/useForm'
import { todayIso } from '../../utils/format'
import DocumentUploads from './DocumentUploads'
import RequestFormLayout from './RequestFormLayout'

export default function ResignationRequestPage() {
  const { resignation_documents: documents } = useOptions()
  const form = useForm({ resignation_date: '', reason: '' })
  const { values, errors, handleChange } = form

  return (
    <RequestFormLayout
      form={form}
      onSubmit={employeeApi.submitResignation}
      hero={{
        icon: 'bi-door-open',
        tone: 'resign',
        title: 'Resignation Request',
        description: 'Submit your intended last day and reason. HR will review it and get back to you.',
      }}
    >
      <TextField
        label="Effective Date"
        name="resignation_date"
        type="date"
        min={todayIso()}
        value={values.resignation_date}
        onChange={handleChange}
        error={errors.resignation_date}
        required
      />
      <TextAreaField
        label="Reason"
        name="reason"
        rows={5}
        placeholder="Share the reason for your resignation"
        value={values.reason}
        onChange={handleChange}
        error={errors.reason}
        required
      />
      <DocumentUploads
        title="Resignation documents"
        intro="A signed resignation letter is required."
        documents={documents}
        form={form}
      />
    </RequestFormLayout>
  )
}
