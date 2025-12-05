/**
 * Centralized API error/success helpers
 * @agent-pattern: DRY error handling with priority fallback
 * @agent-reusable: HIGH
 */
import { message as antdMessage } from 'antd';

/** Extract meaningful message from Axios-like error object. */
export const extractApiMessage = (error, defaultMessage = 'Có lỗi xảy ra, vui lòng thử lại') => {
  if (!error) return defaultMessage;

  // Priority 1: Backend validation / explicit message
  const fromResponseMessage = error.response?.data?.message;
  if (fromResponseMessage) return fromResponseMessage;

  const fromResponseMessagesError = error.response?.data?.messages?.error;
  if (fromResponseMessagesError) return fromResponseMessagesError;

  // Priority 2: Backend error field
  const fromResponseError = error.response?.data?.error;
  if (fromResponseError) return fromResponseError;

  // Priority 3: Axios error.message
  if (error.message) return error.message;

  // Fallback
  return defaultMessage;
};

/**
 * Handle API errors with standardized messaging.
 * @param {Error} error Axios error object
 * @param {Object} options
 * @param {boolean} [options.showMessage=true] Show toast message
 * @param {number} [options.duration=4] seconds
 * @param {string} [options.defaultMessage] fallback text
 * @param {Object} [options.messageApi] optional AntD message instance (from App.useApp())
 * @returns {string} message that was resolved
 */
export const handleApiError = (error, options = {}) => {
  const {
    showMessage = true,
    duration = 4,
    defaultMessage = 'Có lỗi xảy ra, vui lòng thử lại',
    messageApi = null,
  } = options;

  const msg = extractApiMessage(error, defaultMessage);

  if (showMessage) {
    const notifier = messageApi?.error ? messageApi : antdMessage;
    notifier.error(msg, duration);
  }

  if (import.meta.env.DEV) {
    // Keep a lightweight log for debugging without spamming production
    // eslint-disable-next-line no-console
    console.error('[API Error]', {
      message: msg,
      status: error?.response?.status,
      data: error?.response?.data,
      url: error?.config?.url,
    });
  }

  return msg;
};

/**
 * Handle success toast.
 * @param {string} msg message to show
 */
export const handleApiSuccess = (msg, options = {}) => {
  const { showMessage = true, duration = 3, messageApi = null } = options;
  if (showMessage) {
    const notifier = messageApi?.success ? messageApi : antdMessage;
    notifier.success(msg, duration);
  }
  return msg;
};

/**
 * Simple required-field validator for plain objects.
 * @param {Object} formData
 * @param {string[]} requiredFields
 * @returns {boolean}
 */
export const validateRequired = (formData, requiredFields = [], options = {}) => {
  const { fieldLabels = {}, showMessage = true, messageApi = null } = options;
  const notifier = messageApi?.error ? messageApi : antdMessage;

  for (const field of requiredFields) {
    const value = formData?.[field];
    const isEmpty = value === undefined || value === null || value.toString().trim() === '';
    if (isEmpty) {
      const label = fieldLabels[field] || field;
      if (showMessage) {
        notifier.error(`Vui lòng nhập ${label}`);
      }
      return false;
    }
  }
  return true;
};

export default {
  handleApiError,
  handleApiSuccess,
  validateRequired,
  extractApiMessage,
};
