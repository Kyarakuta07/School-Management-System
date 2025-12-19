<?php
/**
 * Image Optimization Utilities
 * Mediterranean of Egypt - School Management System
 * 
 * Provides image optimization, lazy loading, and responsive images.
 * 
 * @author MOE Development Team
 * @version 1.0.0
 */

// ================================================
// LAZY LOADING IMAGES
// ================================================

/**
 * Generate lazy loading image tag
 * @param string $src Image source
 * @param string $alt Alt text
 * @param array $options Additional options
 * @return string HTML img tag
 */
function lazy_img($src, $alt = '', $options = [])
{
    $class = isset($options['class']) ? $options['class'] : '';
    $width = isset($options['width']) ? 'width="' . $options['width'] . '"' : '';
    $height = isset($options['height']) ? 'height="' . $options['height'] . '"' : '';
    $placeholder = isset($options['placeholder']) ? $options['placeholder'] : 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    return sprintf(
        '<img src="%s" data-src="%s" alt="%s" class="lazy %s" %s %s loading="lazy">',
        htmlspecialchars($placeholder),
        htmlspecialchars($src),
        htmlspecialchars($alt),
        htmlspecialchars($class),
        $width,
        $height
    );
}

/**
 * Generate responsive image with srcset
 * @param string $base_path Base image path (without extension)
 * @param string $extension Image extension
 * @param string $alt Alt text
 * @param array $sizes Array of widths
 * @return string HTML picture element
 */
function responsive_img($base_path, $extension, $alt = '', $sizes = [320, 640, 1024])
{
    $srcset = [];

    foreach ($sizes as $size) {
        $srcset[] = "{$base_path}_{$size}.{$extension} {$size}w";
    }

    $srcset_str = implode(', ', $srcset);
    $default_src = $base_path . '_' . $sizes[0] . '.' . $extension;

    return sprintf(
        '<img srcset="%s" sizes="(max-width: 600px) 100vw, 50vw" src="%s" alt="%s" loading="lazy">',
        htmlspecialchars($srcset_str),
        htmlspecialchars($default_src),
        htmlspecialchars($alt)
    );
}

// ================================================
// JAVASCRIPT LAZY LOADING SNIPPET
// ================================================

/**
 * Get JavaScript for lazy loading images
 * Uses Intersection Observer for modern browsers
 * @return string JavaScript code
 */
function get_lazy_load_script()
{
    return <<<'JS'
<script>
(function() {
    'use strict';
    
    // Lazy load images using Intersection Observer
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img.lazy');
        
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    img.classList.add('lazy-loaded');
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        document.querySelectorAll('img.lazy').forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
        });
    }
})();
</script>
JS;
}

/**
 * Get CSS for lazy loading images
 * @return string CSS code
 */
function get_lazy_load_css()
{
    return <<<'CSS'
<style>
img.lazy {
    opacity: 0;
    transition: opacity 0.3s ease;
}

img.lazy-loaded {
    opacity: 1;
}

/* Placeholder shimmer effect */
img.lazy::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, #1a1a25 0%, #2a2a35 50%, #1a1a25 100%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>
CSS;
}

// ================================================
// ASSET PRELOADING
// ================================================

/**
 * Generate preload link tags for critical assets
 * @param array $assets Array of [url, type, crossorigin]
 * @return string HTML link tags
 */
function preload_assets($assets)
{
    $html = '';

    foreach ($assets as $asset) {
        $url = $asset['url'];
        $type = $asset['type'] ?? 'script';
        $crossorigin = isset($asset['crossorigin']) ? ' crossorigin' : '';

        $html .= sprintf(
            '<link rel="preload" href="%s" as="%s"%s>',
            htmlspecialchars($url),
            htmlspecialchars($type),
            $crossorigin
        ) . "\n";
    }

    return $html;
}

/**
 * Generate DNS prefetch for external resources
 * @param array $domains Array of domain names
 * @return string HTML link tags
 */
function dns_prefetch($domains)
{
    $html = '';

    foreach ($domains as $domain) {
        $html .= sprintf('<link rel="dns-prefetch" href="//%s">', htmlspecialchars($domain)) . "\n";
    }

    return $html;
}

// ================================================
// SCRIPT LOADING OPTIMIZATION
// ================================================

/**
 * Generate deferred script tag
 * @param string $src Script source
 * @param bool $module Whether to use ES6 module
 * @return string Script tag
 */
function deferred_script($src, $module = false)
{
    $type = $module ? ' type="module"' : '';
    return sprintf('<script src="%s"%s defer></script>', htmlspecialchars($src), $type);
}

/**
 * Generate async script tag
 * @param string $src Script source
 * @return string Script tag
 */
function async_script($src)
{
    return sprintf('<script src="%s" async></script>', htmlspecialchars($src));
}

/**
 * Generate inline script with JSON data
 * @param string $var_name JavaScript variable name
 * @param mixed $data Data to JSON encode
 * @return string Script tag with JSON data
 */
function inline_json_script($var_name, $data)
{
    $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    return sprintf('<script>window.%s = %s;</script>', $var_name, $json);
}
