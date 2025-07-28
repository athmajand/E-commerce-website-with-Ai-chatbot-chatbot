<?php
/**
 * Accessibility Helper Functions for Kisan Kart
 * 
 * These functions help create accessible UI components
 */

/**
 * Create an accessible icon button
 * 
 * @param string $icon_class The icon class (e.g., 'bi bi-heart')
 * @param string $text The text for screen readers
 * @param string $button_class Additional button classes
 * @param array $attributes Additional button attributes
 * @return string The HTML for the accessible button
 */
function accessible_icon_button($icon_class, $text, $button_class = '', $attributes = []) {
    $attr_str = '';
    foreach ($attributes as $key => $value) {
        $attr_str .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
    
    return '<button class="' . htmlspecialchars($button_class) . '"' . $attr_str . ' aria-label="' . htmlspecialchars($text) . '">
        <i class="' . htmlspecialchars($icon_class) . '" aria-hidden="true"></i>
        <span class="sr-only">' . htmlspecialchars($text) . '</span>
    </button>';
}

/**
 * Create an accessible icon link
 * 
 * @param string $url The URL to link to
 * @param string $icon_class The icon class (e.g., 'bi bi-heart')
 * @param string $text The text for screen readers
 * @param string $link_class Additional link classes
 * @param array $attributes Additional link attributes
 * @return string The HTML for the accessible link
 */
function accessible_icon_link($url, $icon_class, $text, $link_class = '', $attributes = []) {
    $attr_str = '';
    foreach ($attributes as $key => $value) {
        $attr_str .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
    
    return '<a href="' . htmlspecialchars($url) . '" class="' . htmlspecialchars($link_class) . '"' . $attr_str . ' aria-label="' . htmlspecialchars($text) . '">
        <i class="' . htmlspecialchars($icon_class) . '" aria-hidden="true"></i>
        <span class="sr-only">' . htmlspecialchars($text) . '</span>
    </a>';
}

/**
 * Create an accessible select element
 * 
 * @param string $name The select name attribute
 * @param array $options Array of options (value => text)
 * @param string $selected The selected value (optional)
 * @param string $label The label text (optional)
 * @param string $select_class Additional select classes (optional)
 * @param array $attributes Additional select attributes (optional)
 * @return string The HTML for the accessible select
 */
function accessible_select($name, $options, $selected = '', $label = '', $select_class = '', $attributes = []) {
    $id = $name . '_' . uniqid();
    $has_visible_label = !empty($label);
    
    // Add title attribute if no visible label
    if (!$has_visible_label && !isset($attributes['title'])) {
        $attributes['title'] = ucfirst($name);
    }
    
    // Add aria-label if no visible label and no title
    if (!$has_visible_label && !isset($attributes['aria-label']) && !isset($attributes['title'])) {
        $attributes['aria-label'] = ucfirst($name);
    }
    
    $attr_str = '';
    foreach ($attributes as $key => $value) {
        $attr_str .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
    
    $html = '';
    
    // Add label if provided
    if ($has_visible_label) {
        $html .= '<label for="' . $id . '">' . htmlspecialchars($label) . '</label>';
    }
    
    // Create select element
    $html .= '<select id="' . $id . '" name="' . htmlspecialchars($name) . '" class="' . htmlspecialchars($select_class) . '"' . $attr_str . '>';
    
    // Add options
    foreach ($options as $value => $text) {
        $selected_attr = ($value == $selected) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($value) . '"' . $selected_attr . '>' . htmlspecialchars($text) . '</option>';
    }
    
    $html .= '</select>';
    
    return $html;
}
?>
