export default function PanelCard({ title, subtitle, actions, children }) {
  return (
    <section className="panel-card">
      <div className="panel-head">
        <div>
          <h5>{title}</h5>
          {subtitle && <div className="panel-subtitle">{subtitle}</div>}
        </div>
        {actions && <div className="d-flex gap-2 flex-wrap align-items-center">{actions}</div>}
      </div>
      {children}
    </section>
  )
}
