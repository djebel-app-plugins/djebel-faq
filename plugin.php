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
    // Sort field when the site sets none. Items ship as NNN-slug.md, so the file name
    // carries the author's intended order without any front matter.
    const SORT_BY_DEFAULT = 'file';

    // Appended after the configured sort field. Two items that tie on that field - or
    // don't carry it at all - keep falling through these until one of them orders them,
    // so a missing, unknown or duplicated sort value never leaves the list arbitrary.
    const SORT_FALLBACK_FIELDS = [ 'sort_order', 'file', 'title', ];

    private $plugin_id = 'djebel-faq';
    private $current_collection_id;

    // Memos, filled by getSortBy() / getSortFields() on first call.
    private $sort_by = '';
    private $sort_fields = [];

    public function __construct()
    {
        $shortcode_obj = Dj_App_Shortcode::getInstance();
        $shortcode_obj->addShortcode('djebel-faq', [ $this, 'renderFaq' ]);
    }

    public function renderFaq($params = [])
    {
        $title = empty($params['title']) ? 'Frequently Asked Questions' : $params['title'];
        $title = Dj_App_String_Util::trim($title);

        $align = empty($params['align']) ? 'left' : $params['align'];
        $align = Dj_App_String_Util::trim($align);

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
        
        /* The open row grows to whatever the answer actually is (0fr -> 1fr), so there is
           no height ceiling that could cut a long answer off. A browser that can't animate
           to fr units toggles instantly instead - it still never clips. */
        .djebel-plugin-faq-answer {
            display: grid;
            grid-template-rows: 0fr;
            overflow: hidden;
            transition: grid-template-rows 0.3s ease;
            background-color: #f9fafb;
        }

        .djebel-plugin-faq-answer-content {
            /* A grid item keeps its content height without this, so the row can't collapse. */
            min-height: 0;
            padding: 0 1.25rem 1rem 1.25rem;
            color: #4b5563;
            line-height: 1.6;
        }

        .djebel-plugin-faq-item.active .djebel-plugin-faq-answer {
            grid-template-rows: 1fr;
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
                                // Already sanitized in generateFaqData, before caching.
                                $content = empty($faq['content']) ? '' : $faq['content'];
                                echo $content;
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
        $collection_id = empty($params['id']) ? 'default' : $params['id'];
        $collection_id = Dj_App_String_Util::trim($collection_id);
        $this->current_collection_id = Dj_App_String_Util::formatSlug($collection_id);

        // What gets cached is the SORTED list, so the sort field belongs in the key —
        // otherwise changing it keeps serving the previous order until the entry expires.
        $sort_by = $this->getSortBy();
        $cache_key = $this->plugin_id . '-' . $this->current_collection_id . '-' . $sort_by;
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
    
    /**
     * Generate FAQ data by scanning data directory for markdown/json files
     * @param array $params Optional params with 'id' for collection
     * @return array
     */
    public function generateFaqData($params = [])
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

        usort($faq_data, [ $this, 'sortFaqItems' ]);

        // Allow filtering of FAQ data
        $faq_data = Dj_App_Hooks::applyFilter('app.plugin.faq.data', $faq_data);

        // Sanitized here, not at render: this result is what gets cached, so the
        // strip_tags + preg_replace pass runs once per cache fill instead of on every
        // request. Runs after the data filter so hooked-in content is covered too.
        foreach ($faq_data as $idx => $faq_item) {
            if (empty($faq_item['content'])) {
                continue;
            }

            $faq_data[$idx]['content'] = $this->sanitizeContent($faq_item['content']);
        }

        return $faq_data;
    }

    /**
     * This plugin's cache directory. Resolved on first call and memoized, so a request
     * that never reaches the cache dir never pays for resolving it.
     *
     * @return string
     */
    public function getCacheDir()
    {
        static $cache_dir = null;

        if (is_null($cache_dir)) {
            $dir_params = [ 'plugin' => $this->plugin_id, ];
            $cache_dir = Dj_App_Util::getCoreCacheDir($dir_params);
        }

        return $cache_dir;
    }

    /**
     * Get data directory path for a FAQ collection
     * Checks public dir first, falls back to private dir
     * @param array $params Optional params with 'id' for collection
     * @return string
     */
    public function getDataDirectory($params = [])
    {
        $collection_id = empty($params['id']) ? 'default' : $params['id'];
        $collection_id = Dj_App_String_Util::trim($collection_id);
        $formatted_id = Dj_App_String_Util::formatSlug($collection_id);

        $plugin_params = [ 'plugin' => $this->plugin_id, ];

        // Check public data dir first (dj-content/data/app/plugins/)
        $data_dir = Dj_App_Util::getContentDataDir($plugin_params) . '/' . $formatted_id;

        if (is_dir($data_dir)) {
            return $data_dir;
        }

        // Fall back to private data dir (.ht_djebel/data/app/plugins/)
        $data_dir = Dj_App_Util::getCorePrivateDataDir($plugin_params) . '/' . $formatted_id;

        return $data_dir;
    }
    
    /**
     * Get the current FAQ collection ID
     * @return string
     */
    public function getCurrentCollectionId()
    {
        $collection_id = empty($this->current_collection_id) ? 'default' : $this->current_collection_id;
        return $collection_id;
    }
    
    /**
     * Load FAQ item from a JSON file
     * @param string $file Path to .json file
     * @return array|null FAQ data array or null if invalid
     */
    public function loadFaqFromJson($file)
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

        $hash_id = $this->getHash($meta);
        $title = empty($meta['title']) ? '' : $meta['title'];
        $content = empty($faq_data['content']) ? '' : $faq_data['content'];
        $creation_date = empty($meta['creation_date']) ? '' : $meta['creation_date'];
        $last_modified = empty($meta['last_modified']) ? '' : $meta['last_modified'];
        $sort_order = empty($meta['sort_order']) ? 0 : $meta['sort_order'];
        $category = empty($meta['category']) ? 'general' : $meta['category'];
        $tags = empty($faq_data['tags']) ? [] : $faq_data['tags'];
        $related_faqs = empty($faq_data['related_faqs']) ? [] : $faq_data['related_faqs'];

        $result = [
            'id' => $hash_id,
            'title' => $title,
            'content' => $content,
            'creation_date' => $creation_date,
            'last_modified' => $last_modified,
            'sort_order' => $sort_order,
            'category' => $category,
            'tags' => $tags,
            'related_faqs' => $related_faqs,
            'file' => $file,
        ];

        return $result;
    }

    /**
     * Loads FAQ data from a Markdown file with frontmatter.
     *
     * @param string $file Path to .md file
     * @return array|null FAQ data array or null if invalid
     */
    public function loadFaqFromMarkdown($file)
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

        $hash_id = $this->getHash($meta);
        $title = empty($meta['title']) ? '' : $meta['title'];
        $creation_date = empty($meta['creation_date']) ? '' : $meta['creation_date'];
        $last_modified = empty($meta['last_modified']) ? '' : $meta['last_modified'];
        $sort_order = empty($meta['sort_order']) ? 0 : $meta['sort_order'];
        $sort_order = (int) $sort_order;
        $category = empty($meta['category']) ? 'general' : $meta['category'];
        $tags = empty($meta['tags']) ? [] : $meta['tags'];
        $tags = (array) $tags;
        $related_faqs = empty($meta['related_faqs']) ? [] : $meta['related_faqs'];
        $related_faqs = (array) $related_faqs;

        $result = [
            'id' => $hash_id,
            'title' => $title,
            'content' => $html_content,
            'creation_date' => $creation_date,
            'last_modified' => $last_modified,
            'sort_order' => $sort_order,
            'category' => $category,
            'tags' => $tags,
            'related_faqs' => $related_faqs,
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
    public function getHash($meta = [])
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

    /**
     * The field the sort compares on first. Reads plugins.djebel-faq.sort_by, falls back
     * to SORT_BY_DEFAULT, and is filterable via app.plugin.faq.sort_by. Memoized, so the
     * option lookup and the filter run once per request.
     *
     * @return string
     */
    public function getSortBy()
    {
        if (!empty($this->sort_by)) {
            return $this->sort_by;
        }

        $options_obj = Dj_App_Options::getInstance();
        $sort_by = $options_obj->get('plugins.djebel-faq.sort_by', self::SORT_BY_DEFAULT);
        $sort_by = Dj_App_String_Util::trim($sort_by);
        $sort_by = Dj_App_Hooks::applyFilter('app.plugin.faq.sort_by', $sort_by);

        // A listener clearing it would leave the chain headed by an empty field name.
        if (empty($sort_by)) {
            $sort_by = self::SORT_BY_DEFAULT;
        }

        $this->sort_by = $sort_by;

        return $this->sort_by;
    }

    /**
     * The field the sort compares on first, followed by the fallback fields that break
     * ties and cover items that don't carry it.
     *
     * Filterable via app.plugin.faq.sort_fields, so a site can reorder the chain, add its
     * own front-matter field, or replace it outright; a listener returning anything
     * unusable is ignored rather than left to break the sort. Memoized, so the filter
     * runs once per request rather than on every comparison.
     *
     * @return array Sort field names, most significant first
     */
    public function getSortFields()
    {
        if (!empty($this->sort_fields)) {
            return $this->sort_fields;
        }

        $sort_by = $this->getSortBy();

        // array_unique keeps the first occurrence, so the configured field stays the
        // primary sort even when it repeats in the fallback list.
        $sort_fields = [ $sort_by, ];
        $sort_fields = array_merge($sort_fields, self::SORT_FALLBACK_FIELDS);
        $sort_fields = array_unique($sort_fields);

        $ctx = [ 'sort_by' => $sort_by, ];

        $filtered_sort_fields = Dj_App_Hooks::applyFilter('app.plugin.faq.sort_fields', $sort_fields, $ctx);

        if (!empty($filtered_sort_fields) && is_array($filtered_sort_fields)) {
            $sort_fields = $filtered_sort_fields;
        }

        $this->sort_fields = $sort_fields;

        return $this->sort_fields;
    }

    /**
     * Reads one FAQ item's value for a sort field, normalized so it can be compared:
     * a file becomes its base name, a date becomes a timestamp.
     *
     * @param array $item FAQ item
     * @param string $field Field name to read
     * @return mixed Comparable value, or '' when the field is absent or unusable
     */
    public function getSortValue($item, $field)
    {
        // isset() (not empty()) so a real zero - sort_order: 0 - stays a value, not a miss.
        if (!isset($item[$field])) {
            return '';
        }

        $val = $item[$field];

        if ($field == 'file') {
            $val = Dj_App_File_Util::getBasename($val);

            return $val;
        }

        if ($field == 'creation_date' || $field == 'last_modified') {
            $ts = strtotime($val);

            // An unparsable date orders nothing - report it as absent instead.
            if (empty($ts)) {
                return '';
            }

            return $ts;
        }

        return $val;
    }

    /**
     * Sort callback for FAQ items. Compares on the configured field first, then walks
     * the fallback fields, so the order stays stable and predictable whether the site
     * set plugins.djebel-faq.sort_by or not - and even when it names a field the items
     * don't carry.
     *
     * @param array $a First FAQ item
     * @param array $b Second FAQ item
     * @return int
     */
    public function sortFaqItems($a, $b)
    {
        $sort_fields = $this->getSortFields();

        foreach ($sort_fields as $field) {
            $val_a = $this->getSortValue($a, $field);
            $val_b = $this->getSortValue($b, $field);

            // Strict === here: '' is the absent marker, and a real 0 must not match it.
            if ($val_a === '' && $val_b === '') {
                continue;
            }

            // An item carrying the field wins over one that doesn't.
            if ($val_a === '') {
                return 1;
            }

            if ($val_b === '') {
                return -1;
            }

            if (is_numeric($val_a) && is_numeric($val_b)) {
                // Compared, not subtracted - a fractional difference truncates to 0.
                if ($val_a < $val_b) {
                    return -1;
                }

                if ($val_b < $val_a) {
                    return 1;
                }

                continue;
            }

            $diff = strcasecmp($val_a, $val_b);

            if ($diff != 0) {
                return $diff;
            }
        }

        return 0;
    }

    /**
     * Sanitize FAQ content, allowing safe HTML tags
     * @param string $content Raw HTML content
     * @return string Sanitized content
     */
    public function sanitizeContent($content)
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

        $content = Dj_App_String_Util::trim($content);

        return $content;
    }
}