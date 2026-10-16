import { employeeApi } from '../../api/services'
import { SelectField, TextAreaField, TextField } from '../../components/form/FormFields'
import { useOptions } from '../../context/OptionsContext'
import useForm from '../../hooks/useForm'
import { todayIso } from '../../utils/format'
import DocumentUploads from './DocumentUploads'
import RequestFormLayout from './RequestFormLayout'

export default function LeaveRequestPage() {
  const { leave_types: leaveTypes } = useOptions()
  const form = useForm({ leave_type: '', start_date: '', end_date: '', reason: '' })
  const { values, errors, handleChange, setValues } = form

  const selectedType = leaveTypes.find((type) => type.value === values.leave_type)

  /**
   * Switching leave type drops files picked for the previous type.
   */
  const changeLeaveType = (event) => {
    const { start_date, end_date, reason } = values
    setValues({ leave_type: event.target.value, start_date, end_date, reason })
  }

  return (
    <RequestFormLayout
      form={form}
      onSubmit={employeeApi.submitLeave}
      hero={{
        icon: 'bi-airplane',
        tone: 'leave',
        title: 'Leave Request',
        description: 'Choose the leave type first. The documents we ask for will change based on that situation.',
      }}
    >
      <SelectField
        label="Leave Type"
        name="leave_type"
        value={values.leave_type}
        onChange={changeLeaveType}
        error={errors.leave_type}
        options={leaveTypes.map(({ value, label }) => ({ value, label }))}
        placeholder="Select leave type"
        required
      />
      <div className="row">
        <TextField
          label="Start Date"
          name="start_date"
          type="date"
          className="col-md-6 mb-3"
          min={todayIso()}
          value={values.start_date}
          onChange={handleChange}
          error={errors.start_date}
          required
        />
        <TextField
          label="End Date"
          name="end_date"
          type="date"
          className="col-md-6 mb-3"
          min={values.start_date || todayIso()}
          value={values.end_date}
          onChange={handleChange}
          error={errors.end_date}
          required
        />
      </div>
      <TextAreaField
        label="Reason"
        name="reason"
        rows={4}
        placeholder="Briefly explain why you need this leave"
        value={values.reason}
        onChange={handleChange}
        error={errors.reason}
        required
      />
      {selectedType && (
        <DocumentUploads
          key={selectedType.value}
          title={`Documents for ${selectedType.label}`}
          intro="Upload the files that match the selected leave type."
          documents={selectedType.documents}
          form={form}
        />
      )}
    </RequestFormLayout>
  )
}
