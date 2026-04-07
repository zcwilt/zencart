<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources\Manufacturers;

use Zencart\ViewBuilders\BaseController;

class ManufacturersController extends BaseController
{
    public function processRequest(): void
    {
        $action = $this->getAction();
        $method = ($action === '') ? 'processDefaultAction' : 'processAction' . ucfirst($action);
        if ($action !== '' && !method_exists($this, $method)) {
            global $zco_notifier;
            $zco_notifier->notify('NOTIFY_ADMIN_MANUFACTURERS_DEFAULT_ACTION', ['action' => $action]);
            return;
        }

        parent::processRequest();
    }

    protected function processDefaultAction(): void
    {
        $currentRow = $this->formatter->currentRowFromRequest();
        if ($currentRow === null) {
            return;
        }

        $manufacturerId = (int) $this->rowValue($currentRow, 'manufacturers_id', 0);
        $manufacturerName = (string) $this->rowValue($currentRow, 'manufacturers_name', '');
        $featured = (int) $this->rowValue($currentRow, 'featured', 0) === 1;

        $this->setBoxHeader('<h4>' . $manufacturerName . '</h4>');
        $this->setBoxContent(
            '<a href="' . zen_href_link(FILENAME_MANUFACTURERS, $this->pagePrefix() . 'mID=' . $manufacturerId . '&action=edit') . '" class="btn btn-primary" role="button">' . IMAGE_EDIT . '</a> ' .
            '<a href="' . zen_href_link(FILENAME_MANUFACTURERS, $this->pagePrefix() . 'mID=' . $manufacturerId . '&action=delete') . '" class="btn btn-warning" role="button">' . IMAGE_DELETE . '</a>',
            ['align' => 'text-center']
        );
        if ($featured) {
            $this->setBoxContent('<strong>' . TEXT_MANUFACTURER_IS_FEATURED . '</strong>', ['align' => 'text-center']);
        }
        $this->setBoxContent(TEXT_INFO_DATE_ADDED . ' ' . zen_date_short((string) $this->rowValue($currentRow, 'date_added', '')));
        if (zen_not_null($this->rowValue($currentRow, 'last_modified', ''))) {
            $this->setBoxContent(TEXT_INFO_LAST_MODIFIED . ' ' . zen_date_short((string) $this->rowValue($currentRow, 'last_modified', '')));
        }
        $this->setBoxContent(zen_info_image((string) $this->rowValue($currentRow, 'manufacturers_image', ''), $manufacturerName, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'class="object-fit-contain"'));
        $this->setBoxContent(TEXT_PRODUCTS . ' ' . $this->manufacturerProductsCount($manufacturerId));
    }

    protected function processActionNew(): void
    {
        global $zco_notifier;

        $this->setBoxHeader('<h4>' . TEXT_HEADING_NEW_MANUFACTURER . '</h4>');
        $this->setBoxForm(zen_draw_form('manufacturers', FILENAME_MANUFACTURERS, 'action=insert', 'post', 'enctype="multipart/form-data" class="form-horizontal"'));
        $this->setBoxContent(TEXT_NEW_INTRO);
        $this->setBoxContent(zen_draw_label(TEXT_MANUFACTURERS_NAME, 'manufacturers_name', 'class="control-label"') . zen_draw_input_field('manufacturers_name', '', zen_set_field_length(TABLE_MANUFACTURERS, 'manufacturers_name') . ' class="form-control" id="manufacturers_name" required'));
        $this->setBoxContent('<label class="checkbox-inline">' . zen_draw_checkbox_field('featured') . TEXT_MANUFACTURER_FEATURED_LABEL . '</label>');

        $additionalContents = false;
        $zco_notifier->notify('NOTIFY_ADMIN_MANUFACTURERS_NEW', '', $additionalContents);
        if (is_array($additionalContents)) {
            foreach ($additionalContents as $nextAddition) {
                $this->setBoxContent($nextAddition['text'] ?? '', $this->contentParams($nextAddition));
            }
        }

        $this->addManufacturerImageFields('manufacturers/');
        $this->addManufacturerUrlFields();
        $this->setBoxContent('<button type="submit" class="btn btn-primary">' . IMAGE_SAVE . '</button> <a href="' . zen_href_link(FILENAME_MANUFACTURERS, $this->pagePrefix() . 'mID=' . ($this->request->input('mID', ''))) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
    }

    protected function processActionEdit(): void
    {
        global $zco_notifier;

        $currentRow = $this->formatter->currentRowFromRequest();
        if ($currentRow === null) {
            zen_redirect(zen_href_link(FILENAME_MANUFACTURERS, $this->pagePrefix()));
        }

        $manufacturerId = (int) $this->rowValue($currentRow, 'manufacturers_id', 0);
        $manufacturerName = (string) $this->rowValue($currentRow, 'manufacturers_name', '');
        $this->setBoxHeader('<h4>' . TEXT_HEADING_EDIT_MANUFACTURER . '</h4>');
        $this->setBoxForm(zen_draw_form('manufacturers', FILENAME_MANUFACTURERS, $this->pagePrefix() . 'mID=' . $manufacturerId . '&action=save', 'post', 'enctype="multipart/form-data" class="form-horizontal"'));
        $this->setBoxContent(TEXT_INFO_EDIT_INTRO);
        $this->setBoxContent(zen_draw_label(TEXT_MANUFACTURERS_NAME, 'manufacturers_name', 'class="control-label"') . zen_draw_input_field('manufacturers_name', htmlspecialchars($manufacturerName, ENT_COMPAT, CHARSET, true), zen_set_field_length(TABLE_MANUFACTURERS, 'manufacturers_name') . ' class="form-control" id="manufacturers_name" required'));
        $this->setBoxContent('<label class="checkbox-inline">' . zen_draw_checkbox_field('featured', '1', (bool) $this->rowValue($currentRow, 'featured', false)) . TEXT_MANUFACTURER_FEATURED_LABEL . '</label>');

        $additionalContents = false;
        $zco_notifier->notify('NOTIFY_ADMIN_MANUFACTURERS_EDIT', $currentRow, $additionalContents);
        if (is_array($additionalContents)) {
            foreach ($additionalContents as $nextAddition) {
                $this->setBoxContent($nextAddition['text'] ?? '', $this->contentParams($nextAddition));
            }
        }

        $imageName = (string) $this->rowValue($currentRow, 'manufacturers_image', '');
        $defaultDirectory = ($imageName === '') ? '/' : substr($imageName, 0, strpos($imageName, '/') + 1);
        $this->addManufacturerImageFields($defaultDirectory, $imageName, $manufacturerName);
        $this->addManufacturerUrlFields($manufacturerId);
        $this->setBoxContent('<button type="submit" class="btn btn-primary">' . IMAGE_SAVE . '</button> <a href="' . zen_href_link(FILENAME_MANUFACTURERS, $this->pagePrefix() . 'mID=' . $manufacturerId) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
    }

    protected function processActionDelete(): void
    {
        $currentRow = $this->formatter->currentRowFromRequest();
        if ($currentRow === null) {
            zen_redirect(zen_href_link(FILENAME_MANUFACTURERS, $this->pagePrefix()));
        }

        $manufacturerId = (int) $this->rowValue($currentRow, 'manufacturers_id', 0);
        $this->setBoxHeader('<h4>' . TEXT_HEADING_DELETE_MANUFACTURER . '</h4>');
        $this->setBoxForm(zen_draw_form('manufacturers', FILENAME_MANUFACTURERS, $this->pagePrefix() . 'action=deleteconfirm', 'post', 'class="form-horizontal"') . zen_draw_hidden_field('mID', $manufacturerId));
        $this->setBoxContent(TEXT_DELETE_INTRO);
        $this->setBoxContent('<b>' . (string) $this->rowValue($currentRow, 'manufacturers_name', '') . '</b>');
        $this->setBoxContent('<label class="checkbox-inline">' . zen_draw_checkbox_field('delete_image', '', true) . TEXT_DELETE_IMAGE . '</label>');

        $productsCount = $this->manufacturerProductsCount($manufacturerId);
        if ($productsCount > 0) {
            $this->setBoxContent('<label class="checkbox-inline">' . zen_draw_checkbox_field('delete_products') . TEXT_DELETE_PRODUCTS . '</label>');
            $this->setBoxContent(sprintf(TEXT_DELETE_WARNING_PRODUCTS, $productsCount));
        }

        $this->setBoxContent('<button type="submit" class="btn btn-danger">' . IMAGE_DELETE . '</button> <a href="' . zen_href_link(FILENAME_MANUFACTURERS, $this->pagePrefix() . 'mID=' . $manufacturerId) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
    }

    protected function processActionInsert(): void
    {
        $this->saveManufacturer('insert');
    }

    protected function processActionSave(): void
    {
        $this->saveManufacturer('save');
    }

    protected function processActionDeleteconfirm(): void
    {
        global $db, $zco_notifier;

        $manufacturerId = (int) ($_POST['mID'] ?? 0);

        if (isset($_POST['delete_image']) && $_POST['delete_image'] === 'on') {
            $manufacturer = $db->Execute("SELECT manufacturers_image
                                            FROM " . TABLE_MANUFACTURERS . "
                                           WHERE manufacturers_id = " . $manufacturerId);
            $imageLocation = DIR_FS_CATALOG_IMAGES . ($manufacturer->fields['manufacturers_image'] ?? '');
            if (file_exists($imageLocation)) {
                @unlink($imageLocation);
            }
        }

        $db->Execute("DELETE FROM " . TABLE_MANUFACTURERS . " WHERE manufacturers_id = " . $manufacturerId);
        $db->Execute("DELETE FROM " . TABLE_MANUFACTURERS_INFO . " WHERE manufacturers_id = " . $manufacturerId);

        if (isset($_POST['delete_products']) && $_POST['delete_products'] === 'on') {
            $products = $db->Execute("SELECT products_id
                                        FROM " . TABLE_PRODUCTS . "
                                       WHERE manufacturers_id = " . $manufacturerId);
            foreach ($products as $product) {
                zen_remove_product((int) ($product['products_id'] ?? $product->products_id ?? 0));
            }
        } else {
            $db->Execute("UPDATE " . TABLE_PRODUCTS . "
                             SET manufacturers_id = 0
                           WHERE manufacturers_id = " . $manufacturerId);
        }

        $zco_notifier->notify('NOTIFY_ADMIN_MANUFACTURERS_DELETECONFIRM', ['manufacturers_id' => $manufacturerId]);
        zen_redirect(zen_href_link(FILENAME_MANUFACTURERS, $this->pagePrefix()));
    }

    protected function saveManufacturer(string $action): void
    {
        global $db, $zco_notifier;

        $manufacturerId = (int) $this->request->input('mID', 0);
        $manufacturerName = zen_db_prepare_input($_POST['manufacturers_name'] ?? '');
        $featured = !empty($_POST['featured']) ? 1 : 0;

        $sqlDataArray = [
            'manufacturers_name' => $manufacturerName,
            'featured' => $featured,
        ];

        $zco_notifier->notify('NOTIFY_ADMIN_MANUFACTURERS_INSERT_UPDATE', ['action' => $action, 'manufacturers_id' => $manufacturerId], $sqlDataArray);

        if ($action === 'insert') {
            $sqlDataArray['date_added'] = 'now()';
            zen_db_perform(TABLE_MANUFACTURERS, $sqlDataArray);
            $manufacturerId = zen_db_insert_id();
        } else {
            $sqlDataArray['last_modified'] = 'now()';
            zen_db_perform(TABLE_MANUFACTURERS, $sqlDataArray, 'update', "manufacturers_id = " . $manufacturerId);
        }

        $this->persistManufacturerImage($manufacturerId);
        $this->persistManufacturerUrls($manufacturerId, $action);

        $zco_notifier->notify('NOTIFY_ADMIN_MANUFACTURERS_INSERT_UPDATE_COMPLETE', ['action' => $action, 'manufacturers_id' => $manufacturerId]);
        zen_redirect(zen_href_link(FILENAME_MANUFACTURERS, $this->pagePrefix() . 'mID=' . $manufacturerId));
    }

    protected function persistManufacturerImage(int $manufacturerId): void
    {
        global $db;

        $manualImage = $_POST['manufacturers_image_manual'] ?? '';
        $imageDirectory = (string) ($_POST['img_dir'] ?? '');
        if ($manualImage !== '') {
            $imageName = ($manualImage === 'none') ? '' : zen_db_input($imageDirectory . $manualImage);
            $db->Execute("UPDATE " . TABLE_MANUFACTURERS . "
                             SET manufacturers_image = '" . $imageName . "'
                           WHERE manufacturers_id = " . $manufacturerId);
            return;
        }

        $manufacturersImage = new \upload('manufacturers_image');
        $manufacturersImage->set_extensions(['jpg', 'jpeg', 'gif', 'png', 'webp', 'flv', 'webm', 'ogg']);
        $manufacturersImage->set_destination(DIR_FS_CATALOG_IMAGES . $imageDirectory);
        if ($manufacturersImage->parse() && $manufacturersImage->save()) {
            if ($manufacturersImage->filename !== 'none') {
                $dbFilename = zen_limit_image_filename($manufacturersImage->filename, TABLE_MANUFACTURERS, 'manufacturers_image');
                $db->Execute("UPDATE " . TABLE_MANUFACTURERS . "
                                 SET manufacturers_image = '" . zen_db_input($imageDirectory . $dbFilename) . "'
                               WHERE manufacturers_id = " . $manufacturerId);
            } else {
                $db->Execute("UPDATE " . TABLE_MANUFACTURERS . "
                                 SET manufacturers_image = ''
                               WHERE manufacturers_id = " . $manufacturerId);
            }
        }
    }

    protected function persistManufacturerUrls(int $manufacturerId, string $action): void
    {
        $languages = zen_get_languages();
        $urls = $_POST['manufacturers_url'] ?? [];

        for ($i = 0, $n = count($languages); $i < $n; $i++) {
            $languageId = (int) $languages[$i]['id'];
            $sqlDataArray = ['manufacturers_url' => zen_db_prepare_input($urls[$languageId] ?? '')];

            if ($action === 'insert') {
                $sqlDataArray['manufacturers_id'] = $manufacturerId;
                $sqlDataArray['languages_id'] = $languageId;
                zen_db_perform(TABLE_MANUFACTURERS_INFO, $sqlDataArray);
            } else {
                zen_db_perform(TABLE_MANUFACTURERS_INFO, $sqlDataArray, 'update', "manufacturers_id = " . $manufacturerId . " and languages_id = " . $languageId);
            }
        }
    }

    protected function addManufacturerImageFields(string $defaultDirectory, string $imageName = '', string $manufacturerName = ''): void
    {
        $this->setBoxContent(zen_draw_label(TEXT_MANUFACTURERS_IMAGE, 'manufacturers_image', 'class="control-label"') . zen_draw_file_field('manufacturers_image', '', 'class="form-control" id="manufacturers_image"') . ($imageName !== '' ? '<br>' . $imageName : ''));
        $dirInfo = zen_build_subdirectories_array(DIR_FS_CATALOG_IMAGES);
        $this->setBoxContent(zen_draw_label(TEXT_UPLOAD_DIR, 'img_dir', 'class="control-label"') . zen_draw_pull_down_menu('img_dir', $dirInfo, $defaultDirectory, 'class="form-control" id="img_dir"'));
        $this->setBoxContent(zen_draw_label(TEXT_IMAGE_MANUAL, 'manufacturers_image_manual', 'class="control-label"') . zen_draw_input_field('manufacturers_image_manual', '', 'class="form-control" id="manufacturers_image_manual"'));
        if ($imageName !== '') {
            $this->setBoxContent(zen_info_image($imageName, $manufacturerName, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'class="object-fit-contain"'));
        }
    }

    protected function addManufacturerUrlFields(int $manufacturerId = 0): void
    {
        $languages = zen_get_languages();
        $manufacturerInputsString = '';
        for ($i = 0, $n = count($languages); $i < $n; $i++) {
            $languageId = (int) $languages[$i]['id'];
            $manufacturerUrl = $manufacturerId > 0 ? zen_get_manufacturer_url($manufacturerId, $languageId) : '';
            $manufacturerInputsString .= '<div class="input-group"><span class="input-group-addon">' . zen_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '</span>' . zen_draw_input_field('manufacturers_url[' . $languageId . ']', $manufacturerUrl, zen_set_field_length(TABLE_MANUFACTURERS_INFO, 'manufacturers_url') . ' class="form-control"') . '</div><br>';
        }
        $this->setBoxContent('<p class="p_label control-label">' . TEXT_MANUFACTURERS_URL . '</p>' . $manufacturerInputsString);
    }

    protected function manufacturerProductsCount(int $manufacturerId): int
    {
        global $db;

        $manufacturerProducts = $db->Execute("SELECT COUNT(*) AS products_count
                                                FROM " . TABLE_PRODUCTS . "
                                               WHERE manufacturers_id = " . $manufacturerId);

        return (int) ($manufacturerProducts->fields['products_count'] ?? 0);
    }

    protected function pagePrefix(): string
    {
        $page = (string) $this->request->input('page', '');
        if ($page === '' || !ctype_digit($page) || (int) $page < 1) {
            return '';
        }

        return 'page=' . (int) $page . '&';
    }

    protected function rowValue($row, string $field, $default = null)
    {
        if (is_array($row)) {
            return $row[$field] ?? $default;
        }

        if ($row instanceof \ArrayAccess && isset($row[$field])) {
            return $row[$field];
        }

        if (is_object($row) && isset($row->$field)) {
            return $row->$field;
        }

        return $default;
    }

    protected function contentParams(array $row): array
    {
        unset($row['text']);
        return $row;
    }
}
