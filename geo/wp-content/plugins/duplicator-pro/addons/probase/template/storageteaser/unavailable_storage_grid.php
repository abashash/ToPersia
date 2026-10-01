<?php

defined('ABSPATH') or die('');

/**
 * @var \Duplicator\Core\Views\TplMng $tplMng
 */
$items = $tplMng->getDataValueArray('items');

if (empty($items)) {
    return;
}
?>
<div class="dup-unavailable-wrapper margin-top-1">
    <h3><?php esc_html_e('More storage providers with Duplicator Pro', 'duplicator-pro'); ?></h3>
    <p><?php esc_html_e('Activate your license to unlock the following storage providers.', 'duplicator-pro'); ?></p>
    <div class="dup-unavailable-grid">
        <?php foreach ($items as $item) : ?>
            <div class="dup-unavailable-card" title="<?php echo esc_attr($item['name']); ?>">
                <?php
                echo wp_kses(
                    $item['icon'],
                    [
                        'i'   => ['class' => []],
                        'img' => [
                            'src'   => [],
                            'class' => [],
                            'alt'   => [],
                            'title' => [],
                        ],
                    ]
                );
                ?>
                <div class="dup-unavailable-card__label"><?php echo esc_html($item['name']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
