export default function FormHero({ icon, tone, title, description }) {
  return (
    <div className={`form-hero form-hero-${tone}`}>
      <div className="form-icon">
        <i className={`bi ${icon}`} />
      </div>
      <div>
        <h1>{title}</h1>
        <p>{description}</p>
      </div>
    </div>
  )
}
