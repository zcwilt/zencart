<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources\TaxClass;

use Zencart\ViewBuilders\BaseController;

class TaxClassController extends BaseController
{
    protected function currentPage(): int
    {
        return max(1, $this->request->integer('page', 1));
    }

    protected function currentPageParameter(): string
    {
        return 'page=' . $this->currentPage();
    }

    protected function processDefaultAction(): void
    {
        $currentRow = $this->formatter->currentRowFromRequest();
        if ($currentRow === null) {
            return;
        }

        $this->setBoxHeader('<h4>' . $currentRow->tax_class_title . '</h4>');
        $this->setBoxContent(
            '<a href="' . zen_href_link(FILENAME_TAX_CLASSES, $this->currentPageParameter() . '&tID=' . $currentRow->tax_class_id . '&action=edit') . '" class="btn btn-primary" role="button">' . IMAGE_EDIT . '</a> ' .
            '<a href="' . zen_href_link(FILENAME_TAX_CLASSES, $this->currentPageParameter() . '&tID=' . $currentRow->tax_class_id . '&action=delete') . '" class="btn btn-warning" role="button">' . IMAGE_DELETE . '</a>'
        );
        $this->setBoxContent('<br>' . TEXT_INFO_DATE_ADDED . ' ' . zen_date_short($currentRow->date_added));
        $this->setBoxContent(TEXT_INFO_LAST_MODIFIED . ' ' . zen_date_short($currentRow->last_modified));
        $this->setBoxContent('<br>' . TEXT_INFO_CLASS_DESCRIPTION . '<br>' . $currentRow->tax_class_description);
    }

    protected function processActionNew(): void
    {
        $this->setBoxHeader('<h4>' . TEXT_INFO_HEADING_NEW_TAX_CLASS . '</h4>');
        $this->setBoxForm(zen_draw_form('classes', FILENAME_TAX_CLASSES, $this->currentPageParameter() . '&action=insert', 'post', 'class="form-horizontal"'));
        $this->setBoxContent(TEXT_INFO_INSERT_INTRO);
        $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_CLASS_TITLE, 'tax_class_title', 'class="control-label"') . zen_draw_input_field('tax_class_title', '', zen_set_field_length(TABLE_TAX_CLASS, 'tax_class_title') . ' class="form-control"'));
        $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_CLASS_DESCRIPTION, 'tax_class_description', 'class="control-label"') . zen_draw_input_field('tax_class_description', '', zen_set_field_length(TABLE_TAX_CLASS, 'tax_class_description') . ' class="form-control"'));
        $this->setBoxContent('<br><button type="submit" class="btn btn-primary">' . IMAGE_INSERT . '</button> <a href="' . zen_href_link(FILENAME_TAX_CLASSES, $this->currentPageParameter()) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
    }

    protected function processActionEdit(): void
    {
        $currentRow = $this->formatter->currentRowFromRequest();
        if ($currentRow === null) {
            zen_redirect(zen_href_link(FILENAME_TAX_CLASSES, $this->currentPageParameter()));
        }

        $this->setBoxHeader('<h4>' . TEXT_INFO_HEADING_EDIT_TAX_CLASS . '</h4>');
        $this->setBoxForm(zen_draw_form('classes', FILENAME_TAX_CLASSES, $this->currentPageParameter() . '&tID=' . $currentRow->tax_class_id . '&action=save', 'post', 'class="form-horizontal"'));
        $this->setBoxContent(TEXT_INFO_EDIT_INTRO);
        $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_CLASS_TITLE, 'tax_class_title', 'class="control-label"') . zen_draw_input_field('tax_class_title', htmlspecialchars($currentRow->tax_class_title, ENT_COMPAT, CHARSET, true), zen_set_field_length(TABLE_TAX_CLASS, 'tax_class_title') . ' class="form-control"'));
        $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_CLASS_DESCRIPTION, 'tax_class_description', 'class="control-label"') . zen_draw_input_field('tax_class_description', htmlspecialchars($currentRow->tax_class_description, ENT_COMPAT, CHARSET, true), zen_set_field_length(TABLE_TAX_CLASS, 'tax_class_description') . ' class="form-control"'));
        $this->setBoxContent('<br><button type="submit" class="btn btn-primary">' . IMAGE_UPDATE . '</button> <a href="' . zen_href_link(FILENAME_TAX_CLASSES, $this->currentPageParameter() . '&tID=' . $currentRow->tax_class_id) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
    }

    protected function processActionDelete(): void
    {
        $currentRow = $this->formatter->currentRowFromRequest();
        if ($currentRow === null) {
            zen_redirect(zen_href_link(FILENAME_TAX_CLASSES, $this->currentPageParameter()));
        }

        $this->setBoxHeader('<h4>' . TEXT_INFO_HEADING_DELETE_TAX_CLASS . '</h4>');
        $this->setBoxForm(zen_draw_form('classes', FILENAME_TAX_CLASSES, $this->currentPageParameter() . '&action=deleteconfirm', 'post') . zen_draw_hidden_field('tID', $currentRow->tax_class_id));
        $this->setBoxContent(TEXT_INFO_DELETE_INTRO);
        $this->setBoxContent('<br><b>' . $currentRow->tax_class_title . '</b>');
        $this->setBoxContent('<br><button type="submit" class="btn btn-danger">' . IMAGE_DELETE . '</button> <a href="' . zen_href_link(FILENAME_TAX_CLASSES, $this->currentPageParameter() . '&tID=' . $currentRow->tax_class_id) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
    }

    protected function processActionInsert(): void
    {
        global $db;

        $taxClassTitle = zen_db_prepare_input($this->request->post('tax_class_title'));
        $taxClassDescription = zen_db_prepare_input($this->request->post('tax_class_description'));

        $db->Execute("INSERT INTO " . TABLE_TAX_CLASS . " (tax_class_title, tax_class_description, date_added)
                      VALUES ('" . zen_db_input($taxClassTitle) . "', '" . zen_db_input($taxClassDescription) . "', now())");

        zen_redirect(zen_href_link(FILENAME_TAX_CLASSES));
    }

    protected function processActionSave(): void
    {
        global $db;

        $taxClassId = $this->request->integer('tID');
        $taxClassTitle = zen_db_prepare_input($this->request->post('tax_class_title'));
        $taxClassDescription = zen_db_prepare_input($this->request->post('tax_class_description'));

        $db->Execute("UPDATE " . TABLE_TAX_CLASS . "
                        SET tax_class_id = " . $taxClassId . ",
                            tax_class_title = '" . zen_db_input($taxClassTitle) . "',
                            tax_class_description = '" . zen_db_input($taxClassDescription) . "',
                            last_modified = now()
                      WHERE tax_class_id = " . $taxClassId);

        zen_redirect(zen_href_link(FILENAME_TAX_CLASSES, $this->currentPageParameter() . '&tID=' . $taxClassId));
    }

    protected function processActionDeleteconfirm(): void
    {
        global $db;

        $taxClassId = (int) $this->request->post('tID', 0);
        $deleteAllowed = true;

        $result = $db->Execute("SELECT tax_class_id
                                  FROM " . TABLE_TAX_RATES . "
                                 WHERE tax_class_id = " . $taxClassId);
        if ($result->RecordCount() > 0) {
            $deleteAllowed = false;
            $this->messageStack->add_session(ERROR_TAX_RATE_EXISTS_FOR_CLASS, 'error');
        }

        $result = $db->Execute("SELECT COUNT(*) AS count
                                  FROM " . TABLE_PRODUCTS . "
                                 WHERE products_tax_class_id = " . $taxClassId);
        if ((int) $result->fields['count'] > 0) {
            $deleteAllowed = false;
            $this->messageStack->add_session(sprintf(ERROR_TAX_RATE_EXISTS_FOR_PRODUCTS, $result->fields['count']), 'error');
        }

        if ($deleteAllowed) {
            $db->Execute("DELETE FROM " . TABLE_TAX_CLASS . " WHERE tax_class_id = " . $taxClassId);
        }

        zen_redirect(zen_href_link(FILENAME_TAX_CLASSES, $this->currentPageParameter()));
    }
}
