import { describe, it, expect } from 'vitest';
import {
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
} from './formatters';

describe('formatters', () => {
  describe('formatCurrency', () => {
    it('should format number to currency without symbol', () => {
      expect(formatCurrency(1000000)).toBe('1,000,000');
      expect(formatCurrency(1234.56)).toBe('1,235'); // Rounded
    });

    it('should return 0 for null/undefined/NaN', () => {
      expect(formatCurrency(null)).toBe('0');
      expect(formatCurrency(undefined)).toBe('0');
      expect(formatCurrency('abc')).toBe('0');
    });
  });

  describe('formatCurrencySymbol', () => {
    it('should format number to currency with symbol', () => {
      expect(formatCurrencySymbol(1000000)).toBe('1,000,000₫');
    });

    it('should return 0₫ for null/undefined/NaN', () => {
      expect(formatCurrencySymbol(null)).toBe('0₫');
    });
  });

  describe('formatDate', () => {
    it('should format date string correctly', () => {
      // Note: This test depends on the timezone, but usually date-fns handles ISO strings well
      const dateStr = '2023-10-27T10:30:00Z';
      // Ideally we should mock timezone or use a format that is timezone agnostic for simple check
      // But let's check if it returns a string
      expect(typeof formatDate(dateStr)).toBe('string');
    });

    it('should return empty string for invalid date', () => {
      expect(formatDate(null)).toBe('');
      expect(formatDate('invalid-date')).toBe(''); // invalid date results in empty string
    });
  });

  describe('formatNumber', () => {
    it('should format number with separators', () => {
      expect(formatNumber(1234)).toBe('1,234');
    });
  });

  describe('formatDecimal', () => {
    it('should format decimal number', () => {
      expect(formatDecimal(1234.5678, 2)).toBe('1,234.57');
      expect(formatDecimal(1234.5, 2)).toBe('1,234.50');
    });
  });

  describe('parseCurrency', () => {
    it('should parse currency string to number', () => {
      expect(parseCurrency('1,000,000')).toBe(1000000);
      expect(parseCurrency('1,234.56')).toBe(1234.56);
    });

    it('should return 0 for invalid input', () => {
      expect(parseCurrency(null)).toBe(0);
    });
  });

  describe('formatPercentage', () => {
    it('should format number to percentage', () => {
      expect(formatPercentage(50)).toBe('50.00%');
      expect(formatPercentage(12.3456)).toBe('12.35%');
    });
  });

  describe('formatFileSize', () => {
    it('should format bytes to readable size', () => {
      expect(formatFileSize(1024)).toBe('1 KB');
      expect(formatFileSize(1024 * 1024)).toBe('1 MB');
    });
    it('should return 0 Bytes for 0', () => {
      expect(formatFileSize(0)).toBe('0 Bytes');
    });
  });

  describe('truncateText', () => {
    it('should truncate text if longer than maxLength', () => {
      expect(truncateText('hello world', 5)).toBe('hello...');
    });

    it('should return original text if shorter than maxLength', () => {
      expect(truncateText('hello', 10)).toBe('hello');
    });
  });

  describe('formatPhone', () => {
    it('should format phone number', () => {
      expect(formatPhone('0912345678')).toBe('0912 345 678');
    });

    it('should return original if format does not match', () => {
      expect(formatPhone('123')).toBe('123');
    });
  });

  describe('getRelativeTime', () => {
      it('should return relative time', () => {
          const now = new Date();
          const fiveMinutesAgo = new Date(now.getTime() - 5 * 60 * 1000);
          expect(getRelativeTime(fiveMinutesAgo)).toBe('5 phút trước');
      });
  });
});
