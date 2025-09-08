# Djebel FAQ Plugin

A minimalistic and user-friendly FAQ plugin for Djebel applications. Features collapsible accordion-style FAQ items with smooth animations and clean design.

## Features

- ✅ **Collapsible Design** - Clean accordion-style FAQ items
- ✅ **Minimalistic CSS** - Super clean and modern styling
- ✅ **Responsive Design** - Works perfectly on all devices
- ✅ **Auto-generated IDs** - SHA1-based IDs for each FAQ item
- ✅ **Smooth Animations** - Elegant expand/collapse transitions
- ✅ **Accessible** - Proper ARIA attributes and keyboard navigation
- ✅ **Customizable** - Easy to modify and extend
- ✅ **Sample Data** - Includes example FAQ content
- ✅ **Hook System** - Extensible with Djebel hooks and filters

## Installation

1. Place the plugin folder in your `dj-app/dj-content/plugins/` directory
2. The plugin will automatically load when Djebel starts
3. CSS and JavaScript are automatically included when the shortcode is used
4. No additional configuration required

## Usage

### Basic Shortcode

```
[djebel-faq]
```

### Shortcode with Custom Title

```
[djebel-faq title="Help & Support"]
```

### Shortcode with Alignment

```
[djebel-faq align="center"]
[djebel-faq align="right"]
[djebel-faq title="FAQ" align="center"]
```

### Shortcode with Title Control

```
[djebel-faq render_title="1"]
[djebel-faq title="Custom FAQ" render_title="1"]
[djebel-faq title="Help Center" render_title="1" align="center"]
```

### Shortcode with Different Collections

```
[djebel-faq id="technical"]
[djebel-faq id="support" title="Support FAQ"]
[djebel-faq id="general" align="center"]
[djebel-faq id="billing" title="Billing Questions" align="center"]
[djebel-faq id="api" title="API Documentation" render_title="1"]
```

## Shortcode Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `title` | string | `"Frequently Asked Questions"` | Custom title for the FAQ section (always renders when provided) |
| `align` | string | `"left"` | Alignment of the FAQ container (left, center, right) |
| `render_title` | boolean | `0` | Whether to display the default title (0 = hidden, 1 = visible) |
| `id` | string | `"default"` | FAQ collection ID to load from data directory |

### Parameter Examples

**Default FAQ section:**
```
[djebel-faq]
```

**Custom title:**
```
[djebel-faq title="Product Support"]
```

**Help section:**
```
[djebel-faq title="Need Help?"]
```

**Center-aligned FAQ:**
```
[djebel-faq align="center"]
```

**Right-aligned FAQ with custom title:**
```
[djebel-faq title="Product Support" align="right"]
```

**FAQ with default title displayed:**
```
[djebel-faq render_title="1"]
```

**Custom title (always shows when provided):**
```
[djebel-faq title="Help Center" align="center"]
```

**Clean FAQ without title (default):**
```
[djebel-faq]
```

**Different collection with custom title:**
```
[djebel-faq id="technical" title="Technical Support"]
```

**More Collection Examples:**
```
[djebel-faq id="support" title="Customer Support" align="center"]
[djebel-faq id="billing" title="Billing Questions"]
[djebel-faq id="api" title="API Documentation" render_title="1"]
[djebel-faq id="features" align="right"]
[djebel-faq id="troubleshooting" title="Troubleshooting Guide"]
```

## FAQ Data Structure

The plugin uses a simple array structure for FAQ data:

```php
$faq_data = [
    [
        'title' => 'Your Question Here',
        'content' => '<p>Your answer with HTML support</p>',
        'id' => 'auto_generated_id'
    ],
    // ... more FAQ items
];
```

### Data Fields

- **`title`** - The question/title displayed in the FAQ item
- **`content`** - The answer content (supports HTML)
- **`id`** - Auto-generated unique identifier (first 8 characters of SHA1 hash)

## Customization

### CSS Classes

The plugin uses the following CSS classes for styling:

- `.djebel-faq-container` - Main FAQ container
- `.djebel-faq-title` - FAQ section title
- `.djebel-faq-list` - FAQ items container
- `.djebel-faq-item` - Individual FAQ item
- `.djebel-faq-question` - Clickable question button
- `.djebel-faq-answer` - Collapsible answer container
- `.djebel-faq-answer-content` - Answer content wrapper
- `.djebel-faq-icon` - Plus/minus icon

### Hooks and Filters

#### Filters

**`app.plugin.faq.data`**
- Modify FAQ data before rendering
- Parameters: `$faq_data` - Array of FAQ items
- Return: Modified FAQ data array

### Example Customization

**Add custom FAQ data:**
```php
function my_custom_faq_data($faq_data) {
    $custom_faq = [
        'title' => 'Custom Question',
        'content' => '<p>Custom answer with <strong>HTML</strong> support</p>',
        'id' => 'custom_001'
    ];
    
    array_unshift($faq_data, $custom_faq);
    return $faq_data;
}

Dj_App_Hooks::addFilter('app.plugin.faq.data', 'my_custom_faq_data');
```

**Replace all FAQ data:**
```php
function replace_faq_data($faq_data) {
    return [
        [
            'title' => 'How do I get started?',
            'content' => '<p>Getting started is easy! Just follow our step-by-step guide.</p>',
            'id' => 'start_001'
        ],
        [
            'title' => 'What are the system requirements?',
            'content' => '<p>Djebel requires PHP 5.6+ and a modern web server.</p>',
            'id' => 'req_001'
        ]
    ];
}

Dj_App_Hooks::addFilter('app.plugin.faq.data', 'replace_faq_data');
```

## Creating Different FAQ Collections

To create different FAQ collections, create subdirectories in the data folder:

```
.ht_djebel/data/plugins/djebel-faq/
├── default/           # Default collection (id="default")
│   ├── 001-general.json
│   └── 002-installation.json
├── technical/         # Technical collection (id="technical")
│   ├── 001-api.json
│   └── 002-debugging.json
├── support/           # Support collection (id="support")
│   ├── 001-account.json
│   └── 002-billing.json
└── billing/           # Billing collection (id="billing")
    ├── 001-payments.json
    └── 002-refunds.json
```

Each collection directory should contain JSON files with FAQ data. The collection ID in the shortcode corresponds to the directory name.

## Design Features

### Visual Elements

- **Clean Typography** - Modern, readable fonts
- **Subtle Borders** - Light gray borders for definition
- **Hover Effects** - Smooth hover transitions
- **Focus States** - Accessible focus indicators
- **Responsive Layout** - Mobile-optimized design

### Icons

- **Collapsed State** - Plus (+) icon
- **Expanded State** - Minus (-) icon (rotated plus)
- **Smooth Rotation** - CSS transform animations

### Animations

- **Expand/Collapse** - Smooth height transitions
- **Icon Rotation** - 45-degree rotation for minus icon
- **Hover Effects** - Background color changes

## Browser Compatibility

- ✅ Chrome 60+
- ✅ Firefox 55+
- ✅ Safari 12+
- ✅ Edge 79+
- ✅ Mobile browsers

## Accessibility

- **Keyboard Navigation** - Full keyboard support
- **ARIA Attributes** - Proper `aria-expanded` states
- **Focus Management** - Clear focus indicators
- **Screen Reader Support** - Semantic HTML structure

## Requirements

- **PHP:** 5.6 or higher
- **Djebel App:** 1.0.0 or higher
- **JavaScript:** Enabled (for interactive functionality)

## Changelog

### Version 1.0.0
- Initial release
- Collapsible FAQ functionality
- Minimalistic design
- Sample FAQ data included
- Responsive layout
- Accessibility features
- Hook system integration

## Support

For support, feature requests, or bug reports, please contact the development team.

## License

This plugin is licensed under GPL v2 or later.

---

**Author:** Svetoslav Marinov (Slavi)  
**Company:** Orbisius  
**Website:** https://orbisius.com