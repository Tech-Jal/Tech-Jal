<?php
class Utils {
    /**
     * Format a timestamp into a human-readable date
     */
    public static function formatDate($timestamp, $format = 'F j, Y') {
        return date($format, strtotime($timestamp));
    }

    /**
     * Format time elapsed since a given timestamp
     */
    public static function timeAgo($timestamp) {
        $time = time() - strtotime($timestamp);
        
        $units = [
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        ];

        foreach ($units as $unit => $val) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits . ' ' . $val . ($numberOfUnits > 1 ? 's' : '') . ' ago';
        }

        return 'just now';
    }

    /**
     * Generate a random string
     */
    public static function generateRandomString($length = 10) {
        return bin2hex(random_bytes($length));
    }

    /**
     * Validate and process image upload
     */
    public static function handleImageUpload($file, $destination, $maxWidth = 2000, $maxHeight = 2000) {
        try {
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                throw new Exception('No file uploaded');
            }

            // Check file size
            if ($file['size'] > MAX_FILE_SIZE) {
                throw new Exception('File size too large');
            }

            // Check file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, ALLOWED_IMAGE_TYPES)) {
                throw new Exception('Invalid file type');
            }

            // Get image dimensions
            list($width, $height) = getimagesize($file['tmp_name']);

            // Check dimensions
            if ($width > $maxWidth || $height > $maxHeight) {
                throw new Exception('Image dimensions too large');
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = self::generateRandomString() . '.' . $extension;
            $filepath = $destination . '/' . $filename;

            // Move file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Failed to move uploaded file');
            }

            return $filename;
        } catch (Exception $e) {
            error_log("Image upload error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format file size in human-readable format
     */
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Truncate text to a specified length
     */
    public static function truncateText($text, $length = 100, $append = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        $truncated = substr($text, 0, $length);
        $lastSpace = strrpos($truncated, ' ');
        
        if ($lastSpace !== false) {
            $truncated = substr($truncated, 0, $lastSpace);
        }
        
        return $truncated . $append;
    }

    /**
     * Format recipe duration
     */
    public static function formatDuration($minutes) {
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($mins === 0) {
            return $hours . ' hr';
        }
        
        return $hours . ' hr ' . $mins . ' min';
    }

    /**
     * Format rating as stars
     */
    public static function formatRating($rating, $maxRating = 5) {
        $fullStar = '<i class="bi bi-star-fill text-warning"></i>';
        $halfStar = '<i class="bi bi-star-half text-warning"></i>';
        $emptyStar = '<i class="bi bi-star text-warning"></i>';
        
        $output = '';
        $rating = max(0, min($maxRating, $rating));
        
        for ($i = 1; $i <= $maxRating; $i++) {
            if ($rating >= $i) {
                $output .= $fullStar;
            } elseif ($rating > $i - 1) {
                $output .= $halfStar;
            } else {
                $output .= $emptyStar;
            }
        }
        
        return $output;
    }

    /**
     * Clean and validate URL
     */
    public static function sanitizeURL($url) {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }

    /**
     * Generate meta tags for social sharing
     */
    public static function generateMetaTags($data) {
        $tags = [];
        
        // Open Graph tags
        $tags[] = '<meta property="og:title" content="' . htmlspecialchars($data['title']) . '">';
        $tags[] = '<meta property="og:description" content="' . htmlspecialchars($data['description']) . '">';
        if (!empty($data['image'])) {
            $tags[] = '<meta property="og:image" content="' . htmlspecialchars($data['image']) . '">';
        }
        $tags[] = '<meta property="og:url" content="' . htmlspecialchars($data['url']) . '">';
        
        // Twitter Card tags
        $tags[] = '<meta name="twitter:card" content="summary_large_image">';
        $tags[] = '<meta name="twitter:title" content="' . htmlspecialchars($data['title']) . '">';
        $tags[] = '<meta name="twitter:description" content="' . htmlspecialchars($data['description']) . '">';
        if (!empty($data['image'])) {
            $tags[] = '<meta name="twitter:image" content="' . htmlspecialchars($data['image']) . '">';
        }
        
        return implode("\n", $tags);
    }

    /**
     * Generate breadcrumbs
     */
    public static function generateBreadcrumbs($items) {
        $output = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
        
        foreach ($items as $key => $item) {
            if ($key === array_key_last($items)) {
                $output .= '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($item['text']) . '</li>';
            } else {
                $output .= '<li class="breadcrumb-item"><a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['text']) . '</a></li>';
            }
        }
        
        $output .= '</ol></nav>';
        return $output;
    }

    /**
     * Generate pagination links
     */
    public static function generatePagination($currentPage, $totalPages, $urlPattern) {
        if ($totalPages <= 1) return '';
        
        $output = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
        
        // Previous button
        $prevClass = $currentPage <= 1 ? ' disabled' : '';
        $output .= '<li class="page-item' . $prevClass . '">';
        $output .= '<a class="page-link" href="' . sprintf($urlPattern, max(1, $currentPage - 1)) . '" aria-label="Previous">';
        $output .= '<span aria-hidden="true">&laquo;</span></a></li>';
        
        // Page numbers
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == $currentPage) {
                $output .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $output .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $i) . '">' . $i . '</a></li>';
            }
        }
        
        // Next button
        $nextClass = $currentPage >= $totalPages ? ' disabled' : '';
        $output .= '<li class="page-item' . $nextClass . '">';
        $output .= '<a class="page-link" href="' . sprintf($urlPattern, min($totalPages, $currentPage + 1)) . '" aria-label="Next">';
        $output .= '<span aria-hidden="true">&raquo;</span></a></li>';
        
        $output .= '</ul></nav>';
        return $output;
    }
} 