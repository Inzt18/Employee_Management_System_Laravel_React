import { useState } from 'react'
import { adminApi } from '../../../api/services'
import AttachmentPreview from '../../../components/AttachmentPreview'
import Modal from '../../../components/Modal'
import StatusBadge from '../../../components/StatusBadge'
import { useToast } from '../../../context/ToastContext'
import { parseApiError } from '../../../utils/errors'
import { formatDate } from '../../../utils/format'

export default function RequestReviewModal({ request, onClose, onReviewed }) {
  const { notify } = useToast()
  const [comment, setComment] = useState('')
  const [error, setError] = useState(null)
  const [busy, setBusy] = useState(null)

  if (!request) return null

  const isPending = request.status === 'Pending'

  const review = async (status) => {
    setBusy(status)
    setError(null)
    try {
      const response = await adminApi.reviewRequest(request, { status, admin_comment: comment || null })
      notify(response.message)
      onReviewed()
    } catch (err) {
      const parsed = parseApiError(err)
      setError(parsed.fields.admin_comment ?? parsed.message)
    } finally {
      setBusy(null)
    }
  }

  const reason = request.fields?.reason
  const hasDocuments = ['leave', 'resignation'].includes(request.request_type)

  return (
    <Modal
      show
      size={hasDocuments ? 'modal-lg' : ''}
      title={`${request.request_category} request`}
      onClose={onClose}
      footer={
        isPending ? (
          <>
            <button type="button" className="btn btn-ghost me-auto" onClick={onClose} disabled={busy}>
              Close
            </button>
            <button type="button" className="btn btn-outline-danger" onClick={() => review('Rejected')} disabled={busy}>
              <i className="bi bi-x-circle" /> {busy === 'Rejected' ? 'Rejecting...' : 'Reject'}
            </button>
            <button type="button" className="btn btn-success" onClick={() => review('Approved')} disabled={busy}>
              <i className="bi bi-check-circle" /> {busy === 'Approved' ? 'Approving...' : 'Approve'}
            </button>
          </>
        ) : (
          <button type="button" className="btn btn-ghost" onClick={onClose}>
            Close
          </button>
        )
      }
    >
      <dl className="detail-list">
        <dt>Employee</dt>
        <dd>
          {request.employee?.name}
          <span className="text-muted"> · {request.employee?.department}</span>
        </dd>

        <dt>Status</dt>
        <dd>
          <StatusBadge status={request.status} />
        </dd>

        <dt>Submitted</dt>
        <dd>{formatDate(request.created_at)}</dd>

        <dt>Details</dt>
        <dd>
          <strong>{request.summary}</strong>
          {request.details !== request.summary && <div className="pre-wrap">{request.details}</div>}
        </dd>

        {reason && (
          <>
            <dt>Reason</dt>
            <dd className="pre-wrap">{reason}</dd>
          </>
        )}

        {!isPending && (
          <>
            <dt>Reviewed</dt>
            <dd>
              {formatDate(request.reviewed_at)} {request.reviewer && <span className="text-muted">by {request.reviewer}</span>}
            </dd>
            <dt>Admin comment</dt>
            <dd className="pre-wrap">{request.admin_comment || '—'}</dd>
          </>
        )}
      </dl>

      {hasDocuments && (
        <div className="mt-3">
          <h6 className="section-label">
            <i className="bi bi-paperclip" /> Proof documents ({request.attachments?.length ?? 0})
          </h6>
          <AttachmentPreview attachments={request.attachments} />
        </div>
      )}

      {isPending && (
        <div className="mt-3">
          <label className="form-label" htmlFor="admin_comment">
            Comment for the employee <span className="text-muted fw-normal">(required when rejecting)</span>
          </label>
          <textarea
            id="admin_comment"
            className={`form-control ${error ? 'is-invalid' : ''}`}
            rows={3}
            value={comment}
            onChange={(event) => setComment(event.target.value)}
            placeholder="e.g. Approved, enjoy your leave."
          />
          {error && <div className="invalid-feedback d-block">{error}</div>}
        </div>
      )}
    </Modal>
  )
}
