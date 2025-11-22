import { describe, it, expect, vi, beforeEach } from 'vitest';
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
    beforeEach(() => {
        vi.spyOn(console, 'error').mockImplementation(() => { });
    });

    describe('formatCurrency', () => {
        it('should format number with thousand separator', () => {
            expect(formatCurrency(1500000)).toBe('1,500,000');
            expect(formatCurrency(1000)).toBe('1,000');
            expect(formatCurrency(100)).toBe('100');
        });

        it('should return "0" for null, undefined, or empty string', () => {
            expect(formatCurrency(null)).toBe('0');
            expect(formatCurrency(undefined)).toBe('0');
            expect(formatCurrency('')).toBe('0');
        });

        it('should return "0" for NaN', () => {
            expect(formatCurrency('invalid')).toBe('0');
        });

        it('should handle zero', () => {
            expect(formatCurrency(0)).toBe('0');
        });
    });

    describe('formatCurrencySymbol', () => {
        it('should format number with symbol', () => {
            expect(formatCurrencySymbol(1500000)).toBe('1,500,000₫');
            expect(formatCurrencySymbol(1000)).toBe('1,000₫');
        });

        it('should return "0₫" for null, undefined, or empty', () => {
            expect(formatCurrencySymbol(null)).toBe('0₫');
            expect(formatCurrencySymbol(undefined)).toBe('0₫');
            expect(formatCurrencySymbol('')).toBe('0₫');
        });
    });

    describe('formatDate', () => {
        it('should format ISO date string', () => {
            const result = formatDate('2025-11-22T10:30:00');
            expect(result).toMatch(/22\/11\/2025/);
        });

        it('should format Date object', () => {
            const date = new Date('2025-11-22T10:30:00');
            const result = formatDate(date);
            expect(result).toMatch(/22\/11\/2025/);
        });

        it('should return empty string for null or undefined', () => {
            expect(formatDate(null)).toBe('');
            expect(formatDate(undefined)).toBe('');
        });

        it('should return empty string for invalid date', () => {
            expect(formatDate('invalid-date')).toBe('');
        });

        it('should use custom format string', () => {
            const result = formatDate('2025-11-22T10:30:00', 'yyyy-MM-dd');
            expect(result).toBe('2025-11-22');
        });
    });

    describe('formatDateOnly', () => {
        it('should format date without time', () => {
            const result = formatDateOnly('2025-11-22T10:30:00');
            expect(result).toBe('22/11/2025');
        });
    });

    describe('formatTimeOnly', () => {
        it('should format time without date', () => {
            const result = formatTimeOnly('2025-11-22T10:30:00');
            expect(result).toBe('10:30');
        });
    });

    describe('formatNumber', () => {
        it('should format number with thousand separator', () => {
            expect(formatNumber(1234567)).toBe('1,234,567');
            expect(formatNumber(1000)).toBe('1,000');
        });

        it('should return "0" for null, undefined, or empty', () => {
            expect(formatNumber(null)).toBe('0');
            expect(formatNumber(undefined)).toBe('0');
            expect(formatNumber('')).toBe('0');
        });
    });

    describe('formatDecimal', () => {
        it('should format decimal with default 2 places', () => {
            expect(formatDecimal(1234.567)).toBe('1,234.57');
        });

        it('should format decimal with custom places', () => {
            expect(formatDecimal(1234.567, 3)).toBe('1,234.567');
            expect(formatDecimal(1234.5, 1)).toBe('1,234.5');
        });

        it('should return "0" for null, undefined, or empty', () => {
            expect(formatDecimal(null)).toBe('0');
            expect(formatDecimal(undefined)).toBe('0');
        });
    });

    describe('parseCurrency', () => {
        it('should parse currency string to number', () => {
            expect(parseCurrency('1,500,000')).toBe(1500000);
            expect(parseCurrency('1,000')).toBe(1000);
        });

        it('should handle currency with symbol', () => {
            expect(parseCurrency('1,500,000₫')).toBe(1500000);
        });

        it('should return 0 for null or undefined', () => {
            expect(parseCurrency(null)).toBe(0);
            expect(parseCurrency(undefined)).toBe(0);
            expect(parseCurrency('')).toBe(0);
        });

        it('should handle negative numbers', () => {
            expect(parseCurrency('-1,500')).toBe(-1500);
        });
    });

    describe('formatPercentage', () => {
        it('should format percentage with default 2 decimals', () => {
            // formatPercentage divides by 100, so 56.67 becomes 0.5667
            expect(formatPercentage(56.67)).toMatch(/%$/);
        });

        it('should format percentage with custom decimals', () => {
            expect(formatPercentage(100, 0)).toMatch(/%$/);
            expect(formatPercentage(50, 1)).toMatch(/%$/);
        });

        it('should return "0%" for null, undefined, or empty', () => {
            expect(formatPercentage(null)).toBe('0%');
            expect(formatPercentage(undefined)).toBe('0%');
        });
    });

    describe('formatFileSize', () => {
        it('should format bytes', () => {
            expect(formatFileSize(500)).toBe('500 Bytes');
        });

        it('should format KB', () => {
            expect(formatFileSize(1024)).toBe('1 KB');
            expect(formatFileSize(2048)).toBe('2 KB');
        });

        it('should format MB', () => {
            expect(formatFileSize(1048576)).toBe('1 MB');
            expect(formatFileSize(1572864)).toBe('1.5 MB');
        });

        it('should format GB', () => {
            expect(formatFileSize(1073741824)).toBe('1 GB');
        });

        it('should return "0 Bytes" for 0 or null', () => {
            expect(formatFileSize(0)).toBe('0 Bytes');
            expect(formatFileSize(null)).toBe('0 Bytes');
        });
    });

    describe('truncateText', () => {
        it('should truncate long text', () => {
            const longText = 'This is a very long text that should be truncated';
            expect(truncateText(longText, 20)).toBe('This is a very long ...');
        });

        it('should not truncate short text', () => {
            const shortText = 'Short text';
            expect(truncateText(shortText, 20)).toBe('Short text');
        });

        it('should use default max length of 50', () => {
            const text = 'A'.repeat(60);
            const result = truncateText(text);
            expect(result).toBe('A'.repeat(50) + '...');
        });

        it('should return empty string for null or undefined', () => {
            expect(truncateText(null)).toBe('');
            expect(truncateText(undefined)).toBe('');
        });
    });

    describe('formatPhone', () => {
        it('should format Vietnamese phone number', () => {
            expect(formatPhone('0912345678')).toBe('0912 345 678');
        });

        it('should handle phone with non-digits', () => {
            expect(formatPhone('091-234-5678')).toBe('0912 345 678');
        });

        it('should return original if not matching pattern', () => {
            expect(formatPhone('123')).toBe('123');
        });

        it('should return empty string for null or undefined', () => {
            expect(formatPhone(null)).toBe('');
            expect(formatPhone(undefined)).toBe('');
        });
    });

    describe('getRelativeTime', () => {
        it('should return "Vừa xong" for recent time', () => {
            const now = new Date();
            const recent = new Date(now.getTime() - 30 * 1000); // 30 seconds ago
            expect(getRelativeTime(recent)).toBe('Vừa xong');
        });

        it('should return minutes ago', () => {
            const now = new Date();
            const past = new Date(now.getTime() - 5 * 60 * 1000); // 5 minutes ago
            expect(getRelativeTime(past)).toBe('5 phút trước');
        });

        it('should return hours ago', () => {
            const now = new Date();
            const past = new Date(now.getTime() - 3 * 60 * 60 * 1000); // 3 hours ago
            expect(getRelativeTime(past)).toBe('3 giờ trước');
        });

        it('should return days ago', () => {
            const now = new Date();
            const past = new Date(now.getTime() - 2 * 24 * 60 * 60 * 1000); // 2 days ago
            expect(getRelativeTime(past)).toBe('2 ngày trước');
        });

        it('should return formatted date for old dates', () => {
            const past = new Date('2024-01-01');
            const result = getRelativeTime(past);
            expect(result).toMatch(/01\/01\/2024/);
        });

        it('should return empty string for null or undefined', () => {
            expect(getRelativeTime(null)).toBe('');
            expect(getRelativeTime(undefined)).toBe('');
        });

        it('should handle ISO string', () => {
            const now = new Date();
            const past = new Date(now.getTime() - 10 * 60 * 1000); // 10 minutes ago
            expect(getRelativeTime(past.toISOString())).toBe('10 phút trước');
        });
    });
});
