<?php
namespace PDF;

class PDFHelper {
    // Define a global variable for the PDF cache directory
    private static $pdf_cache_dir;

    // Automatically initialize the PDF cache directory
    private static function initializeCacheDir() {
        if (!self::$pdf_cache_dir) {
            $upload_dir = wp_upload_dir();
            self::$pdf_cache_dir = $upload_dir['basedir'] . '/ai_chatbot_pdfs/';

            // Ensure the directory exists
            if (!file_exists(self::$pdf_cache_dir)) {
                wp_mkdir_p(self::$pdf_cache_dir);
            }
        }
    }

    // Refactored to ensure the cache directory is always initialized
    private static function getCachePaths($hash) {
        self::initializeCacheDir();
        $pdf_path = self::$pdf_cache_dir . "$hash.pdf";
        $txt_path = self::$pdf_cache_dir . "$hash.txt";
        return [$pdf_path, $txt_path];
    }

    public static function extractTextFromPost($post_id) {
        $content = get_post_field('post_content', $post_id);
        preg_match_all('/https?:\/\/[^"\']+\.pdf/', $content, $matches);
        $pdf_urls = array_unique($matches[0]);

        $all_text = '';
        foreach ($pdf_urls as $url) {
            $hash = md5($url);
            list($pdf_path, $txt_path) = self::getCachePaths($hash);

            // Create folder if not exists
            if (!file_exists(self::$pdf_cache_dir)) {
                wp_mkdir_p(self::$pdf_cache_dir);
            }

            // Download PDF if not already cached
            if (!file_exists($pdf_path)) {
                $pdf_data = wp_remote_get($url);
                if (is_wp_error($pdf_data)) {
                    error_log("Failed to download PDF: $url");
                    continue;
                }
                file_put_contents($pdf_path, wp_remote_retrieve_body($pdf_data));
            }

            // Convert to text if not already done
            if (file_exists($pdf_path) && !file_exists($txt_path)) {
                $cmd = escapeshellcmd("pdftotext '$pdf_path' '$txt_path'");
                shell_exec($cmd);
                if (!file_exists($txt_path)) {
                    error_log("Failed to convert PDF to text: $pdf_path");
                    continue;
                }
            }

            // Read the text
            if (file_exists($txt_path)) {
                $all_text .= file_get_contents($txt_path) . "\n\n";
            }
        }

        return $all_text;
    }

    public static function searchForTerm($term) {
        self::initializeCacheDir();

        $matching_pdfs = [];
        $files = glob(self::$pdf_cache_dir . '*.txt'); // Get all cached text files

        foreach ($files as $txt_file) {
            $content = file_get_contents($txt_file);
            if (stripos($content, $term) !== false) {
                $hash = basename($txt_file, '.txt');
                $upload_dir = wp_upload_dir();
                $pdf_url = $upload_dir['baseurl'] . "/ai_chatbot_pdfs/$hash.pdf";
                $matching_pdfs[] = $pdf_url;
            }
        }

        return $matching_pdfs;
    }
}