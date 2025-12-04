/**
 * Image URL utilities for handling Docker internal hostnames and relative paths
 */

/**
 * Get the base origin for image URLs, converting Docker hostnames to localhost
 * @returns {string} Base origin URL like "http://localhost:8000"
 */
export const getImageOrigin = () => {
    const apiBase =
        import.meta.env.VITE_API_URL ||
        import.meta.env.VITE_API_PROXY_TARGET ||
        import.meta.env.VITE_API_BASE_URL ||
        'http://localhost:8000';

    let baseOrigin = apiBase.replace(/\/api\/?$/, '');

    // Handle Docker internal hostnames - convert to localhost for browser access
    try {
        const parsed = new URL(baseOrigin);
        const hostname = parsed.hostname?.toLowerCase();
        if (['web', 'api', 'backend'].includes(hostname)) {
            baseOrigin = `${parsed.protocol}//localhost:${parsed.port || '8000'}`;
        }
    } catch (e) {
        baseOrigin = 'http://localhost:8000';
    }

    return baseOrigin;
};

/**
 * Convert a relative image path to a full URL accessible by the browser
 * Handles Docker internal hostnames automatically
 * 
 * @param {string} path - Image path (relative or full URL)
 * @param {string} [placeholder='/placeholder-product.png'] - Fallback for empty paths
 * @returns {string} Full image URL
 * 
 * @example
 * getImageUrl('/uploads/variants/image.png') 
 * // Returns: "http://localhost:8000/uploads/variants/image.png"
 * 
 * getImageUrl('https://example.com/image.png')
 * // Returns: "https://example.com/image.png"
 * 
 * getImageUrl(null)
 * // Returns: "/placeholder-product.png"
 */
export const getImageUrl = (path, placeholder = '/placeholder-product.png') => {
    if (!path) return placeholder;

    // Already a full URL - return as-is
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    // Data URL - return as-is
    if (path.startsWith('data:')) {
        return path;
    }

    const baseOrigin = getImageOrigin();
    return `${baseOrigin}${path.startsWith('/') ? '' : '/'}${path}`;
};

/**
 * Resolve image URL from an image object with multiple possible fields
 * 
 * @param {Object} image - Image object
 * @param {string} [placeholder] - Fallback for missing images
 * @returns {string} Resolved image URL
 */
export const resolveImageFromObject = (image, placeholder = '/placeholder-product.png') => {
    if (!image) return placeholder;

    const path = image.image_url || image.url || image.path || image.image_path || image.file_url || '';
    return getImageUrl(path, placeholder);
};

export default { getImageUrl, getImageOrigin, resolveImageFromObject };
