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
*/

$obj = new Djebel_Faq_Plugin();

class Djebel_Faq_Plugin
{
    public function __construct()
    {
        $shortcode_obj = Dj_App_Shortcode::getInstance();
        $shortcode_obj->addShortcode('djebel-faq', [ $this, 'renderFaq' ]);
    }

    public function renderFaq($params = [])
    {
        $title = empty($params['title']) ? 'Frequently Asked Questions' : trim($params['title']);
        $faq_data = $this->getFaqData();
        
        ?>
        <style>
        .djebel-faq-container {
            max-width: 800px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .djebel-faq-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #1f2937;
            text-align: center;
        }
        
        .djebel-faq-item {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            overflow: hidden;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        
        .djebel-faq-item:hover {
            border-color: #d1d5db;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .djebel-faq-question {
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
        
        .djebel-faq-question:hover {
            background-color: #f9fafb;
        }
        
        .djebel-faq-question:focus {
            outline: 2px solid #3b82f6;
            outline-offset: -2px;
        }
        
        .djebel-faq-icon {
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
        
        .djebel-faq-item.active .djebel-faq-icon {
            transform: rotate(45deg);
        }
        
        .djebel-faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background-color: #f9fafb;
        }
        
        .djebel-faq-answer-content {
            padding: 0 1.25rem 1rem 1.25rem;
            color: #4b5563;
            line-height: 1.6;
        }
        
        .djebel-faq-item.active .djebel-faq-answer {
            max-height: 500px;
        }
        
        @media (max-width: 640px) {
            .djebel-faq-container {
                margin: 0 1rem;
            }
            
            .djebel-faq-question {
                padding: 0.875rem 1rem;
                font-size: 0.9rem;
            }
            
            .djebel-faq-answer-content {
                padding: 0 1rem 0.875rem 1rem;
            }
        }
        </style>
        
        <div class="djebel-faq-container">
            <h2 class="djebel-faq-title"><?php echo Djebel_App_HTML::encodeEntities($title); ?></h2>
            
            <div class="djebel-faq-list">
                <?php foreach ($faq_data as $faq) { ?>
                    <div class="djebel-faq-item" data-faq-id="<?php echo $faq['id']; ?>">
                        <button class="djebel-faq-question" type="button" aria-expanded="false">
                            <span><?php echo Djebel_App_HTML::encodeEntities($faq['title']); ?></span>
                            <span class="djebel-faq-icon">+</span>
                        </button>
                        <div class="djebel-faq-answer">
                            <div class="djebel-faq-answer-content">
                                <?php echo $faq['content']; ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const faqItems = document.querySelectorAll('.djebel-faq-item');
            
            faqItems.forEach(function(item) {
                const question = item.querySelector('.djebel-faq-question');
                const answer = item.querySelector('.djebel-faq-answer');
                
                question.addEventListener('click', function() {
                    const isActive = item.classList.contains('active');
                    
                    // Close all other items
                    faqItems.forEach(function(otherItem) {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                            otherItem.querySelector('.djebel-faq-question').setAttribute('aria-expanded', 'false');
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
        });
        </script>
        <?php
    }
    
    private function getFaqData()
    {
        // Sample FAQ data - can be replaced with database or external data source
        $faq_data = [
            [
                'title' => 'What is Djebel?',
                'content' => '<p>Djebel is a modern web application framework designed for building scalable and maintainable web applications. It provides a clean architecture, powerful features, and excellent developer experience.</p>',
                'id' => $this->generateId('What is Djebel?')
            ],
            [
                'title' => 'How do I install Djebel?',
                'content' => '<p>Installing Djebel is straightforward. Simply download the latest version from our website, extract the files to your web server directory, and follow the installation wizard. The process typically takes just a few minutes.</p>',
                'id' => $this->generateId('How do I install Djebel?')
            ],
            [
                'title' => 'Is Djebel free to use?',
                'content' => '<p>Yes, Djebel is completely free and open-source. You can use it for personal projects, commercial applications, or any other purpose without any licensing fees or restrictions.</p>',
                'id' => $this->generateId('Is Djebel free to use?')
            ],
            [
                'title' => 'What programming languages does Djebel support?',
                'content' => '<p>Djebel primarily supports PHP for backend development, along with HTML, CSS, and JavaScript for frontend development. It also includes built-in support for popular templating engines and modern JavaScript frameworks.</p>',
                'id' => $this->generateId('What programming languages does Djebel support?')
            ],
            [
                'title' => 'How can I get support?',
                'content' => '<p>We offer several support channels including our documentation website, community forums, GitHub issues, and direct email support. Our community is very active and helpful for both beginners and advanced users.</p>',
                'id' => $this->generateId('How can I get support?')
            ],
            [
                'title' => 'Can I contribute to Djebel development?',
                'content' => '<p>Absolutely! Djebel is an open-source project and we welcome contributions from the community. You can contribute by reporting bugs, suggesting features, submitting pull requests, or helping with documentation.</p>',
                'id' => $this->generateId('Can I contribute to Djebel development?')
            ]
        ];
        
        // Allow filtering of FAQ data
        $faq_data = Dj_App_Hooks::applyFilter('app.plugin.faq.data', $faq_data);
        
        return $faq_data;
    }
    
    private function generateId($title)
    {
        return substr(sha1($title), 0, 8);
    }
}