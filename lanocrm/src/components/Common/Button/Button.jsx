import React from 'react';
import PropTypes from 'prop-types';
import styles from './Button.module.css';

/**
 * Button Component - KiotViet Style + LANO Brand
 * 
 * @component
 * @example
 * <Button variant="primary" size="md" onClick={handleClick}>
 *   Lưu thay đổi
 * </Button>
 */
const Button = ({
  children,
  variant = 'primary',
  size = 'md',
  type = 'button',
  disabled = false,
  loading = false,
  icon = null,
  iconPosition = 'left',
  fullWidth = false,
  onClick,
  className = '',
  ...props
}) => {
  // Build class names
  const classNames = [
    styles.button,
    styles[variant],
    styles[size],
    loading && styles.loading,
    disabled && styles.disabled,
    fullWidth && styles.fullWidth,
    icon && styles.withIcon,
    className,
  ]
    .filter(Boolean)
    .join(' ');

  // Handle click with disabled/loading check
  const handleClick = (e) => {
    if (disabled || loading) {
      e.preventDefault();
      return;
    }
    onClick?.(e);
  };

  return (
    <button
      type={type}
      className={classNames}
      onClick={handleClick}
      disabled={disabled || loading}
      {...props}
    >
      {/* Loading spinner */}
      {loading && (
        <span className={styles.spinner} aria-hidden="true">
          <svg
            className={styles.spinnerIcon}
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              className={styles.spinnerCircle}
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              strokeWidth="4"
            />
            <path
              className={styles.spinnerPath}
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
          </svg>
        </span>
      )}

      {/* Icon (left position) */}
      {icon && iconPosition === 'left' && !loading && (
        <span className={styles.iconLeft}>{icon}</span>
      )}

      {/* Button text */}
      {children && <span className={styles.text}>{children}</span>}

      {/* Icon (right position) */}
      {icon && iconPosition === 'right' && !loading && (
        <span className={styles.iconRight}>{icon}</span>
      )}
    </button>
  );
};

Button.propTypes = {
  /** Button content */
  children: PropTypes.node,
  /** Button variant */
  variant: PropTypes.oneOf(['primary', 'secondary', 'ghost', 'danger']),
  /** Button size */
  size: PropTypes.oneOf(['sm', 'md', 'lg']),
  /** Button type attribute */
  type: PropTypes.oneOf(['button', 'submit', 'reset']),
  /** Disabled state */
  disabled: PropTypes.bool,
  /** Loading state */
  loading: PropTypes.bool,
  /** Icon element */
  icon: PropTypes.node,
  /** Icon position */
  iconPosition: PropTypes.oneOf(['left', 'right']),
  /** Full width button */
  fullWidth: PropTypes.bool,
  /** Click handler */
  onClick: PropTypes.func,
  /** Additional CSS class */
  className: PropTypes.string,
};

export default Button;