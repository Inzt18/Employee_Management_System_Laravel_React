/**
 * Labelled Bootstrap form controls that show the Laravel validation message for their field.
 */

function FieldShell({ label, name, error, hint, required, className = 'mb-3', children }) {
  return (
    <div className={className}>
      {label && (
        <label className="form-label" htmlFor={name}>
          {label} {required && <span className="text-danger">*</span>}
        </label>
      )}
      {children}
      {error ? <div className="invalid-feedback d-block">{error}</div> : hint && <div className="form-text">{hint}</div>}
    </div>
  )
}

export function TextField({ label, name, error, hint, className, type = 'text', ...inputProps }) {
  return (
    <FieldShell label={label} name={name} error={error} hint={hint} required={inputProps.required} className={className}>
      <input id={name} name={name} type={type} className={`form-control ${error ? 'is-invalid' : ''}`} {...inputProps} />
    </FieldShell>
  )
}

export function TextAreaField({ label, name, error, hint, className, rows = 3, ...inputProps }) {
  return (
    <FieldShell label={label} name={name} error={error} hint={hint} required={inputProps.required} className={className}>
      <textarea id={name} name={name} rows={rows} className={`form-control ${error ? 'is-invalid' : ''}`} {...inputProps} />
    </FieldShell>
  )
}

/**
 * options: array of strings or { value, label } objects.
 */
export function SelectField({ label, name, error, hint, className, options, placeholder, ...inputProps }) {
  return (
    <FieldShell label={label} name={name} error={error} hint={hint} required={inputProps.required} className={className}>
      <select id={name} name={name} className={`form-select ${error ? 'is-invalid' : ''}`} {...inputProps}>
        {placeholder !== undefined && <option value="">{placeholder}</option>}
        {options.map((option) => {
          const { value, label: optionLabel } = typeof option === 'string' ? { value: option, label: option } : option
          return (
            <option key={value} value={value}>
              {optionLabel}
            </option>
          )
        })}
      </select>
    </FieldShell>
  )
}

export function FileField({ label, name, error, hint, required, accept, onChange, className }) {
  return (
    <FieldShell label={label} name={name} error={error} hint={hint} required={required} className={className}>
      <input
        id={name}
        name={name}
        type="file"
        accept={accept}
        required={required}
        onChange={onChange}
        className={`form-control ${error ? 'is-invalid' : ''}`}
      />
    </FieldShell>
  )
}

export function FormAlert({ message }) {
  if (!message) return null
  return <div className={`alert alert-${message.type}`}>{message.text}</div>
}
