import { FileField } from '../../components/form/FormFields'
import { useOptions } from '../../context/OptionsContext'

/**
 * File inputs for the documents a request type requires (rules come from the API).
 */
export default function DocumentUploads({ title, intro, documents, form }) {
  const { upload } = useOptions()

  if (!documents.length) return null

  const accept = upload.extensions.map((extension) => `.${extension}`).join(',')
  const maxMb = upload.max_kilobytes / 1024

  return (
    <div className="attachment-box mb-4">
      <h6 className="mb-1">{title}</h6>
      <p className="text-muted small mb-3">
        {intro} Accepted: {upload.extensions.join(', ').toUpperCase()} up to {maxMb} MB each.
      </p>
      {documents.map((document) => (
        <FileField
          key={document.field}
          label={document.label}
          name={document.field}
          required={document.required}
          hint={document.hint}
          accept={accept}
          onChange={form.handleChange}
          error={form.errors[document.field]}
        />
      ))}
    </div>
  )
}
