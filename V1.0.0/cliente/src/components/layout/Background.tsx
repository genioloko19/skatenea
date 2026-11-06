export function Background() {
  return (
    <div className="dynamic-background" aria-hidden="true">
      <div className="dynamic-background__base" />
      <div className="dynamic-background__orb dynamic-background__orb--one" />
      <div className="dynamic-background__orb dynamic-background__orb--two" />
      <div className="dynamic-background__orb dynamic-background__orb--three" />
      <div className="dynamic-background__grain" />
    </div>
  );
}
