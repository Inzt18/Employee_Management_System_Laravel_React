import { attachmentApi } from '../api/services'
import { useToast } from '../context/ToastContext'
import { parseApiError } from '../utils/errors'
import { formatFileSize } from '../utils/format'

export default function AttachmentLinks({ attachments, detailed = false }) {
  const { notify } = useToast()

  if (!attachments?.length) {
    return <span className="text-muted">{detailed ? 'No attachments submitted.' : 'None'}</span>
  }

  const open = (attachment) => (event) => {
    event.stopPropagation()
    attachmentApi.open(attachment).catch((error) => notify(parseApiError(error).message, 'danger'))
  }

  return (
    <div className="attachment-list">
      {attachments.map((attachment) => (
        <button key={attachment.id} type="button" className="btn btn-link attachment-link" onClick={open(attachment)}>
          <i className={`bi ${attachment.mime_type === 'application/pdf' ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image'}`} />{' '}
          {attachment.document_label}
          {detailed && (
            <span className="text-muted">
              {' '}
              — {attachment.original_name} ({formatFileSize(attachment.file_size)})
            </span>
          )}
        </button>
      ))}
    </div>
  )
}
