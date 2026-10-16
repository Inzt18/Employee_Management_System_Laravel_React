import { useEffect, useState } from 'react'
import { attachmentApi } from '../api/services'
import { useToast } from '../context/ToastContext'
import { parseApiError } from '../utils/errors'
import { formatFileSize } from '../utils/format'
import Spinner from './Spinner'

const isPdf = (attachment) => attachment.mime_type === 'application/pdf'

/**
 * Shows the proof documents of a request inline, with open and download actions.
 */
export default function AttachmentPreview({ attachments }) {
  const { notify } = useToast()
  const [selectedId, setSelectedId] = useState(attachments?.[0]?.id ?? null)
  const [preview, setPreview] = useState({ id: null, url: null, error: null })

  const selected = attachments?.find((attachment) => attachment.id === selectedId)

  useEffect(() => {
    if (!selected) return undefined
    let active = true
    let objectUrl = null

    attachmentApi
      .blob(selected)
      .then((blob) => {
        objectUrl = URL.createObjectURL(blob)
        if (active) setPreview({ id: selected.id, url: objectUrl, error: null })
      })
      .catch((error) => active && setPreview({ id: selected.id, url: null, error: parseApiError(error).message }))

    return () => {
      active = false
      if (objectUrl) URL.revokeObjectURL(objectUrl)
    }
  }, [selected])

  if (!attachments?.length) {
    return <span className="text-muted">No documents submitted.</span>
  }

  const run = (action) => () => action(selected).catch((error) => notify(parseApiError(error).message, 'danger'))
  const loading = preview.id !== selected.id

  return (
    <div className="attachment-preview">
      <div className="attachment-tabs">
        {attachments.map((attachment) => (
          <button
            key={attachment.id}
            type="button"
            className={`attachment-tab ${attachment.id === selectedId ? 'active' : ''}`}
            onClick={() => setSelectedId(attachment.id)}
          >
            <i className={`bi ${isPdf(attachment) ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image'}`} />
            <span>{attachment.document_label}</span>
          </button>
        ))}
      </div>

      <div className="attachment-viewer">
        {loading && <Spinner label="Loading document..." />}
        {!loading && preview.error && <div className="text-danger small p-3">{preview.error}</div>}
        {!loading && preview.url && isPdf(selected) && (
          <iframe src={preview.url} title={selected.document_label} className="attachment-frame" />
        )}
        {!loading && preview.url && !isPdf(selected) && (
          <img src={preview.url} alt={selected.document_label} className="attachment-image" />
        )}
      </div>

      <div className="attachment-meta">
        <span className="text-muted small text-truncate">
          {selected.original_name} · {formatFileSize(selected.file_size)}
        </span>
        <div className="d-flex gap-2">
          <button type="button" className="btn btn-sm btn-ghost" onClick={run(attachmentApi.open)}>
            <i className="bi bi-box-arrow-up-right" /> Open
          </button>
          <button type="button" className="btn btn-sm btn-outline-primary" onClick={run(attachmentApi.download)}>
            <i className="bi bi-download" /> Download
          </button>
        </div>
      </div>
    </div>
  )
}
