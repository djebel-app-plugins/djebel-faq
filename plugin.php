<?php
/*
plugin_name: Djebel FAQ
plugin_uri: https://djebel.com/plugins/djebel-faq
description: Minimalistic FAQ plugin with collapsible items. Supports custom FAQ data with title, content, and auto-generated IDs.
version: 1.0.0
load_priority:20
tags: faq, accordion, help, support
stable_version: 1.0.0
min_php_ver: 5.6
min_dj_app_ver: 1.0.0
tested_with_dj_app_ver: 1.0.0
author_name: Svetoslav Marinov (Slavi)
company_name: Orbisius
author_uri: https://orbisius.com
text_domain: djebel-faq
license: gpl2
requires: djebel-markdown
*/

$obj = new Djebel_Faq_Plugin();

class Djebel_Faq_Plugin
{
    private $plugin_id = 'djebel-faq';
    private $cache_dir;
    private $current_collection_id;
    private $sort_by = 'file';

    public function __construct()
    {
        $this->cache_dir = Dj_App_Util::getCoreCacheDir(['plugin' => $this->plugin_id]);

        $shortcode_obj = Dj_App_Shortcode::getInstance();
        $shortcode_obj->addShortcode('djebel-faq', [ $this, 'renderFaq' ]);
    }

    public function renderFaq($params = [])
    {
        $title = empty($params['title']) ? 'Frequently Asked Questions' : trim($params['title']);
        $align = empty($params['align']) ? 'left' : trim($params['align']);
        $render_title = empty($params['render_title']) ? 0 : 1;
        $has_custom_title = !empty($params['title']);
        $faq_data = $this->getFaqData($params);
        
        if (empty($faq_data)) {
            return '<!-- No FAQ data available -->';
        }
        
        ?>
        <style>
        .djebel-plugin-faq-container {
            max-width: 800px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .djebel-plugin-faq-container.align-left {
            margin-left: 0;
            margin-right: auto;
        }
        
        .djebel-plugin-faq-container.align-right {
            margin-left: auto;
            margin-right: 0;
        }
        
        .djebel-plugin-faq-container.align-center {
            margin-left: auto;
            margin-right: auto;
        }
        
        .djebel-plugin-faq-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #1f2937;
            text-align: center;
        }
        
        .djebel-plugin-faq-item {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            overflow: hidden;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        
        .djebel-plugin-faq-item:hover {
            border-color: #d1d5db;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .djebel-plugin-faq-question {
            width: 100%;
            padding: 1rem 1.25rem;
            background: none;
            border: none;
            text-align: left;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            color: #374151;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s ease;
        }
        
        .djebel-plugin-faq-question:hover {
            background-color: #f9fafb;
        }
        
        .djebel-plugin-faq-question:focus {
            outline: 2px solid #3b82f6;
            outline-offset: -2px;
        }
        
        .djebel-plugin-faq-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            color: #6b7280;
            transition: transform 0.2s ease;
            flex-shrink: 0;
            margin-left: 1rem;
        }
        
        .djebel-plugin-faq-item.active .djebel-plugin-faq-icon {
            transform: rotate(45deg);
        }
        
        .djebel-plugin-faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background-color: #f9fafb;
        }
        
        .djebel-plugin-faq-answer-content {
            padding: 0 1.25rem 1rem 1.25rem;
            color: #4b5563;
            line-height: 1.6;
        }
        
        .djebel-plugin-faq-item.active .djebel-plugin-faq-answer {
            max-height: 500px;
        }
        
        @media (max-width: 640px) {
            .djebel-plugin-faq-container {
                margin: 0 1rem;
            }
            
            .djebel-plugin-faq-question {
                padding: 0.875rem 1rem;
                font-size: 0.9rem;
            }
            
            .djebel-plugin-faq-answer-content {
                padding: 0 1rem 0.875rem 1rem;
            }
        }
        </style>

        <div class="djebel-plugin-faq-container align-<?php echo Djebel_App_HTML::encodeEntities($align); ?>">
            <?php
            if ($has_custom_title || $render_title) {
            ?>
                <h2 class="djebel-plugin-faq-title"><?php echo Djebel_App_HTML::encodeEntities($title); ?></h2>
            <?php
            }
            ?>

            <div class="djebel-plugin-faq-list">
                <?php foreach ($faq_data as $faq) {
                    $faq_id = empty($faq['id']) ? '' : $faq['id'];
                    $faq_title = empty($faq['title']) ? '' : $faq['title'];
                ?>
                    <div class="djebel-plugin-faq-item" data-faq-id="<?php echo Djebel_App_HTML::escAttr($faq_id); ?>">
                        <button class="djebel-plugin-faq-question" type="button" aria-expanded="false">
                            <span><?php echo Djebel_App_HTML::encodeEntities($faq_title); ?></span>
                            <span class="djebel-plugin-faq-icon">+</span>
                        </button>
                        <div class="djebel-plugin-faq-answer">
                            <div class="djebel-plugin-faq-answer-content">
                                <?php
                                $content = empty($faq['content']) ? '' : $faq['content'];
                                echo $this->sanitizeContent($content);
                                ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        
        <script>
        (function() {
            'use strict';
            
            function initFaq() {
                const faqItems = document.querySelectorAll('.djebel-plugin-faq-item');
                
                if (faqItems.length === 0) {
                    return;
                }
                
                faqItems.forEach(function(item) {
                    const question = item.querySelector('.djebel-plugin-faq-question');
                    const answer = item.querySelector('.djebel-plugin-faq-answer');
                    
                    if (!question || !answer) {
                        return;
                    }
                    
                    question.addEventListener('click', function() {
                        const isActive = item.classList.contains('active');
                        
                        // Close all other items
                        faqItems.forEach(function(otherItem) {
                            if (otherItem !== item) {
                                otherItem.classList.remove('active');
                                const otherQuestion = otherItem.querySelector('.djebel-plugin-faq-question');
                                if (otherQuestion) {
                                    otherQuestion.setAttribute('aria-expanded', 'false');
                                }
                            }
                        });
                        
                        // Toggle current item
                        if (isActive) {
                            item.classList.remove('active');
                            question.setAttribute('aria-expanded', 'false');
                        } else {
                            item.classList.add('active');
                            question.setAttribute('aria-expanded', 'true');
                        }
                    });
                });
            }
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initFaq);
            } else {
                initFaq();
            }
        })();
        </script>
        <?php
    }

    public function getFaqData($params = [])
    {
        $collection_id = empty($params['id']) ? 'default' : trim($params['id']);
        $this->current_collection_id = Dj_App_String_Util::formatSlug($collection_id);

        $cache_key = $this->plugin_id . '-' . $this->current_collection_id;
        $cache_params = ['plugin' => $this->plugin_id, 'ttl' => 8 * 60 * 60]; // 8 hours

        $options_obj = Dj_App_Options::getInstance();

        $cache_faq = $options_obj->get('plugins.djebel-faq.cache');
        $cache_faq = !Dj_App_Util::isDisabled($cache_faq); // if not explicitly disabled.

        // Try to get from cache
        $cached_data = $cache_faq ? Dj_App_Cache::get($cache_key, $cache_params) : false;

        if (!empty($cached_data)) {
            return $cached_data;
        }

        // Generate fresh data
        $faq_data = $this->generateFaqData($params);

        // Save to cache
        Dj_App_Cache::set($cache_key, $faq_data, $cache_params);

        return $faq_data;
    }
    
    private function generateFaqData($params = [])
    {
        $faq_data = [];
        $data_dir = $this->getDataDirectory($params);

        if (!is_dir($data_dir)) {
            return [];
        }

        // Scan for markdown files
        $md_files = glob($data_dir . '/*.md');

        foreach ($md_files as $file) {
            $faq_item = $this->loadFaqFromMarkdown($file);

            if ($faq_item) {
                $faq_data[] = $faq_item;
            }
        }

        $scan_for_json_files = Dj_App_Hooks::applyFilter('app.plugin.faq.scan_for_json', false);

        if ($scan_for_json_files) { // Scan for JSON files (backward compatibility)
            $json_files = glob($data_dir . '/*.json');

            foreach ($json_files as $file) {
                $faq_item = $this->loadFaqFromJson($file);

                if ($faq_item) {
                    $faq_data[] = $faq_item;
                }
            }
        }

        // Allow customizing sort field
        $options_obj = Dj_App_Options::getInstance();
        $sort_by = $options_obj->get('plugins.djebel-faq.sort_by');

        if (!empty($sort_by)) {
            $this->sort_by = $sort_by;
        }

        $this->sort_by = Dj_App_Hooks::applyFilter('app.plugin.faq.sort_by', $this->sort_by);

        // Sort by configured field
        usort($faq_data, [ $this, 'sortFaqItems' ]);

        // Allow filtering of FAQ data
        $faq_data = Dj_App_Hooks::applyFilter('app.plugin.faq.data', $faq_data);

        return $faq_data;
    }

    private function getDataDirectory($params = [])
    {
        $collection_id = empty($params['id']) ? 'default' : trim($params['id']);
        $formatted_id = Dj_App_String_Util::formatSlug($collection_id);
        $data_dir = Dj_App_Util::getCorePrivateDataDir(['plugin' => $this->plugin_id]) . '/' . $formatted_id;
        return $data_dir;
    }
    
    private function getCurrentCollectionId()
    {
        $collection_id = empty($this->current_collection_id) ? 'default' : $this->current_collection_id;
        return $collection_id;
    }
    
    private function loadFaqFromJson($file)
    {
        if (!file_exists($file)) {
            $result = null;
            return $result;
        }

        $read_result = Dj_App_File_Util::read($file);

        if ($read_result->isError() || empty($read_result->output)) {
            $result = null;
            return $result;
        }

        $json_content = $read_result->output;

        $data = Dj_App_String_Util::jsonDecode($json_content);

        if (empty($data)) {
            $result = null;
            return $result;
        }

        if (empty($data['meta']) || empty($data['data'])) {
            $result = null;
            return $result;
        }

        $meta = $data['meta'];
        $faq_data = $data['data'];

        // Only return active FAQs
        if (empty($meta['status']) || $meta['status'] !== 'active') {
            $result = null;
            return $result;
        }

        $result = [
            'id' => $this->getHash($meta),
            'title' => empty($meta['title']) ? '' : $meta['title'],
            'content' => empty($faq_data['content']) ? '' : $faq_data['content'],
            'creation_date' => empty($meta['creation_date']) ? '' : $meta['creation_date'],
            'sort_order' => empty($meta['sort_order']) ? 0 : $meta['sort_order'],
            'category' => empty($meta['category']) ? 'general' : $meta['category'],
            'tags' => empty($faq_data['tags']) ? [] : $faq_data['tags'],
            'related_faqs' => empty($faq_data['related_faqs']) ? [] : $faq_data['related_faqs'],
        ];

        return $result;
    }

    /**
     * Loads FAQ data from a Markdown file with frontmatter.
     *
     * @param string $file Path to .md file
     * @return array|null FAQ data array or null if invalid
     */
    private function loadFaqFromMarkdown($file)
    {
        if (!file_exists($file)) {
            $result = null;
            return $result;
        }

        // Parse frontmatter via markdown plugin (it reads the file from $ctx)
        $ctx = [
            'file' => $file,
            'full' => 1,
        ];

        $parse_res = Dj_App_Hooks::applyFilter('app.plugins.markdown.parse_front_matter', '', $ctx);

        if (!is_object($parse_res) || $parse_res->isError()) {
            $result = null;
            return $result;
        }

        $meta = $parse_res->meta;
        $content = $parse_res->content;

        // Only return active FAQs (default to active if not specified)
        $status = empty($meta['status']) ? 'active' : $meta['status'];

        if ($status !== 'active') {
            $result = null;
            return $result;
        }

        // Convert markdown to HTML via hook
        $ctx = [
            'source' => 'djebel-faq',
            'file' => $file,
            'full' => 1,
        ];

        $html_content = Dj_App_Hooks::applyFilter('app.plugins.markdown.convert_markdown', $content, $ctx);

        // Fallback to raw content if no markdown processor registered
        if (empty($html_content)) {
            $html_content = $content;
        }

        $result = [
            'id' => $this->getHash($meta),
            'title' => empty($meta['title']) ? '' : $meta['title'],
            'content' => $html_content,
            'creation_date' => empty($meta['creation_date']) ? '' : $meta['creation_date'],
            'sort_order' => empty($meta['sort_order']) ? 0 : (int) $meta['sort_order'],
            'category' => empty($meta['category']) ? 'general' : $meta['category'],
            'tags' => empty($meta['tags']) ? [] : (array) $meta['tags'],
            'related_faqs' => empty($meta['related_faqs']) ? [] : (array) $meta['related_faqs'],
            'file' => $file,
        ];

        return $result;
    }

    /**
     * Get hash ID from FAQ metadata with fallback support
     *
     * @param array $meta Front matter metadata
     * @return string Hash ID from metadata, empty if not found
     */
    private function getHash($meta = [])
    {
        $hash_id = empty($meta['hash_id']) ? '' : $meta['hash_id'];

        if (empty($hash_id)) {
            $hash_id = empty($meta['hash']) ? '' : $meta['hash'];
        }

        if (empty($hash_id)) {
            $hash_id = empty($meta['id']) ? '' : $meta['id'];
        }

        return $hash_id;
    }

    private function sortFaqItems($a, $b)
    {
        $field = $this->sort_by;
        $val_a = false;
        $val_b = false;

        // Get values based on sort field
        if ($field === 'file') {
            $val_a = isset($a['file']) ? basename($a['file']) : false;
            $val_b = isset($b['file']) ? basename($b['file']) : false;
        } elseif ($field === 'creation_date') {
            $val_a = isset($a['creation_date']) ? strtotime($a['creation_date']) : false;
            $val_b = isset($b['creation_date']) ? strtotime($b['creation_date']) : false;
        } elseif ($field === 'last_modified') {
            $val_a = isset($a['last_modified']) ? strtotime($a['last_modified']) : false;
            $val_b = isset($b['last_modified']) ? strtotime($b['last_modified']) : false;
        } elseif ($field === 'title') {
            $val_a = isset($a['title']) ? $a['title'] : false;
            $val_b = isset($b['title']) ? $b['title'] : false;
        } elseif ($field === 'sort_order') {
            $val_a = isset($a['sort_order']) ? $a['sort_order'] : false;
            $val_b = isset($b['sort_order']) ? $b['sort_order'] : false;
        }

        // Handle missing values
        if ($val_a && !$val_b) {
            return -1;
        }

        if (!$val_a && $val_b) {
            return 1;
        }

        // Both have values - compare
        if ($val_a && $val_b) {
            if (is_numeric($val_a) && is_numeric($val_b)) {
                return $val_a - $val_b;
            } else {
                return strcasecmp($val_a, $val_b);
            }
        }

        // Fallback: sort by title
        return strcasecmp($a['title'], $b['title']);
    }

    private function sanitizeContent($content)
    {
        if (empty($content)) {
            return '';
        }

        // Allow safe HTML tags for FAQ content
        $allowed_tags = '<p><br><strong><em><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';

        // Strip potentially dangerous tags and attributes
        $content = strip_tags($content, $allowed_tags);

        // Additional security: remove any javascript: or data: attributes
        if (strpos($content, ' on') !== false) {
            $content = preg_replace('#\s*on\w+\s*=\s*["\'][^"\']*["\']#si', '', $content);
        }

        if (stripos($content, 'javascript:') !== false) {
            $content = preg_replace('#\s*javascript\s*:#si', '', $content);
        }

        if (stripos($content, 'data:') !== false) {
            $content = preg_replace('#\s*data\s*:#si', '', $content);
        }

        $content = trim($content);

        return $content;
    }
}