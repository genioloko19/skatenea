import { forwardRef, type InputHTMLAttributes, type ReactNode, type SelectHTMLAttributes, type TextareaHTMLAttributes } from 'react';
import { cn } from '../../lib/cn';

interface FieldWrapperProps {
  label?: string;
  hint?: string;
  error?: string;
  required?: boolean;
  children: ReactNode;
  htmlFor?: string;
}

export function Field({ label, hint, error, required, children, htmlFor }: FieldWrapperProps) {
  return (
    <label className={cn('field', error && 'field--error')} htmlFor={htmlFor}>
      {label && (
        <span className="field__label">
          {label}
          {required && <span aria-hidden="true"> *</span>}
        </span>
      )}
      {children}
      {hint && !error && <span className="field__hint">{hint}</span>}
      {error && <span className="field__error">{error}</span>}
    </label>
  );
}

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  hint?: string;
  error?: string;
  leading?: ReactNode;
  trailing?: ReactNode;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
  { label, hint, error, leading, trailing, className, required, id, ...props },
  ref,
) {
  return (
    <Field label={label} hint={hint} error={error} required={required} htmlFor={id}>
      <span className={cn('input-shell', Boolean(leading) && 'input-shell--leading', Boolean(trailing) && 'input-shell--trailing')}>
        {leading && <span className="input-shell__icon" aria-hidden="true">{leading}</span>}
        <input ref={ref} id={id} className={cn('input', className)} required={required} {...props} />
        {trailing && <span className="input-shell__icon input-shell__icon--trailing">{trailing}</span>}
      </span>
    </Field>
  );
});

interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  label?: string;
  hint?: string;
  error?: string;
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(function Textarea(
  { label, hint, error, className, required, id, ...props },
  ref,
) {
  return (
    <Field label={label} hint={hint} error={error} required={required} htmlFor={id}>
      <textarea ref={ref} id={id} className={cn('input input--textarea', className)} required={required} {...props} />
    </Field>
  );
});

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  label?: string;
  hint?: string;
  error?: string;
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(function Select(
  { label, hint, error, className, required, id, children, ...props },
  ref,
) {
  return (
    <Field label={label} hint={hint} error={error} required={required} htmlFor={id}>
      <select ref={ref} id={id} className={cn('input input--select', className)} required={required} {...props}>
        {children}
      </select>
    </Field>
  );
});
