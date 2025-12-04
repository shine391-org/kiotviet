/**
 * Formatting Utilities
 * @file src/utils/formatters.js
 * @description Currency, date, number formatting functions
 */

import { format, parseISO, isValid } from 'date-fns';
import { vi } from 'date-fns/locale';
import numeral from 'numeral';

// Configure numeral locale for Vietnamese
numeral.register('locale', 'vi', {
  delimiters: {
    thousands: ',',
    decimal: '.',
  },
  abbreviations: {
    thousand: 'k',
    million: 'm',
    billion: 'b',
    trillion: 't',
  },
  currency: {
    symbol: '₫',
  },
});
numeral.locale('vi');

/**
 * Format currency VND (no symbol, just number with commas)
 * @param {number} value - Number to format
 * @returns {string} - Formatted string (e.g., "1,500,000")
 */
export const formatCurrency = (value) => {
  if (value === null || value === undefined || value === '') return '0';
  if (isNaN(value)) return '0';
  return numeral(value).format('0,0');
};

/**
 * Format currency with VND symbol
 * @param {number} value - Number to format
 * @returns {string} - Formatted string with symbol (e.g., "1,500,000₫")
 */
export const formatCurrencySymbol = (value) => {
  if (value === null || value === undefined || value === '') return '0₫';
  if (isNaN(value)) return '0₫';
  return numeral(value).format('0,0') + '₫';
};

/**
 * Format date to Vietnamese format
 * @param {string|Date} dateString - Date string or Date object
 * @param {string} formatStr - Date format (default: 'dd/MM/yyyy HH:mm')
 * @returns {string} - Formatted date string
 */
export const formatDate = (dateString, formatStr = 'dd/MM/yyyy HH:mm') => {
  if (!dateString) return '';
  
  try {
    let date;
    if (typeof dateString === 'string') {
      date = parseISO(dateString);
    } else {
      date = dateString;
    }
    
    if (!isValid(date)) return '';
    
    return format(date, formatStr, { locale: vi });
  } catch (error) {
    console.error('Date format error:', error);
    return dateString;
  }
};

/**
 * Format date only (no time)
 * @param {string|Date} dateString
 * @returns {string} - Format: "27/10/2025"
 */
export const formatDateOnly = (dateString) => {
  return formatDate(dateString, 'dd/MM/yyyy');
};

/**
 * Format time only (no date)
 * @param {string|Date} dateString
 * @returns {string} - Format: "23:32"
 */
export const formatTimeOnly = (dateString) => {
  return formatDate(dateString, 'HH:mm');
};

/**
 * Format number with thousand separator
 * @param {number} value - Number to format
 * @returns {string} - Formatted string (e.g., "1,234")
 */
export const formatNumber = (value) => {
  if (value === null || value === undefined || value === '') return '0';
  if (isNaN(value)) return '0';
  return numeral(value).format('0,0');
};

/**
 * Format decimal number
 * @param {number} value - Number to format
 * @param {number} decimals - Number of decimal places (default: 2)
 * @returns {string} - Formatted string (e.g., "1,234.56")
 */
export const formatDecimal = (value, decimals = 2) => {
  if (value === null || value === undefined || value === '') return '0';
  if (isNaN(value)) return '0';
  const formatString = `0,0.${'0'.repeat(decimals)}`;
  return numeral(value).format(formatString);
};

/**
 * Parse currency string to number
 * @param {string} value - Currency string (e.g., "1,500,000")
 * @returns {number} - Parsed number
 */
export const parseCurrency = (value) => {
  if (!value) return 0;
  const cleaned = String(value).replace(/[^0-9.-]/g, '');
  return parseFloat(cleaned) || 0;
};

/**
 * Format percentage
 * @param {number} value - Number to format (0-100)
 * @param {number} decimals - Decimal places (default: 2)
 * @returns {string} - Formatted string (e.g., "56.67%")
 */
export const formatPercentage = (value, decimals = 2) => {
  if (value === null || value === undefined || value === '') return '0%';
  if (isNaN(value)) return '0%';
  return numeral(value / 100).format(`0,0.${'0'.repeat(decimals)}%`);
};

/**
 * Format file size
 * @param {number} bytes - File size in bytes
 * @returns {string} - Formatted string (e.g., "1.5 MB")
 */
export const formatFileSize = (bytes) => {
  if (!bytes || bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

/**
 * Shorten long text with ellipsis
 * @param {string} text - Text to shorten
 * @param {number} maxLength - Maximum length (default: 50)
 * @returns {string} - Shortened text
 */
export const truncateText = (text, maxLength = 50) => {
  if (!text) return '';
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
};

/**
 * Format phone number Vietnamese
 * @param {string} phone - Phone number
 * @returns {string} - Formatted phone (e.g., "0912 345 678")
 */
export const formatPhone = (phone) => {
  if (!phone) return '';
  const cleaned = phone.replace(/\D/g, '');
  const match = cleaned.match(/^(\d{4})(\d{3})(\d{3})$/);
  if (match) {
    return `${match[1]} ${match[2]} ${match[3]}`;
  }
  return phone;
};

/**
 * Get relative time (e.g., "2 hours ago")
 * @param {string|Date} dateString
 * @returns {string}
 */
export const getRelativeTime = (dateString) => {
  if (!dateString) return '';
  
  try {
    const date = typeof dateString === 'string' ? parseISO(dateString) : dateString;
    const now = new Date();
    const diffMs = now - date;
    const diffSeconds = Math.floor(diffMs / 1000);
    const diffMinutes = Math.floor(diffSeconds / 60);
    const diffHours = Math.floor(diffMinutes / 60);
    const diffDays = Math.floor(diffHours / 24);
    
    if (diffSeconds < 60) return 'Vừa xong';
    if (diffMinutes < 60) return `${diffMinutes} phút trước`;
    if (diffHours < 24) return `${diffHours} giờ trước`;
    if (diffDays < 7) return `${diffDays} ngày trước`;
    
    return formatDateOnly(date);
  } catch (error) {
    return '';
  }
};

export default {
  formatCurrency,
  formatCurrencySymbol,
  formatDate,
  formatDateOnly,
  formatTimeOnly,
  formatNumber,
  formatDecimal,
  parseCurrency,
  formatPercentage,
  formatFileSize,
  truncateText,
  formatPhone,
  getRelativeTime,
};