<?php
/**
 * Expected variables:
 * - object $formatter
 *
 * Optional variables:
 * - string $toolbarFormName
 * - string $toolbarColumnClass
 * - array $toolbarHiddenParameters
 * - string $searchButtonLabel
 * - string $resetButtonLabel
 */
$toolbarFormName = $toolbarFormName ?? 'table-search';
$toolbarColumnClass = $toolbarColumnClass ?? 'col-xs-12';
$searchButtonLabel = $searchButtonLabel ?? (defined('IMAGE_SEARCH') ? IMAGE_SEARCH : 'Search');
$resetButtonLabel = $resetButtonLabel ?? (defined('IMAGE_RESET') ? IMAGE_RESET : 'Reset');
$hasSearch = method_exists($formatter, 'hasSearch') && $formatter->hasSearch();
$hasFilters = method_exists($formatter, 'hasFilters') && $formatter->hasFilters();
$toolbarBeforeHtml = '';
$toolbarAfterHtml = '';

if (!isset($toolbarHiddenParameters)) {
    if (method_exists($formatter, 'toolbarHiddenParameters')) {
        $toolbarHiddenParameters = $formatter->toolbarHiddenParameters();
    } elseif (method_exists($formatter, 'searchHiddenParameters')) {
        $toolbarHiddenParameters = $formatter->searchHiddenParameters();
    } else {
        $toolbarHiddenParameters = [];
    }
}

global $zco_notifier;
if (isset($zco_notifier) && is_object($zco_notifier) && method_exists($zco_notifier, 'notify')) {
    $state = [
        'hasSearch' => $hasSearch,
        'hasFilters' => $hasFilters,
    ];
    $zco_notifier->notify(
        'NOTIFY_ADMIN_RESOURCE_TOOLBAR_START',
        ['formatter' => $formatter],
        $toolbarBeforeHtml,
        $toolbarAfterHtml,
        $toolbarFormName,
        $toolbarColumnClass,
        $toolbarHiddenParameters,
        $searchButtonLabel,
        $resetButtonLabel,
        $state
    );
    $hasSearch = $state['hasSearch'] ?? $hasSearch;
    $hasFilters = $state['hasFilters'] ?? $hasFilters;
}

if ($hasSearch || $hasFilters) {
    ?>
    <?= $toolbarBeforeHtml ?>
    <div class="row">
        <div class="<?= $toolbarColumnClass ?>">
            <?= zen_draw_form($toolbarFormName, $formatter->searchAction(), '', 'get', 'class="form-inline js-resource-search-form" data-lookahead="true" data-lookahead-min="3" data-lookahead-delay="350" data-reset-href="' . htmlspecialchars($formatter->searchResetHref(), ENT_QUOTES) . '"') ?>
            <?php foreach ($toolbarHiddenParameters as $name => $value) { ?>
                <?= zen_draw_hidden_field((string) $name, (string) $value) ?>
            <?php } ?>
            <?php if ($hasFilters) { ?>
                <?php foreach ($formatter->filters() as $filter) { ?>
                    <?php if (($filter['type'] ?? 'select') !== 'select') { continue; } ?>
                    <div class="form-group">
                        <label class="sr-only" for="<?= htmlspecialchars($toolbarFormName . '-filter-' . (string) $filter['key'], ENT_QUOTES) ?>"><?= htmlspecialchars((string) $filter['label'], ENT_QUOTES) ?></label>
                        <select
                            id="<?= htmlspecialchars($toolbarFormName . '-filter-' . (string) $filter['key'], ENT_QUOTES) ?>"
                            name="<?= htmlspecialchars((string) $filter['parameter'], ENT_QUOTES) ?>"
                            class="form-control js-resource-filter-select"
                        >
                            <?php foreach (($filter['options'] ?? []) as $optionValue => $optionLabel) { ?>
                                <option value="<?= htmlspecialchars((string) $optionValue, ENT_QUOTES) ?>"<?= (string) $filter['value'] === (string) $optionValue ? ' selected' : '' ?>>
                                    <?= htmlspecialchars((string) $optionLabel, ENT_QUOTES) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                <?php } ?>
            <?php } ?>
            <?php if ($hasSearch) { ?>
                <div class="form-group">
                    <?= zen_draw_input_field($formatter->searchParameter(), $formatter->searchValue(), 'class="form-control js-resource-search-input" autocomplete="off" placeholder="' . htmlspecialchars($formatter->searchPlaceholder(), ENT_QUOTES) . '"') ?>
                </div>
                <button type="submit" class="btn btn-default"><?= $searchButtonLabel ?></button>
                <?php if ($formatter->searchValue() !== '') { ?>
                    <a href="<?= $formatter->searchResetHref() ?>" class="btn btn-link" role="button"><?= $resetButtonLabel ?></a>
                <?php } ?>
            <?php } ?>
            </form>
        </div>
    </div>
    <?= $toolbarAfterHtml ?>
<?php } ?>
