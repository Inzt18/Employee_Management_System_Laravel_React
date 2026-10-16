import { useEffect } from 'react'
import { createPortal } from 'react-dom'

/**
 * Bootstrap-styled modal controlled entirely by React state (no Bootstrap JS needed).
 */
export default function Modal({ show, title, onClose, children, footer, size = '' }) {
  useEffect(() => {
    if (!show) return undefined
    const closeOnEscape = (event) => event.key === 'Escape' && onClose()
    document.addEventListener('keydown', closeOnEscape)
    document.body.classList.add('modal-open')
    return () => {
      document.removeEventListener('keydown', closeOnEscape)
      document.body.classList.remove('modal-open')
    }
  }, [show, onClose])

  if (!show) return null

  return createPortal(
    <>
      <div className="modal d-block" role="dialog" aria-modal="true" onMouseDown={onClose}>
        <div className={`modal-dialog modal-dialog-centered modal-dialog-scrollable ${size}`} onMouseDown={(event) => event.stopPropagation()}>
          <div className="modal-content app-modal">
            <div className="modal-header">
              <h5 className="modal-title">{title}</h5>
              <button type="button" className="btn-close" aria-label="Close" onClick={onClose} />
            </div>
            <div className="modal-body">{children}</div>
            {footer && <div className="modal-footer">{footer}</div>}
          </div>
        </div>
      </div>
      <div className="modal-backdrop show" />
    </>,
    document.body,
  )
}
